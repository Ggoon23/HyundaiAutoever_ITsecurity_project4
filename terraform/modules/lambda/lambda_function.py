import json
import boto3
import pymysql
import os
from datetime import datetime

def get_db_credentials():
    """Secrets Manager에서 DB 자격증명 가져오기"""
    secret_name = os.environ['DB_SECRET_NAME']
    region = os.environ['REGION']

    client = boto3.client('secretsmanager', region_name=region)
    response = client.get_secret_value(SecretId=secret_name)
    return json.loads(response['SecretString'])

def connect_db():
    """RDS 연결"""
    creds = get_db_credentials()
    return pymysql.connect(
        host=creds['host'],
        port=int(creds['port']),
        database=creds['dbname'],
        user=creds['username'],
        password=creds['password'],
        cursorclass=pymysql.cursors.DictCursor
    )

def send_alert(subject, message):
    """SNS 알림 전송"""
    sns = boto3.client('sns', region_name=os.environ['REGION'])
    sns.publish(
        TopicArn=os.environ['SNS_TOPIC_ARN'],
        Subject=subject,
        Message=message
    )

def lambda_handler(event, context):
    """Canary 배포 Phase 제어"""
    try:
        conn = connect_db()
        cursor = conn.cursor()

        # 진행 중인 Canary 배포 조회
        cursor.execute("""
            SELECT canary_id, deployment_id, phase,
                   success_count, fail_count, target_percentage
            FROM canary_deployments
            WHERE status = 'in_progress'
        """)

        deployments = cursor.fetchall()

        for deployment in deployments:
            canary_id, deployment_id, phase, success, fail, target_pct = deployment

            total = success + fail
            if total == 0:
                continue

            success_rate = (success / total) * 100

            print(f"Canary {canary_id}: Phase {phase}, Success Rate: {success_rate}%")

            # 실패율 > 5% → 배포 중단
            if success_rate < 95:
                cursor.execute("""
                    UPDATE canary_deployments
                    SET status = 'failed'
                    WHERE canary_id = %s
                """, (canary_id,))

                # Audit log
                cursor.execute("""
                    INSERT INTO audit_logs (actor, action, target, result, details)
                    VALUES (%s, %s, %s, %s, %s)
                """, (
                    'lambda-canary-controller',
                    'canary_abort',
                    f'deployment_{deployment_id}',
                    'aborted',
                    f'Phase {phase} 실패율 {100-success_rate:.1f}% 초과'
                ))

                conn.commit()

                # SNS 알림
                send_alert(
                    '[OTA Alert] Canary Deployment Failed',
                    f'배포 ID: {deployment_id}\n'
                    f'Phase: {phase}\n'
                    f'성공률: {success_rate:.1f}%\n'
                    f'배포가 자동 중단되었습니다.'
                )

            # 성공률 >= 95% → 다음 Phase로 전환
            elif success_rate >= 95:
                next_phase = phase + 1
                if next_phase <= 3:  # Phase 1, 2, 3
                    cursor.execute("""
                        UPDATE canary_deployments
                        SET phase = %s
                        WHERE canary_id = %s
                    """, (next_phase, canary_id))

                    # Audit log
                    cursor.execute("""
                        INSERT INTO audit_logs (actor, action, target, result, details)
                        VALUES (%s, %s, %s, %s, %s)
                    """, (
                        'lambda-canary-controller',
                        'canary_phase_transition',
                        f'deployment_{deployment_id}',
                        'success',
                        f'Phase {phase} → Phase {next_phase} 전환'
                    ))

                    conn.commit()

                    print(f"Phase {phase} → Phase {next_phase} 전환")
                else:
                    # 모든 Phase 완료
                    cursor.execute("""
                        UPDATE canary_deployments
                        SET status = 'completed'
                        WHERE canary_id = %s
                    """, (canary_id,))

                    conn.commit()
                    print(f"Deployment {deployment_id} 완료")

        cursor.close()
        conn.close()

        return {
            'statusCode': 200,
            'body': json.dumps('Canary check completed')
        }

    except Exception as e:
        print(f"Error: {str(e)}")
        return {
            'statusCode': 500,
            'body': json.dumps(f'Error: {str(e)}')
        }

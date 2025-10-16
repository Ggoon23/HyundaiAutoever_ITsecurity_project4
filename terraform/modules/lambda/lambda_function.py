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
    """OTA 업데이트 모니터링 및 알림"""
    try:
        conn = connect_db()
        cursor = conn.cursor()

        # 최근 실패한 업데이트 조회
        cursor.execute("""
            SELECT update_id, vin, ecu, phase, error, ts
            FROM reports
            WHERE phase = 'failed'
            AND ts > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        """)

        failed_updates = cursor.fetchall()

        # 실패한 업데이트가 있으면 알림 전송
        if failed_updates:
            message = "최근 1시간 내 실패한 OTA 업데이트:\n\n"
            for update in failed_updates:
                message += f"Update ID: {update['update_id']}\n"
                message += f"VIN: {update['vin']}\n"
                message += f"ECU: {update['ecu']}\n"
                message += f"Phase: {update['phase']}\n"
                message += f"Error: {update['error']}\n"
                message += f"Time: {update['ts']}\n"
                message += "---\n"

            send_alert(
                '[OTA Alert] Update Failures Detected',
                message
            )

        # 진행 중인 업데이트 통계
        cursor.execute("""
            SELECT update_id, phase, COUNT(*) as count
            FROM reports
            WHERE ts > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY update_id, phase
        """)

        stats = cursor.fetchall()

        print(f"Update statistics: {json.dumps(stats, default=str)}")

        cursor.close()
        conn.close()

        return {
            'statusCode': 200,
            'body': json.dumps({
                'failed_count': len(failed_updates),
                'stats': stats
            }, default=str)
        }

    except Exception as e:
        print(f"Error: {str(e)}")
        return {
            'statusCode': 500,
            'body': json.dumps(f'Error: {str(e)}')
        }

# AWS OTA 인프라 Terraform 구축 지시사항

## 프로젝트 개요

자동차 OTA(Over-The-Air) 펌웨어 배포 시스템의 AWS 인프라를 Terraform으로 구축합니다.
이 인프라는 침해사고 시뮬레이션 프로젝트용이며, 실무 환경과 유사하게 구성됩니다.

**목표:**
- 자동차 펌웨어 OTA 배포 시스템 인프라
- Uptane 프레임워크 기반 보안 아키텍처
- Canary 배포 지원
- 침해사고 시뮬레이션 가능한 구조

**AWS Region:** ap-northeast-2 (서울)

---

## 전체 아키텍처

```
인터넷
  ↓
ALB (Public Subnet)
  ↓
EC2 Auto Scaling (Public Subnet)
  ↓
RDS MySQL (Private Subnet)

EC2 → S3 (Firmware Storage)
EC2 → Secrets Manager (Credentials)
All → CloudWatch (Logs & Metrics)
All → CloudTrail (Audit)
Lambda → RDS (Canary Automation)
```

---

## Terraform 프로젝트 구조

```
terraform/
├── main.tf                 # Provider 및 모듈 호출
├── variables.tf            # 입력 변수
├── outputs.tf              # 출력값
├── terraform.tfvars        # 변수값
├── backend.tf              # State 백엔드 (선택)
│
├── modules/
│   ├── vpc/
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   │
│   ├── security-groups/
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   │
│   ├── iam/
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   │
│   ├── s3/
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   │
│   ├── rds/
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   │
│   ├── ec2/
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   │
│   ├── alb/
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   │
│   ├── lambda/
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   ├── outputs.tf
│   │   └── lambda_function.py
│   │
│   ├── cloudwatch/
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   │
│   ├── cloudtrail/
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   │
│   ├── sns/
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   └── outputs.tf
│   │
│   └── secrets-manager/
│       ├── main.tf
│       ├── variables.tf
│       └── outputs.tf
│
└── scripts/
    ├── user-data.sh        # EC2 User Data
    └── init-db.sql         # RDS 초기 스키마
```

---

## 1. VPC 모듈 (modules/vpc)

### 요구사항

**VPC:**
- CIDR: 10.0.0.0/16
- DNS 지원 활성화
- DNS 호스트네임 활성화
- 태그: Name=ota-vpc

**Public Subnets:**
- public-subnet-a: 10.0.1.0/24 (ap-northeast-2a)
- public-subnet-b: 10.0.2.0/24 (ap-northeast-2b)
- Map Public IP on Launch: true

**Private Subnets:**
- private-subnet-a: 10.0.11.0/24 (ap-northeast-2a)
- private-subnet-b: 10.0.12.0/24 (ap-northeast-2b)
- Map Public IP on Launch: false

**Internet Gateway:**
- VPC에 연결

**Route Tables:**
- Public Route Table:
  - 0.0.0.0/0 → Internet Gateway
  - Public Subnets 연결
  
- Private Route Table:
  - Local only (10.0.0.0/16)
  - Private Subnets 연결

**VPC Endpoints:**
- S3 Gateway Endpoint (무료)
  - Route Table: Public, Private 모두 연결

### 출력값
- vpc_id
- public_subnet_ids
- private_subnet_ids
- s3_endpoint_id

---

## 2. Security Groups 모듈 (modules/security-groups)

### sg-alb
```hcl
ingress {
  from_port   = 80
  to_port     = 80
  protocol    = "tcp"
  cidr_blocks = ["0.0.0.0/0"]
}

ingress {
  from_port   = 443
  to_port     = 443
  protocol    = "tcp"
  cidr_blocks = ["0.0.0.0/0"]
}

egress {
  from_port   = 0
  to_port     = 0
  protocol    = "-1"
  cidr_blocks = ["10.0.0.0/16"]
}
```

### sg-ec2
```hcl
ingress {
  from_port       = 80
  to_port         = 80
  protocol        = "tcp"
  security_groups = [sg-alb.id]
}

ingress {
  from_port       = 443
  to_port         = 443
  protocol        = "tcp"
  security_groups = [sg-alb.id]
}

ingress {
  from_port   = 22
  to_port     = 22
  protocol    = "tcp"
  cidr_blocks = ["YOUR_ADMIN_IP/32"]  # 변수로 받기
  description = "Admin SSH access"
}

egress {
  from_port   = 0
  to_port     = 0
  protocol    = "-1"
  cidr_blocks = ["0.0.0.0/0"]
}
```

### sg-rds
```hcl
ingress {
  from_port       = 3306
  to_port         = 3306
  protocol        = "tcp"
  security_groups = [sg-ec2.id, sg-lambda.id]
}

egress {
  # No outbound rules (default deny)
}
```

### sg-lambda
```hcl
egress {
  from_port   = 3306
  to_port     = 3306
  protocol    = "tcp"
  security_groups = [sg-rds.id]
}

egress {
  from_port   = 443
  to_port     = 443
  protocol    = "tcp"
  cidr_blocks = ["0.0.0.0/0"]
  description = "AWS API calls"
}
```

### 출력값
- sg_alb_id
- sg_ec2_id
- sg_rds_id
- sg_lambda_id

---

## 3. IAM 모듈 (modules/iam)

### ota-ec2-role

**Trust Policy:**
```json
{
  "Version": "2012-10-17",
  "Statement": [{
    "Effect": "Allow",
    "Principal": {"Service": "ec2.amazonaws.com"},
    "Action": "sts:AssumeRole"
  }]
}
```

**Inline Policy:**
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:GetObject",
        "s3:PutObject",
        "s3:ListBucket"
      ],
      "Resource": [
        "arn:aws:s3:::ota-firmware-bucket-*",
        "arn:aws:s3:::ota-firmware-bucket-*/*"
      ]
    },
    {
      "Effect": "Allow",
      "Action": [
        "secretsmanager:GetSecretValue"
      ],
      "Resource": [
        "arn:aws:secretsmanager:ap-northeast-2:*:secret:ota/rds/credentials*",
        "arn:aws:secretsmanager:ap-northeast-2:*:secret:ota/api-keys/vendor-b*"
      ]
    },
    {
      "Effect": "Allow",
      "Action": [
        "logs:CreateLogGroup",
        "logs:CreateLogStream",
        "logs:PutLogEvents"
      ],
      "Resource": "arn:aws:logs:ap-northeast-2:*:*"
    }
  ]
}
```

### lambda-canary-role

**Trust Policy:**
```json
{
  "Version": "2012-10-17",
  "Statement": [{
    "Effect": "Allow",
    "Principal": {"Service": "lambda.amazonaws.com"},
    "Action": "sts:AssumeRole"
  }]
}
```

**Managed Policies:**
- AWSLambdaVPCAccessExecutionRole

**Inline Policy:**
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "secretsmanager:GetSecretValue"
      ],
      "Resource": "arn:aws:secretsmanager:ap-northeast-2:*:secret:ota/rds/credentials*"
    },
    {
      "Effect": "Allow",
      "Action": [
        "sns:Publish"
      ],
      "Resource": [
        "arn:aws:sns:ap-northeast-2:*:ota-alerts",
        "arn:aws:sns:ap-northeast-2:*:ota-cloudtrail-alerts"
      ]
    },
    {
      "Effect": "Allow",
      "Action": [
        "logs:CreateLogGroup",
        "logs:CreateLogStream",
        "logs:PutLogEvents"
      ],
      "Resource": "*"
    }
  ]
}
```

### 출력값
- ec2_role_arn
- ec2_instance_profile_name
- lambda_role_arn

---

## 4. S3 모듈 (modules/s3)

### Bucket 설정
```hcl
bucket = "ota-firmware-bucket-${data.aws_caller_identity.current.account_id}"

versioning {
  enabled = true
}

server_side_encryption_configuration {
  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

public_access_block {
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}
```

### Folder Structure
```
폴더 생성 (빈 객체로):
- image-repo/targets/.keep
- image-repo/metadata/.keep
- director-repo/metadata/.keep
- certificates/.keep
- public-keys/.keep
```

### Lifecycle Rules
```hcl
lifecycle_rule {
  id      = "archive-old-versions"
  enabled = true

  noncurrent_version_transition {
    days          = 90
    storage_class = "GLACIER"
  }

  noncurrent_version_expiration {
    days = 180
  }
}
```

### Bucket Policy
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "DenyInsecureTransport",
      "Effect": "Deny",
      "Principal": "*",
      "Action": "s3:*",
      "Resource": [
        "arn:aws:s3:::BUCKET_NAME",
        "arn:aws:s3:::BUCKET_NAME/*"
      ],
      "Condition": {
        "Bool": {
          "aws:SecureTransport": "false"
        }
      }
    }
  ]
}
```

### 출력값
- bucket_name
- bucket_arn
- bucket_domain_name

---

## 5. RDS 모듈 (modules/rds)

### DB Subnet Group
```hcl
name       = "ota-db-subnet-group"
subnet_ids = [private_subnet_a_id, private_subnet_b_id]
```

### DB Instance
```hcl
identifier           = "ota-mysql"
engine               = "mysql"
engine_version       = "8.0.42"
instance_class       = "db.t3.micro"
allocated_storage    = 20
max_allocated_storage = 50
storage_type         = "gp3"
storage_encrypted    = true

db_name  = "ota_db"
username = "admin"
password = "password"  # 고정 credential

multi_az               = false
publicly_accessible    = false
vpc_security_group_ids = [sg_rds_id]
db_subnet_group_name   = aws_db_subnet_group.ota.name

backup_retention_period = 3
backup_window          = "03:00-04:00"
maintenance_window     = "sun:04:00-sun:05:00"

enabled_cloudwatch_logs_exports = ["error", "general", "slowquery"]

skip_final_snapshot       = false
final_snapshot_identifier = "ota-mysql-final-snapshot-${timestamp()}"

parameter_group_name = aws_db_parameter_group.ota.name

tags = {
  Name        = "ota-mysql"
  Environment = "development"
}
```

### DB Parameter Group
```hcl
resource "aws_db_parameter_group" "ota" {
  name   = "ota-mysql-params"
  family = "mysql8.0"

  parameter {
    name  = "character_set_server"
    value = "utf8mb4"
  }

  parameter {
    name  = "collation_server"
    value = "utf8mb4_unicode_ci"
  }

  parameter {
    name  = "max_connections"
    value = "100"
  }

  tags = {
    Name = "ota-mysql-parameter-group"
  }
}
```

### 초기 스키마 (사용자가 직접 생성)
`scripts/init-db.sql` 파일:
```sql
-- OTA Database Schema
-- MySQL 8.0
-- Character Set: UTF8MB4

-- 사용자가 직접 테이블을 생성할 예정입니다.
```

### 출력값
- db_instance_endpoint
- db_instance_id
- db_instance_address
- db_instance_port

---

## 6. EC2 모듈 (modules/ec2)

### Launch Template
```hcl
name_prefix   = "ota-server-"
image_id      = data.aws_ami.amazon_linux_2023.id
instance_type = "t2.micro"

iam_instance_profile {
  name = var.instance_profile_name
}

vpc_security_group_ids = [var.sg_ec2_id]

user_data = base64encode(templatefile("${path.module}/../../scripts/user-data.sh", {
  db_secret_name = var.db_secret_name
  region         = var.region
}))

tag_specifications {
  resource_type = "instance"
  tags = {
    Name = "ota-server"
  }
}
```

### User Data Script (`scripts/user-data.sh`)
```bash
#!/bin/bash
set -e

# 로그 설정
exec > >(tee /var/log/user-data.log)
exec 2>&1

echo "Starting OTA Server initialization..."

# 패키지 업데이트
yum update -y

# Docker 설치
yum install -y docker jq
systemctl start docker
systemctl enable docker

# CloudWatch Agent 설치
wget https://s3.amazonaws.com/amazoncloudwatch-agent/amazon_linux/amd64/latest/amazon-cloudwatch-agent.rpm
rpm -U ./amazon-cloudwatch-agent.rpm

# Secrets Manager에서 DB 정보 가져오기
DB_SECRET=$(aws secretsmanager get-secret-value \
  --secret-id ${db_secret_name} \
  --region ${region} \
  --query SecretString --output text)

DB_HOST=$(echo $DB_SECRET | jq -r .host)
DB_PORT=$(echo $DB_SECRET | jq -r .port)
DB_NAME=$(echo $DB_SECRET | jq -r .dbname)
DB_USER=$(echo $DB_SECRET | jq -r .username)
DB_PASSWORD=$(echo $DB_SECRET | jq -r .password)

# OTA 애플리케이션 컨테이너 실행 (임시 nginx)
docker run -d \
  -p 80:80 \
  --name ota-server \
  --restart unless-stopped \
  nginx:latest

# Health check 엔드포인트 생성
docker exec ota-server bash -c 'echo "OK" > /usr/share/nginx/html/health'

echo "OTA Server initialization completed!"
```

### Auto Scaling Group
```hcl
name                = "ota-asg"
vpc_zone_identifier = var.public_subnet_ids
min_size            = 1
max_size            = 2
desired_capacity    = 1
health_check_type   = "ELB"
health_check_grace_period = 300

launch_template {
  id      = aws_launch_template.ota.id
  version = "$Latest"
}

target_group_arns = [var.target_group_arn]

tag {
  key                 = "Name"
  value               = "ota-server"
  propagate_at_launch = true
}
```

### Auto Scaling Policy
```hcl
resource "aws_autoscaling_policy" "cpu_target" {
  name                   = "ota-cpu-target"
  autoscaling_group_name = aws_autoscaling_group.ota.name
  policy_type            = "TargetTrackingScaling"

  target_tracking_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ASGAverageCPUUtilization"
    }
    target_value = 70.0
  }
}
```

### 출력값
- launch_template_id
- autoscaling_group_name
- autoscaling_group_arn

---

## 7. ALB 모듈 (modules/alb)

### Target Group
```hcl
name     = "ota-tg"
port     = 80
protocol = "HTTP"
vpc_id   = var.vpc_id

health_check {
  enabled             = true
  healthy_threshold   = 2
  unhealthy_threshold = 3
  timeout             = 5
  interval            = 30
  path                = "/health"
  protocol            = "HTTP"
  matcher             = "200"
}

deregistration_delay = 30

tags = {
  Name = "ota-target-group"
}
```

### ALB
```hcl
name               = "ota-alb"
internal           = false
load_balancer_type = "application"
security_groups    = [var.sg_alb_id]
subnets            = var.public_subnet_ids

enable_deletion_protection = false
enable_http2              = true
enable_cross_zone_load_balancing = true

tags = {
  Name = "ota-alb"
}
```

### Listeners

**HTTP Listener (80):**
```hcl
resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.ota.arn
  port              = 80
  protocol          = "HTTP"

  default_action {
    type = "redirect"

    redirect {
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }
}
```

**HTTPS Listener (443):**
```hcl
resource "aws_lb_listener" "https" {
  load_balancer_arn = aws_lb.ota.arn
  port              = 443
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-2017-01"
  certificate_arn   = var.certificate_arn  # ACM 인증서 필요

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.ota.arn
  }
}
```

**Path-based Routing Rules:**
```hcl
# /firmware/* 라우팅
resource "aws_lb_listener_rule" "firmware" {
  listener_arn = aws_lb_listener.https.arn
  priority     = 100

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.ota.arn
  }

  condition {
    path_pattern {
      values = ["/firmware/*"]
    }
  }
}

# /metadata/* 라우팅
resource "aws_lb_listener_rule" "metadata" {
  listener_arn = aws_lb_listener.https.arn
  priority     = 101

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.ota.arn
  }

  condition {
    path_pattern {
      values = ["/metadata/*"]
    }
  }
}

# /vehicle/* 라우팅
resource "aws_lb_listener_rule" "vehicle" {
  listener_arn = aws_lb_listener.https.arn
  priority     = 102

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.ota.arn
  }

  condition {
    path_pattern {
      values = ["/vehicle/*"]
    }
  }
}

# /deploy/* 라우팅
resource "aws_lb_listener_rule" "deploy" {
  listener_arn = aws_lb_listener.https.arn
  priority     = 103

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.ota.arn
  }

  condition {
    path_pattern {
      values = ["/deploy/*"]
    }
  }
}
```

### 출력값
- alb_arn
- alb_dns_name
- alb_zone_id
- target_group_arn

---

## 8. Lambda 모듈 (modules/lambda)

### Lambda Function
```hcl
filename         = data.archive_file.lambda_zip.output_path
function_name    = "canary-phase-controller"
role             = var.lambda_role_arn
handler          = "lambda_function.lambda_handler"
source_code_hash = data.archive_file.lambda_zip.output_base64sha256
runtime          = "python3.11"
timeout          = 60
memory_size      = 256

vpc_config {
  subnet_ids         = var.private_subnet_ids
  security_group_ids = [var.sg_lambda_id]
}

environment {
  variables = {
    DB_SECRET_NAME = var.db_secret_name
    REGION         = var.region
    SNS_TOPIC_ARN  = var.sns_topic_arn
  }
}

tags = {
  Name = "canary-phase-controller"
}
```

### Lambda Function Code (`modules/lambda/lambda_function.py`)
```python
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
```

### EventBridge Rule
```hcl
resource "aws_cloudwatch_event_rule" "canary_check" {
  name                = "canary-phase-check"
  description         = "Canary 배포 Phase 체크 (5분 간격)"
  schedule_expression = "rate(5 minutes)"
}

resource "aws_cloudwatch_event_target" "lambda" {
  rule      = aws_cloudwatch_event_rule.canary_check.name
  target_id = "canary-lambda"
  arn       = aws_lambda_function.canary.arn
}

resource "aws_lambda_permission" "allow_eventbridge" {
  statement_id  = "AllowExecutionFromEventBridge"
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.canary.function_name
  principal     = "events.amazonaws.com"
  source_arn    = aws_cloudwatch_event_rule.canary_check.arn
}
```

### Lambda Layer (pymysql)
```hcl
# pymysql Layer 생성 (별도로 빌드 필요)
resource "aws_lambda_layer_version" "pymysql" {
  filename   = "pymysql-layer.zip"  # 사전 빌드 필요
  layer_name = "pymysql"

  compatible_runtimes = ["python3.11"]
}

# Lambda Function에 Layer 추가
resource "aws_lambda_function" "canary" {
  # ... (위 설정)

  layers = [aws_lambda_layer_version.pymysql.arn]
}
```

### 출력값
- lambda_function_arn
- lambda_function_name

---

## 9. CloudWatch 모듈 (modules/cloudwatch)

### Log Groups
```hcl
# EC2 로그
resource "aws_cloudwatch_log_group" "ec2" {
  name              = "/aws/ec2/ota-server"
  retention_in_days = 14
}

# ALB 로그
resource "aws_cloudwatch_log_group" "alb" {
  name              = "/aws/alb/ota-alb"
  retention_in_days = 14
}

# RDS 로그
resource "aws_cloudwatch_log_group" "rds" {
  name              = "/aws/rds/instance/ota-mysql/error"
  retention_in_days = 14
}

# Lambda 로그
resource "aws_cloudwatch_log_group" "lambda" {
  name              = "/aws/lambda/canary-controller"
  retention_in_days = 14
}
```

### CloudWatch Alarms

**EC2 Alarms:**
```hcl
# CPU > 80%
resource "aws_cloudwatch_metric_alarm" "ec2_cpu" {
  alarm_name          = "ota-ec2-high-cpu"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "CPUUtilization"
  namespace           = "AWS/EC2"
  period              = 300
  statistic           = "Average"
  threshold           = 80
  alarm_description   = "EC2 CPU 사용률이 80%를 초과했습니다"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    AutoScalingGroupName = var.asg_name
  }
}

# Status Check Failed
resource "aws_cloudwatch_metric_alarm" "ec2_status" {
  alarm_name          = "ota-ec2-status-check"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "StatusCheckFailed"
  namespace           = "AWS/EC2"
  period              = 60
  statistic           = "Maximum"
  threshold           = 0
  alarm_description   = "EC2 Status Check 실패"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    AutoScalingGroupName = var.asg_name
  }
}
```

**ALB Alarms:**
```hcl
# Unhealthy Hosts > 0
resource "aws_cloudwatch_metric_alarm" "alb_unhealthy" {
  alarm_name          = "ota-alb-unhealthy-hosts"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "UnHealthyHostCount"
  namespace           = "AWS/ApplicationELB"
  period              = 60
  statistic           = "Maximum"
  threshold           = 0
  alarm_description   = "ALB에 Unhealthy Host 존재"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    LoadBalancer = var.alb_arn_suffix
    TargetGroup  = var.target_group_arn_suffix
  }
}

# Response Time > 2s
resource "aws_cloudwatch_metric_alarm" "alb_response_time" {
  alarm_name          = "ota-alb-high-response-time"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "TargetResponseTime"
  namespace           = "AWS/ApplicationELB"
  period              = 300
  statistic           = "Average"
  threshold           = 2
  alarm_description   = "ALB 응답 시간이 2초를 초과했습니다"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    LoadBalancer = var.alb_arn_suffix
  }
}

# 5xx Errors > 10
resource "aws_cloudwatch_metric_alarm" "alb_5xx" {
  alarm_name          = "ota-alb-5xx-errors"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 1
  metric_name         = "HTTPCode_Target_5XX_Count"
  namespace           = "AWS/ApplicationELB"
  period              = 300
  statistic           = "Sum"
  threshold           = 10
  alarm_description   = "ALB 5xx 에러가 10건을 초과했습니다"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    LoadBalancer = var.alb_arn_suffix
  }
}
```

**RDS Alarms:**
```hcl
# CPU > 80%
resource "aws_cloudwatch_metric_alarm" "rds_cpu" {
  alarm_name          = "ota-rds-high-cpu"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "CPUUtilization"
  namespace           = "AWS/RDS"
  period              = 300
  statistic           = "Average"
  threshold           = 80
  alarm_description   = "RDS CPU 사용률이 80%를 초과했습니다"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    DBInstanceIdentifier = var.db_instance_id
  }
}

# Free Storage < 2GB
resource "aws_cloudwatch_metric_alarm" "rds_storage" {
  alarm_name          = "ota-rds-low-storage"
  comparison_operator = "LessThanThreshold"
  evaluation_periods  = 1
  metric_name         = "FreeStorageSpace"
  namespace           = "AWS/RDS"
  period              = 300
  statistic           = "Average"
  threshold           = 2147483648  # 2GB in bytes
  alarm_description   = "RDS 여유 공간이 2GB 미만입니다"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    DBInstanceIdentifier = var.db_instance_id
  }
}

# Connections > 50
resource "aws_cloudwatch_metric_alarm" "rds_connections" {
  alarm_name          = "ota-rds-high-connections"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "DatabaseConnections"
  namespace           = "AWS/RDS"
  period              = 300
  statistic           = "Average"
  threshold           = 50
  alarm_description   = "RDS 연결 수가 50을 초과했습니다"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    DBInstanceIdentifier = var.db_instance_id
  }
}
```

### Dashboards
```hcl
resource "aws_cloudwatch_dashboard" "ota_main" {
  dashboard_name = "ota-main-dashboard"

  dashboard_body = jsonencode({
    widgets = [
      {
        type = "metric"
        properties = {
          metrics = [
            ["AWS/EC2", "CPUUtilization", { stat = "Average" }],
            ["AWS/ApplicationELB", "TargetResponseTime", { stat = "Average" }],
            ["AWS/RDS", "CPUUtilization", { stat = "Average" }]
          ]
          period = 300
          stat   = "Average"
          region = "ap-northeast-2"
          title  = "System Overview"
        }
      }
    ]
  })
}
```

---

## 10. CloudTrail 모듈 (modules/cloudtrail)

### CloudTrail Logging Bucket
```hcl
resource "aws_s3_bucket" "cloudtrail" {
  bucket = "ota-cloudtrail-logs-${data.aws_caller_identity.current.account_id}"
}

resource "aws_s3_bucket_policy" "cloudtrail" {
  bucket = aws_s3_bucket.cloudtrail.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid    = "AWSCloudTrailAclCheck"
        Effect = "Allow"
        Principal = {
          Service = "cloudtrail.amazonaws.com"
        }
        Action   = "s3:GetBucketAcl"
        Resource = aws_s3_bucket.cloudtrail.arn
      },
      {
        Sid    = "AWSCloudTrailWrite"
        Effect = "Allow"
        Principal = {
          Service = "cloudtrail.amazonaws.com"
        }
        Action   = "s3:PutObject"
        Resource = "${aws_s3_bucket.cloudtrail.arn}/*"
        Condition = {
          StringEquals = {
            "s3:x-amz-acl" = "bucket-owner-full-control"
          }
        }
      }
    ]
  })
}

resource "aws_s3_bucket_lifecycle_configuration" "cloudtrail" {
  bucket = aws_s3_bucket.cloudtrail.id

  rule {
    id     = "archive-old-logs"
    status = "Enabled"

    transition {
      days          = 90
      storage_class = "GLACIER"
    }

    expiration {
      days = 365
    }
  }
}
```

### CloudTrail
```hcl
resource "aws_cloudtrail" "ota" {
  name                          = "ota-trail"
  s3_bucket_name                = aws_s3_bucket.cloudtrail.id
  include_global_service_events = true
  is_multi_region_trail         = false
  enable_log_file_validation    = true

  event_selector {
    read_write_type           = "All"
    include_management_events = true

    data_resource {
      type   = "AWS::S3::Object"
      values = ["${var.firmware_bucket_arn}/*"]
    }
  }

  insight_selector {
    insight_type = "ApiCallRateInsight"
  }

  tags = {
    Name = "ota-trail"
  }
}
```

### EventBridge Rules

**의심스러운 S3 활동:**
```hcl
resource "aws_cloudwatch_event_rule" "s3_suspicious" {
  name        = "ota-suspicious-s3-access"
  description = "S3 DeleteObject/DeleteBucket 감지"

  event_pattern = jsonencode({
    source      = ["aws.s3"]
    detail-type = ["AWS API Call via CloudTrail"]
    detail = {
      eventName = ["DeleteObject", "DeleteBucket", "PutBucketPolicy"]
    }
  })
}

resource "aws_cloudwatch_event_target" "s3_suspicious" {
  rule      = aws_cloudwatch_event_rule.s3_suspicious.name
  target_id = "SendToSNS"
  arn       = var.sns_security_topic_arn
}
```

**EC2 종료 시도:**
```hcl
resource "aws_cloudwatch_event_rule" "ec2_terminate" {
  name        = "ota-unauthorized-ec2-action"
  description = "EC2 Terminate/Stop 감지"

  event_pattern = jsonencode({
    source      = ["aws.ec2"]
    detail-type = ["AWS API Call via CloudTrail"]
    detail = {
      eventName = ["TerminateInstances", "StopInstances"]
    }
  })
}

resource "aws_cloudwatch_event_target" "ec2_terminate" {
  rule      = aws_cloudwatch_event_rule.ec2_terminate.name
  target_id = "SendToSNS"
  arn       = var.sns_security_topic_arn
}
```

**RDS 변경:**
```hcl
resource "aws_cloudwatch_event_rule" "rds_modification" {
  name        = "ota-rds-modification"
  description = "RDS 삭제/수정 감지"

  event_pattern = jsonencode({
    source      = ["aws.rds"]
    detail-type = ["AWS API Call via CloudTrail"]
    detail = {
      eventName = ["DeleteDBInstance", "ModifyDBInstance", "DeleteDBSnapshot"]
    }
  })
}

resource "aws_cloudwatch_event_target" "rds_modification" {
  rule      = aws_cloudwatch_event_rule.rds_modification.name
  target_id = "SendToSNS"
  arn       = var.sns_security_topic_arn
}
```

---

## 11. SNS 모듈 (modules/sns)

### SNS Topics
```hcl
# 일반 알림
resource "aws_sns_topic" "alerts" {
  name         = "ota-alerts"
  display_name = "OTA System Alerts"
}

# 보안 알림
resource "aws_sns_topic" "security" {
  name         = "ota-cloudtrail-alerts"
  display_name = "OTA CloudTrail Alerts"
}
```

### Email Subscriptions
```hcl
resource "aws_sns_topic_subscription" "alerts_email" {
  topic_arn = aws_sns_topic.alerts.arn
  protocol  = "email"
  endpoint  = "xogoon1325@gmail.com"
}

resource "aws_sns_topic_subscription" "security_email" {
  topic_arn = aws_sns_topic.security.arn
  protocol  = "email"
  endpoint  = "xogoon1325@gmail.com"
}
```

### 출력값
- alerts_topic_arn
- security_topic_arn

---

## 12. Secrets Manager 모듈 (modules/secrets-manager)

### RDS Credentials
```hcl
resource "random_password" "rds" {
  length  = 32
  special = true
}

resource "aws_secretsmanager_secret" "rds_credentials" {
  name                    = "ota/rds/credentials"
  recovery_window_in_days = 7
}

resource "aws_secretsmanager_secret_version" "rds_credentials" {
  secret_id = aws_secretsmanager_secret.rds_credentials.id
  secret_string = jsonencode({
    username = "admin"
    password = "password"
    engine   = "mysql"
    host     = var.db_instance_address
    port     = var.db_instance_port
    dbname   = "ota_db"
  })
}

# 30일 자동 로테이션
resource "aws_secretsmanager_secret_rotation" "rds" {
  secret_id           = aws_secretsmanager_secret.rds_credentials.id
  rotation_lambda_arn = aws_lambda_function.rotate_secret.arn

  rotation_rules {
    automatically_after_days = 30
  }
}
```

### Vendor B API Key
```hcl
resource "random_password" "vendor_b_api_key" {
  length  = 64
  special = false
}

resource "aws_secretsmanager_secret" "vendor_b" {
  name                    = "ota/api-keys/vendor-b"
  recovery_window_in_days = 7
}

resource "aws_secretsmanager_secret_version" "vendor_b" {
  secret_id = aws_secretsmanager_secret.vendor_b.id
  secret_string = jsonencode({
    api_key    = random_password.vendor_b_api_key.result
    created_at = timestamp()
    expires_at = timeadd(timestamp(), "2160h")  # 90일
  })
}
```

### 출력값
- rds_secret_arn
- rds_secret_name
- vendor_b_secret_arn

---

## 13. 루트 Main.tf

```hcl
terraform {
  required_version = ">= 1.5"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.5"
    }
  }

  # State 백엔드 (선택사항)
  # backend "s3" {
  #   bucket = "ota-terraform-state"
  #   key    = "ota/terraform.tfstate"
  #   region = "ap-northeast-2"
  # }
}

provider "aws" {
  region = var.region

  default_tags {
    tags = {
      Project     = "OTA-System"
      Environment = var.environment
      ManagedBy   = "Terraform"
    }
  }
}

# VPC 모듈
module "vpc" {
  source = "./modules/vpc"

  vpc_cidr             = var.vpc_cidr
  availability_zones   = var.availability_zones
  public_subnet_cidrs  = var.public_subnet_cidrs
  private_subnet_cidrs = var.private_subnet_cidrs
}

# Security Groups 모듈
module "security_groups" {
  source = "./modules/security-groups"

  vpc_id        = module.vpc.vpc_id
  admin_ssh_ip  = var.admin_ssh_ip
}

# IAM 모듈
module "iam" {
  source = "./modules/iam"

  firmware_bucket_name = module.s3.bucket_name
}

# S3 모듈
module "s3" {
  source = "./modules/s3"
}

# RDS 모듈
module "rds" {
  source = "./modules/rds"

  vpc_id             = module.vpc.vpc_id
  private_subnet_ids = module.vpc.private_subnet_ids
  sg_rds_id          = module.security_groups.sg_rds_id
}

# Secrets Manager 모듈
module "secrets_manager" {
  source = "./modules/secrets-manager"

  db_instance_address = module.rds.db_instance_address
  db_instance_port    = module.rds.db_instance_port
}

# SNS 모듈
module "sns" {
  source = "./modules/sns"

  email_address = var.alert_email
}

# ALB 모듈
module "alb" {
  source = "./modules/alb"

  vpc_id            = module.vpc.vpc_id
  public_subnet_ids = module.vpc.public_subnet_ids
  sg_alb_id         = module.security_groups.sg_alb_id
  certificate_arn   = var.certificate_arn  # 사전 생성 필요
}

# EC2 모듈
module "ec2" {
  source = "./modules/ec2"

  vpc_id                = module.vpc.vpc_id
  public_subnet_ids     = module.vpc.public_subnet_ids
  sg_ec2_id             = module.security_groups.sg_ec2_id
  instance_profile_name = module.iam.ec2_instance_profile_name
  target_group_arn      = module.alb.target_group_arn
  db_secret_name        = module.secrets_manager.rds_secret_name
  region                = var.region
}

# Lambda 모듈
module "lambda" {
  source = "./modules/lambda"

  vpc_id             = module.vpc.vpc_id
  private_subnet_ids = module.vpc.private_subnet_ids
  sg_lambda_id       = module.security_groups.sg_lambda_id
  lambda_role_arn    = module.iam.lambda_role_arn
  db_secret_name     = module.secrets_manager.rds_secret_name
  sns_topic_arn      = module.sns.alerts_topic_arn
  region             = var.region
}

# CloudWatch 모듈
module "cloudwatch" {
  source = "./modules/cloudwatch"

  asg_name                 = module.ec2.autoscaling_group_name
  alb_arn_suffix           = module.alb.alb_arn_suffix
  target_group_arn_suffix  = module.alb.target_group_arn_suffix
  db_instance_id           = module.rds.db_instance_id
  sns_topic_arn            = module.sns.alerts_topic_arn
}

# CloudTrail 모듈
module "cloudtrail" {
  source = "./modules/cloudtrail"

  firmware_bucket_arn    = module.s3.bucket_arn
  sns_security_topic_arn = module.sns.security_topic_arn
}
```

---

## 14. Variables.tf

```hcl
variable "region" {
  description = "AWS Region"
  type        = string
  default     = "ap-northeast-2"
}

variable "environment" {
  description = "Environment (development/production)"
  type        = string
  default     = "development"
}

variable "vpc_cidr" {
  description = "VPC CIDR block"
  type        = string
  default     = "10.0.0.0/16"
}

variable "availability_zones" {
  description = "Availability Zones"
  type        = list(string)
  default     = ["ap-northeast-2a", "ap-northeast-2b"]
}

variable "public_subnet_cidrs" {
  description = "Public subnet CIDR blocks"
  type        = list(string)
  default     = ["10.0.1.0/24", "10.0.2.0/24"]
}

variable "private_subnet_cidrs" {
  description = "Private subnet CIDR blocks"
  type        = list(string)
  default     = ["10.0.11.0/24", "10.0.12.0/24"]
}

variable "admin_ssh_ip" {
  description = "Admin SSH access IP (CIDR)"
  type        = string
  default     = "0.0.0.0/0"  # 실제 IP로 변경 필요
}

variable "alert_email" {
  description = "Alert email address"
  type        = string
  default     = "xogoon1325@gmail.com"
}

variable "certificate_arn" {
  description = "ACM Certificate ARN for ALB HTTPS"
  type        = string
  default     = ""  # ACM에서 사전 생성 필요
}
```

---

## 15. Outputs.tf

```hcl
output "alb_dns_name" {
  description = "ALB DNS Name"
  value       = module.alb.alb_dns_name
}

output "rds_endpoint" {
  description = "RDS Endpoint"
  value       = module.rds.db_instance_endpoint
}

output "s3_bucket_name" {
  description = "S3 Firmware Bucket Name"
  value       = module.s3.bucket_name
}

output "ec2_asg_name" {
  description = "EC2 Auto Scaling Group Name"
  value       = module.ec2.autoscaling_group_name
}

output "lambda_function_name" {
  description = "Lambda Function Name"
  value       = module.lambda.lambda_function_name
}

output "sns_alerts_topic_arn" {
  description = "SNS Alerts Topic ARN"
  value       = module.sns.alerts_topic_arn
}

output "rds_secret_name" {
  description = "RDS Credentials Secret Name"
  value       = module.secrets_manager.rds_secret_name
}
```

---

## 16. 배포 순서 및 명령어

### 사전 준비
```bash
# 1. AWS CLI 설정
aws configure

# 2. ACM 인증서 생성 (HTTPS용)
# AWS Console에서 수동 생성 또는:
aws acm request-certificate \
  --domain-name ota.yourdomain.com \
  --validation-method DNS \
  --region ap-northeast-2

# 3. Lambda Layer 빌드 (pymysql)
mkdir -p lambda-layer/python
pip install pymysql -t lambda-layer/python/
cd lambda-layer && zip -r ../pymysql-layer.zip . && cd ..
```

### Terraform 배포
```bash
# 1. 초기화
terraform init

# 2. 계획 확인
terraform plan

# 3. 배포 실행
terraform apply

# 4. 출력 확인
terraform output

# 5. State 확인
terraform show
```

### RDS 스키마 초기화
```bash
# RDS 엔드포인트 확인
RDS_ENDPOINT=$(terraform output -raw rds_endpoint)

# MySQL 클라이언트 설치 (필요시)
# sudo yum install -y mysql

# Secrets Manager에서 비밀번호 가져오기
DB_PASSWORD=$(aws secretsmanager get-secret-value \
  --secret-id ota/rds/credentials \
  --query SecretString --output text | jq -r .password)

# MySQL로 연결 (사용자가 직접 테이블 생성)
mysql -h $RDS_ENDPOINT -u admin -p$DB_PASSWORD ota_db
```

### SNS 구독 확인
```bash
# 이메일로 확인 링크가 전송됨
# xogoon1325@gmail.com에서 확인하여 구독 승인
```

### 테스트
```bash
# ALB DNS로 Health Check
ALB_DNS=$(terraform output -raw alb_dns_name)
curl http://$ALB_DNS/health

# CloudWatch Logs 확인
aws logs tail /aws/ec2/ota-server --follow

# RDS 접속 테스트
mysql -h $RDS_ENDPOINT -u admin -p$DB_PASSWORD ota_db -e "SHOW TABLES;"
```

---

## 17. 주의사항 및 베스트 프랙티스

### 보안
1. **Secrets 관리:**
   - `terraform.tfvars`는 `.gitignore`에 추가
   - RDS 비밀번호는 자동 생성
   - State 파일은 S3 백엔드 사용 (암호화)

2. **네트워크:**
   - `admin_ssh_ip`를 실제 관리자 IP로 제한
   - Security Group은 최소 권한 원칙

3. **IAM:**
   - Role은 최소 권한만 부여
   - Access Key는 사용하지 않음

### 비용 최적화
1. EC2는 t2.micro 사용
2. RDS는 db.t2.micro + Single-AZ
3. CloudWatch Logs 보관 14일
4. S3 Lifecycle로 오래된 버전 정리

### 운영
1. **State 백엔드:**
   - S3 + DynamoDB Lock 사용 권장
   - State 파일 버전 관리

2. **모듈화:**
   - 각 모듈은 독립적으로 테스트 가능
   - 의존성은 명확하게 정의

3. **태그:**
   - 모든 리소스에 일관된 태그 적용
   - 비용 추적 용이

### 트러블슈팅
1. **Lambda VPC 이슈:**
   - ENI 생성 권한 확인
   - Subnet에 충분한 IP 확보

2. **RDS 연결:**
   - Security Group 규칙 확인
   - Subnet Group 설정 확인

3. **ALB Health Check:**
   - `/health` 엔드포인트 구현 확인
   - Target Group 설정 확인

---

## 18. 예상 비용 (월간)

```
EC2 (t2.micro x 1):          $8.5
RDS (db.t2.micro):           $13
ALB:                         $26
S3 (50GB):                   $1.5
CloudWatch:                  $3
CloudTrail:                  $1
Lambda:                      $0 (프리티어)
SNS:                         $1
Secrets Manager:             $1
Data Transfer:               $5
━━━━━━━━━━━━━━━━━━━━━━━━━━━
총 예상:                     $60/월
```

---

## 19. 추가 작업 (배포 후)

1. **ACM 인증서:**
   - ALB HTTPS를 위해 필요
   - DNS 검증 완료 후 certificate_arn 업데이트

2. **도메인 설정:**
   - Route 53에 A 레코드 생성
   - ALB를 Alias Target으로 설정

3. **애플리케이션 배포:**
   - Docker 이미지 빌드
   - User Data 스크립트 업데이트

4. **모니터링 대시보드:**
   - CloudWatch Dashboard 커스터마이징
   - 추가 메트릭 설정

5. **백업 정책:**
   - RDS 스냅샷 스케줄 확인
   - S3 버전 관리 확인

---

## 완료 체크리스트

```
□ Terraform 프로젝트 구조 생성
□ 각 모듈별 main.tf, variables.tf, outputs.tf 작성
□ 루트 main.tf 작성
□ variables.tf, outputs.tf 작성
□ scripts/user-data.sh 작성
□ scripts/init-db.sql 작성
□ Lambda function 코드 작성
□ psycopg2 Layer 빌드
□ ACM 인증서 생성
□ terraform init 실행
□ terraform plan 확인
□ terraform apply 실행
□ RDS 스키마 초기화
□ SNS 이메일 구독 확인
□ ALB Health Check 테스트
□ CloudWatch Logs 확인
□ 비용 모니터링 설정
```
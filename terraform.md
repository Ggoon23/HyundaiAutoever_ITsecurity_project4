📋 1. VPC 모듈 
✅ VPC CIDR: 10.0.0.0/16
✅ DNS 지원 및 호스트네임 활성화
✅ Public Subnets: 10.0.1.0/24, 10.0.2.0/24 (2개 AZ)
✅ Private Subnets: 10.0.11.0/24, 10.0.12.0/24 (2개 AZ)
✅ Internet Gateway 연결
✅ Public/Private Route Table 분리
✅ S3 VPC Endpoint (Gateway) 생성 및 연결

📋 2. Security Groups 모듈 
✅ ALB SG: 80/443 포트 인바운드, VPC 내부로만 아웃바운드
✅ EC2 SG: ALB로부터만 80/443, SSH(22) 관리자 IP 제한
✅ RDS SG: EC2와 Lambda로부터만 5432 포트 허용
✅ Lambda SG: RDS로 5432, AWS API용 443 아웃바운드
✅ 순환 참조 방지를 위한 별도 규칙 정의

📋 3. IAM 모듈 
✅ EC2 Role: S3, Secrets Manager, CloudWatch Logs 권한
✅ Lambda Role: VPC Access, Secrets Manager, SNS, Logs 권한
✅ Trust Policy 및 Inline Policy 정확히 구현
✅ Instance Profile 생성

📋 4. S3 모듈 
✅ 버킷 이름: ota-firmware-bucket-{account_id}
✅ Versioning 활성화
✅ AES256 암호화
✅ Public Access Block 전체 차단
✅ Lifecycle: 90일 후 Glacier, 180일 후 삭제
✅ Bucket Policy: HTTPS 전송 강제
✅ Uptane 폴더 구조 생성 (image-repo, director-repo, certificates, public-keys)

📋 5. RDS 모듈 
✅ PostgreSQL 15.4, db.t3.micro
✅ 암호화 저장소 (storage_encrypted)
✅ Multi-AZ: false (비용 절감)
✅ 백업 보존: 3일
✅ CloudWatch Logs 내보내기
✅ Final Snapshot 생성
✅ 랜덤 비밀번호 자동 생성

📋 6. EC2 모듈 
✅ Amazon Linux 2023 AMI 사용
✅ t2.micro 인스턴스
✅ Launch Template + Auto Scaling Group
✅ Min: 1, Max: 2, Desired: 1
✅ ELB Health Check (300초 grace period)
✅ Target Tracking Policy (CPU 70%)
✅ User Data 스크립트 템플릿 적용

📋 7. ALB 모듈 
✅ Application Load Balancer (Public)
✅ Target Group: /health 경로로 헬스체크
✅ HTTP → HTTPS 리다이렉트 (301)
✅ HTTPS Listener (certificate_arn 조건부)
✅ Path-based routing: /firmware/, /metadata/, /vehicle/, /deploy/

📋 8. Lambda 모듈 
✅ Python 3.11 런타임
✅ VPC 내부 배치 (Private Subnet)
✅ Canary 배포 Phase 제어 로직
✅ 성공률 95% 기준 자동 Phase 전환
✅ 실패 시 SNS 알림 및 배포 중단
✅ EventBridge Rule: 5분 간격 실행
✅ Audit Log 기록

📋 9. CloudWatch 모듈 
✅ Log Groups: EC2, ALB, RDS, Lambda (14일 보존)
✅ EC2 Alarms: CPU 80%, Status Check
✅ ALB Alarms: Unhealthy Hosts, Response Time 2초, 5xx Errors
✅ RDS Alarms: CPU 80%, Storage 2GB, Connections 50
✅ CloudWatch Dashboard 생성

📋 10. CloudTrail 모듈 
✅ S3 버킷 자동 생성 및 정책 설정
✅ Log File Validation 활성화
✅ S3 데이터 이벤트 추적
✅ API Call Rate Insight
✅ EventBridge 규칙:
S3 의심 활동 (DeleteObject, DeleteBucket, PutBucketPolicy)
EC2 종료 시도 (TerminateInstances, StopInstances)
RDS 변경 (DeleteDBInstance, ModifyDBInstance)
✅ SNS 보안 토픽으로 알림

📋 11. SNS 모듈 
✅ ota-alerts 토픽 (일반 알림)
✅ ota-cloudtrail-alerts 토픽 (보안 알림)
✅ 이메일 구독: xogoon1325@gmail.com

📋 12. Secrets Manager 모듈 
✅ RDS 자격증명 저장 (ota/rds/credentials)
✅ Vendor B API Key 저장 (ota/api-keys/vendor-b)
✅ 7일 복구 기간
✅ JSON 형식으로 구조화된 시크릿

📋 13. Scripts 
✅ user-data.sh: Docker 설치, Secrets Manager 연동, nginx 컨테이너 실행, /health 엔드포인트
✅ init-db.sql: 모든 테이블 정의 (vehicles, firmware_metadata, deployment_history, canary_deployments, audit_logs 등 10개 테이블)
✅ 인덱스 생성

📋 14. Root Files
✅ main.tf: 모든 모듈 호출 및 의존성 올바르게 연결
✅ variables.tf: 모든 필요한 변수 정의 (기본값 포함)
✅ outputs.tf: ALB DNS, RDS Endpoint, S3 Bucket 등 주요 출력

배포 전 확인사항:
⚠️ admin_ssh_ip 변수를 실제 관리자 IP로 변경
⚠️ HTTPS 사용 시 ACM 인증서 생성 후 certificate_arn 설정
✅ 이메일 구독 확인 (SNS 토픽 구독 승인 필요)
배포전 확인사항에대해 더 자세히 말해줘
배포 전 확인사항에 대해 자세히 설명드리겠습니다.
🔧 배포 전 필수 확인사항
1. ⚠️ Admin SSH IP 설정 (보안 중요!)
현재 variables.tf에서 SSH 접근이 모든 IP(0.0.0.0/0)에 열려있습니다:
variable "admin_ssh_ip" {
  description = "Admin SSH access IP (CIDR)"
  type        = string
  default     = "0.0.0.0/0"  # ⚠️ 위험! 전세계에서 접근 가능
}
해결 방법:
본인 IP 확인 후 변경
# Windows에서 본인 공인 IP 확인
curl ifconfig.me
# 예: 123.456.789.012 라면
# variables.tf 수정:
admin_ssh_ip = "123.456.789.012/32"  # /32는 단일 IP를 의미

2. ⚠️ ACM 인증서 생성 (HTTPS 사용 시 필수)
현재 ALB는 HTTP만 지원하는 상태입니다. HTTPS를 사용하려면 ACM 인증서가 필요합니다.
방법 1: AWS Console에서 생성
AWS Console → Certificate Manager (ACM) 이동
"인증서 요청" 클릭
도메인 이름 입력 (예: ota.yourdomain.com)
DNS 검증 또는 이메일 검증 선택
생성 후 ARN 복사
방법 2: AWS CLI로 생성
# 인증서 요청
aws acm request-certificate \
  --domain-name ota.taehoon-project.com \
  --validation-method DNS \
  --region ap-northeast-2

# 출력 예시:
# {
#   "CertificateArn": "arn:aws:acm:ap-northeast-2:123456789012:certificate/abc-123-def"
# }
DNS 검증 완료 (Route 53 사용 시)
# 검증 레코드 확인
aws acm describe-certificate \
  --certificate-arn <YOUR_CERT_ARN> \
  --region ap-northeast-2

# Route 53에 CNAME 레코드 추가 (콘솔에서 확인된 값 입력)
terraform.tfvars에 추가
certificate_arn = "arn:aws:acm:ap-northeast-2:123456789012:certificate/abc-123-def"
참고: 인증서 없이도 배포 가능하지만, HTTPS Listener는 생성되지 않습니다.


3. 📧 SNS 이메일 구독 확인
배포 후 이메일 구독 승인이 필요합니다.
# 배포 후 이메일 확인
# 제목: "AWS Notification - Subscription Confirmation"
# 내용의 "Confirm subscription" 링크 클릭
주의: 링크를 클릭하지 않으면 알림을 받을 수 없습니다! 자동화 방법:
# 배포 후 구독 상태 확인
aws sns list-subscriptions-by-topic \
  --topic-arn $(terraform output -raw sns_alerts_topic_arn) \
  --region ap-northeast-2

# 출력에서 "PendingConfirmation" 상태 확인


# ALB 대신 EC2에 직접 접근 (개발/테스트용)
# - ALB 모듈 주석 처리
# - EC2를 Public Subnet에 배치하고 Public IP 할당
AWS 계정 한도 확인:
# VPC 한도 확인
aws service-quotas get-service-quota \
  --service-code vpc \
  --quota-code L-F678F1CE \
  --region ap-northeast-2

# EC2 인스턴스 한도 확인
aws service-quotas get-service-quota \
  --service-code ec2 \
  --quota-code L-1216C47A \
  --region ap-northeast-2


6. 🔐 Terraform State 백엔드 설정 (선택사항, 권장)
현재는 로컬에 state 파일이 저장됩니다. 팀 협업이나 안전한 관리를 위해 S3 백엔드 사용을 권장합니다.
S3 백엔드 버킷 생성:
# State 저장용 S3 버킷 생성
aws s3 mb s3://ota-terraform-state-$(aws sts get-caller-identity --query Account --output text) --region ap-northeast-2

# 버킷 버전 관리 활성화
aws s3api put-bucket-versioning \
  --bucket ota-terraform-state-$(aws sts get-caller-identity --query Account --output text) \
  --versioning-configuration Status=Enabled \
  --region ap-northeast-2

# 암호화 활성화
aws s3api put-bucket-encryption \
  --bucket ota-terraform-state-$(aws sts get-caller-identity --query Account --output text) \
  --server-side-encryption-configuration '{
    "Rules": [{
      "ApplyServerSideEncryptionByDefault": {
        "SSEAlgorithm": "AES256"
      }
    }]
  }' \
  --region ap-northeast-2
main.tf의 backend 블록 활성화:
# terraform/main.tf의 주석 제거
backend "s3" {
  bucket = "ota-terraform-state-<YOUR_ACCOUNT_ID>"
  key    = "ota/terraform.tfstate"
  region = "ap-northeast-2"
}



🚀 배포 순서
모든 확인이 끝났다면 다음 순서로 배포하세요:
# 1. Terraform 디렉토리로 이동
cd terraform

# 2. Terraform 초기화
terraform init

# 3. 배포 계획 확인
terraform plan

# 4. 변경사항 리뷰 후 배포
terraform apply

# 5. 출력값 확인
terraform output

# 6. ALB DNS로 Health Check 테스트
curl http://$(terraform output -raw alb_dns_name)/health

# 7. RDS 스키마 초기화 (엔드포인트 확인 후)
# Secrets Manager에서 비밀번호 가져오기
aws secretsmanager get-secret-value \
  --secret-id ota/rds/credentials \
  --region ap-northeast-2 \
  --query SecretString --output text | jq -r .password

# psql 설치 후 스키마 생성
psql -h $(terraform output -raw rds_endpoint | cut -d: -f1) \
     -U ota_admin \
     -d ota_db \
     -f scripts/init-db.sql

# 8. SNS 이메일 구독 승인 (이메일 확인)
⚠️ 문제 발생 시 디버깅
# CloudWatch Logs 확인
aws logs tail /aws/ec2/ota-server --follow --region ap-northeast-2

# RDS 연결 테스트
aws rds describe-db-instances \
  --db-instance-identifier ota-postgres \
  --region ap-northeast-2

# Security Group 규칙 확인
terraform state show module.security_groups.aws_security_group.ec2

# 리소스 삭제 (문제 발생 시)
terraform destroy
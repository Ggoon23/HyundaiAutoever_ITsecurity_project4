#!/bin/bash
set -e

# 로그 설정
exec > >(tee /var/log/user-data.log)
exec 2>&1

echo "Starting OTA Server initialization..."

# 패키지 업데이트
yum update -y

# Docker, MySQL 클라이언트 설치
yum install -y docker jq mysql

systemctl start docker
systemctl enable docker

# CloudWatch Agent 설치
wget https://s3.amazonaws.com/amazoncloudwatch-agent/amazon_linux/amd64/latest/amazon-cloudwatch-agent.rpm
rpm -U ./amazon-cloudwatch-agent.rpm

# Secrets Manager에서 DB 정보 가져오기
echo "Fetching database credentials from Secrets Manager..."
DB_SECRET=$(aws secretsmanager get-secret-value \
  --secret-id ${db_secret_name} \
  --region ${region} \
  --query SecretString --output text)

DB_HOST=$(echo $DB_SECRET | jq -r .host)
DB_PORT=$(echo $DB_SECRET | jq -r .port)
DB_NAME=$(echo $DB_SECRET | jq -r .dbname)
DB_USER=$(echo $DB_SECRET | jq -r .username)
DB_PASSWORD=$(echo $DB_SECRET | jq -r .password)

# init-db.sql 다운로드 (S3 또는 로컬)
echo "Creating init-db.sql..."
cat > /tmp/init-db.sql << 'EOF'
-- OTA Database Schema
-- MySQL 8.0
-- Character Set: UTF8MB4

-- 사용자가 직접 테이블을 생성할 예정입니다.
EOF

# RDS 연결 대기 (최대 10분)
echo "Waiting for RDS to be ready..."
MAX_ATTEMPTS=60
ATTEMPT=0
until mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASSWORD -e "SELECT 1" > /dev/null 2>&1; do
  ATTEMPT=$((ATTEMPT+1))
  if [ $ATTEMPT -ge $MAX_ATTEMPTS ]; then
    echo "ERROR: Could not connect to RDS after $MAX_ATTEMPTS attempts"
    break
  fi
  echo "Attempt $ATTEMPT/$MAX_ATTEMPTS: RDS not ready yet, waiting 10 seconds..."
  sleep 10
done

# init-db.sql 실행
if [ $ATTEMPT -lt $MAX_ATTEMPTS ]; then
  echo "Executing init-db.sql..."
  mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASSWORD $DB_NAME < /tmp/init-db.sql
  echo "Database initialization completed!"
else
  echo "Skipping database initialization due to connection timeout"
fi

# OTA 애플리케이션 컨테이너 실행 (임시 nginx)
docker run -d \
  -p 80:80 \
  --name ota-server \
  --restart unless-stopped \
  nginx:latest

# Health check 엔드포인트 생성
docker exec ota-server bash -c 'echo "OK" > /usr/share/nginx/html/health'

echo "OTA Server initialization completed!"

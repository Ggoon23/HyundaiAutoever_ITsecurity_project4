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

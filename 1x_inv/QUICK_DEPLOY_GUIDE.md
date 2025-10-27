# 1xINV 시스템 빠른 배포 가이드

## 📋 목차
1. [사전 준비](#사전-준비)
2. [한 번에 설치 (자동화)](#한-번에-설치-자동화)
3. [수동 배포 (단계별)](#수동-배포-단계별)
4. [설정 파일 수정](#설정-파일-수정)
5. [최종 확인](#최종-확인)

---

## 🎯 사전 준비

### 필요한 것
- ✅ Ubuntu 20.04 LTS 이상 서버
- ✅ Root 또는 sudo 권한
- ✅ 고정 IP 주소 (내부망)
- ✅ 프로젝트 파일 (`/1x_inv` 디렉토리)

### 서버 기본 정보 확인
```bash
# 호스트명 확인
hostname

# IP 주소 확인
ip addr show

# Ubuntu 버전 확인
lsb_release -a
```

---

## 🚀 한 번에 설치 (자동화)

### 1단계: 설치 스크립트 생성

```bash
# 설치 스크립트 생성
cat > /tmp/1xinv_install.sh << 'EOF'
#!/bin/bash
set -e

echo "=================================="
echo "1xINV 시스템 자동 설치 시작"
echo "=================================="

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 함수: 진행 상황 출력
print_status() {
    echo -e "${GREEN}[+]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

print_error() {
    echo -e "${RED}[X]${NC} $1"
}

# 1. 시스템 업데이트
print_status "시스템 업데이트 중..."
apt update && apt upgrade -y

# 2. 필수 패키지 설치
print_status "필수 패키지 설치 중..."
apt install -y git curl wget vim net-tools unzip

# 3. Apache 설치
print_status "Apache 웹서버 설치 중..."
apt install -y apache2
systemctl enable apache2
systemctl start apache2

# Apache 모듈 활성화
a2enmod rewrite headers deflate expires ssl
systemctl restart apache2

# 4. PHP 설치
print_status "PHP 설치 중..."
apt install -y php php-cli php-common php-json php-xml \
  php-mbstring php-curl php-gd php-zip php-intl \
  php-mysql php-imap php-ldap

# 5. MariaDB 설치
print_status "MariaDB 설치 중..."
apt install -y mariadb-server mariadb-client
systemctl enable mariadb
systemctl start mariadb

# 6. Composer 설치
print_status "Composer 설치 중..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 7. Postfix 설치 (메일 서버)
print_status "Postfix 설치 중..."
DEBIAN_FRONTEND=noninteractive apt install -y postfix
systemctl enable postfix
systemctl start postfix

# 8. Dovecot 설치 (IMAP)
print_status "Dovecot 설치 중..."
apt install -y dovecot-core dovecot-imapd
systemctl enable dovecot
systemctl start dovecot

# 9. 방화벽 설정
print_status "방화벽 설정 중..."
ufw --force enable
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
ufw allow 25/tcp   # SMTP
ufw allow 143/tcp  # IMAP
ufw allow 587/tcp  # SMTP Submission

# 10. 타임존 설정
print_status "타임존 설정 중..."
timedatectl set-timezone Asia/Seoul

# 11. 로케일 설정
print_status "로케일 설정 중..."
locale-gen ko_KR.UTF-8

print_status "기본 설치 완료!"
echo ""
echo "=================================="
echo "설치된 소프트웨어 버전 확인"
echo "=================================="
echo "Apache: $(apache2 -v | head -1)"
echo "PHP: $(php -v | head -1)"
echo "MariaDB: $(mysql --version)"
echo "Composer: $(composer --version)"
echo ""
print_warning "다음 단계: 데이터베이스 설정 및 파일 배포"
EOF

# 실행 권한 부여
chmod +x /tmp/1xinv_install.sh
```

### 2단계: 스크립트 실행

```bash
# 루트 권한으로 실행
sudo /tmp/1xinv_install.sh

# 또는 직접 실행
sudo bash /tmp/1xinv_install.sh
```

**예상 소요 시간: 5-10분**

---

## 📦 수동 배포 (단계별)

자동화 스크립트 대신 단계별로 설치하려면:

### STEP 1: 시스템 준비 (1분)

```bash
# 시스템 업데이트
sudo apt update
sudo apt upgrade -y

# 기본 도구 설치
sudo apt install -y git curl wget vim net-tools unzip

# 타임존 설정
sudo timedatectl set-timezone Asia/Seoul

# 로케일 설정
sudo locale-gen ko_KR.UTF-8
```

### STEP 2: 웹서버 설치 (2분)

```bash
# Apache 설치
sudo apt install -y apache2
sudo systemctl enable apache2
sudo systemctl start apache2

# Apache 모듈 활성화
sudo a2enmod rewrite headers deflate expires ssl
sudo systemctl restart apache2
```

### STEP 3: PHP 설치 (2분)

```bash
# PHP 및 확장 설치
sudo apt install -y php php-cli php-common php-json php-xml \
  php-mbstring php-curl php-gd php-zip php-intl \
  php-mysql php-imap php-ldap

# PHP 버전 확인
php -v
```

### STEP 4: 데이터베이스 설치 (2분)

```bash
# MariaDB 설치
sudo apt install -y mariadb-server mariadb-client
sudo systemctl enable mariadb
sudo systemctl start mariadb

# 보안 설정 (대화형)
sudo mysql_secure_installation
```

**mysql_secure_installation 답변:**
```
Set root password? Y → 강력한 비밀번호 입력
Remove anonymous users? Y
Disallow root login remotely? Y
Remove test database? Y
Reload privilege tables? Y
```

### STEP 5: 추가 소프트웨어 (3분)

```bash
# Composer 설치
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Postfix 설치 (메일 서버)
sudo DEBIAN_FRONTEND=noninteractive apt install -y postfix
sudo systemctl enable postfix
sudo systemctl start postfix

# Dovecot 설치 (IMAP)
sudo apt install -y dovecot-core dovecot-imapd
sudo systemctl enable dovecot
sudo systemctl start dovecot
```

### STEP 6: 방화벽 설정 (1분)

```bash
# UFW 활성화
sudo ufw --force enable

# 포트 오픈
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw allow 25/tcp   # SMTP
sudo ufw allow 143/tcp  # IMAP
sudo ufw allow 587/tcp  # SMTP Submission

# 상태 확인
sudo ufw status verbose
```

---

## 🗄️ 데이터베이스 설정

### 한 번에 DB 생성 스크립트

```bash
# DB 생성 스크립트
cat > /tmp/create_databases.sql << 'EOF'
-- Website 데이터베이스
CREATE DATABASE IF NOT EXISTS ota_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY 'Website@2025!';
GRANT ALL PRIVILEGES ON ota_db.* TO 'admin'@'localhost';

-- Webmail 데이터베이스
CREATE DATABASE IF NOT EXISTS roundcubemail CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'roundcube'@'localhost' IDENTIFIED BY 'Roundcube@2025!';
GRANT ALL PRIVILEGES ON roundcubemail.* TO 'roundcube'@'localhost';

-- 메일 서버 데이터베이스
CREATE DATABASE IF NOT EXISTS mail_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'mailuser'@'localhost' IDENTIFIED BY 'MailUser@2025!';
GRANT ALL PRIVILEGES ON mail_db.* TO 'mailuser'@'localhost';

FLUSH PRIVILEGES;
EOF

# 실행
sudo mysql -u root -p < /tmp/create_databases.sql

# 확인
sudo mysql -u root -p -e "SHOW DATABASES;"
```

**DB 비밀번호 메모하세요!**
```
admin: Website@2025!
roundcube: Roundcube@2025!
mailuser: MailUser@2025!
```

---

## 📂 파일 배포

### 한 번에 배포 스크립트

```bash
# 배포 스크립트 생성
cat > /tmp/deploy_files.sh << 'EOF'
#!/bin/bash
set -e

# 색상
GREEN='\033[0;32m'
NC='\033[0m'

echo -e "${GREEN}[+]${NC} 파일 배포 시작..."

# 프로젝트 경로 (수정 필요)
PROJECT_PATH="/path/to/1x_inv"

# 1. Website 배포
echo -e "${GREEN}[+]${NC} Website 배포 중..."
sudo cp -r $PROJECT_PATH/website /var/www/html/
sudo chown -R www-data:www-data /var/www/html/website
sudo chmod -R 755 /var/www/html/website
sudo chmod -R 777 /var/www/html/website/uploads

# 2. Webmail 배포
echo -e "${GREEN}[+]${NC} Webmail 배포 중..."
sudo cp -r $PROJECT_PATH/webmail /var/www/html/
sudo chown -R www-data:www-data /var/www/html/webmail
sudo chmod -R 755 /var/www/html/webmail
sudo chmod -R 777 /var/www/html/webmail/temp
sudo chmod -R 777 /var/www/html/webmail/logs

# 3. Composer 의존성 설치
echo -e "${GREEN}[+]${NC} Composer 의존성 설치 중..."
cd /var/www/html/webmail
sudo -u www-data composer install --no-dev --optimize-autoloader

# 4. DB 초기화
echo -e "${GREEN}[+]${NC} 데이터베이스 초기화 중..."
sudo mysql -u admin -p'Website@2025!' ota_db < /var/www/html/website/init-db.sql
sudo mysql -u roundcube -p'Roundcube@2025!' roundcubemail < /var/www/html/webmail/SQL/mysql.initial.sql
sudo mysql -u roundcube -p'Roundcube@2025!' roundcubemail < /var/www/html/webmail/REGISTRATION_SETUP.sql

echo -e "${GREEN}[+]${NC} 배포 완료!"
EOF

# 실행 권한
chmod +x /tmp/deploy_files.sh
```

**프로젝트 경로 수정 후 실행:**
```bash
# 스크립트 수정 (PROJECT_PATH 변경)
nano /tmp/deploy_files.sh

# 실행
sudo /tmp/deploy_files.sh
```

---

## ⚙️ 설정 파일 수정

### 필수 수정 사항 체크리스트

#### 1. Webmail 설정 (config.inc.php)

```bash
sudo nano /var/www/html/webmail/config/config.inc.php
```

**필수 수정:**
```php
// 1. 데이터베이스 비밀번호
$config['db_dsnw'] = 'mysql://roundcube:Roundcube@2025!@localhost/roundcubemail';

// 2. 암호화 키 생성 (24자)
// 생성 명령어: openssl rand -base64 24 | cut -c1-24
$config['des_key'] = 'GENERATED-KEY-HERE-24CH';

// 3. 메일 서버 주소
$config['imap_host'] = 'localhost:143';
$config['smtp_host'] = 'localhost:25';
```

**암호화 키 생성:**
```bash
openssl rand -base64 24 | cut -c1-24
# 출력 예: abc123XYZ789def456GHI
# 이 값을 config.inc.php의 des_key에 입력
```

#### 2. .htaccess IP 제한

```bash
sudo nano /var/www/html/webmail/.htaccess
```

**실제 IP 대역으로 수정:**
```apache
<RequireAll>
    # 실제 회사 네트워크 IP로 변경
    Require ip 192.168.1.0/24
    Require ip 10.0.0.0/8
    Require ip 127.0.0.1
</RequireAll>
```

#### 3. Apache 가상호스트 설정

```bash
# Website 가상호스트
cat > /tmp/1xinv-website.conf << 'EOF'
<VirtualHost *:80>
    ServerName 1xinv.local
    ServerAdmin admin@1xinv.com
    DocumentRoot /var/www/html/website

    <Directory /var/www/html/website>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/1xinv_website_error.log
    CustomLog ${APACHE_LOG_DIR}/1xinv_website_access.log combined
</VirtualHost>
EOF

# Webmail 가상호스트
cat > /tmp/1xinv-webmail.conf << 'EOF'
<VirtualHost *:80>
    ServerName webmail.1xinv.local
    ServerAdmin support@1xinv.com
    DocumentRoot /var/www/html/webmail

    <Directory /var/www/html/webmail>
        Options -Indexes +FollowSymLinks
        AllowOverride All

        <RequireAll>
            Require ip 192.168.0.0/16
            Require ip 10.0.0.0/8
            Require ip 127.0.0.1
        </RequireAll>
    </Directory>

    <Directory /var/www/html/webmail/installer>
        Require all denied
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/webmail_error.log
    CustomLog ${APACHE_LOG_DIR}/webmail_access.log combined

    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
EOF

# 가상호스트 파일 이동
sudo mv /tmp/1xinv-website.conf /etc/apache2/sites-available/
sudo mv /tmp/1xinv-webmail.conf /etc/apache2/sites-available/

# 사이트 활성화
sudo a2dissite 000-default.conf
sudo a2ensite 1xinv-website.conf
sudo a2ensite 1xinv-webmail.conf

# Apache 재시작
sudo apache2ctl configtest
sudo systemctl restart apache2
```

#### 4. /etc/hosts 수정

```bash
sudo nano /etc/hosts
```

**추가:**
```
127.0.0.1       localhost
192.168.1.100   1xinv-server 1xinv.local webmail.1xinv.local

# 192.168.1.100을 실제 서버 IP로 변경
```

---

## 🔐 보안 설정

### 중요 보안 조치

```bash
# 1. installer 디렉토리 삭제 (필수!)
sudo rm -rf /var/www/html/webmail/installer

# 2. 파일 권한 재확인
sudo chmod -R 755 /var/www/html/webmail
sudo chmod -R 777 /var/www/html/webmail/temp
sudo chmod -R 777 /var/www/html/webmail/logs

# 3. 민감 파일 권한 강화
sudo chmod 600 /var/www/html/webmail/config/config.inc.php

# 4. Apache 사용자 확인
ps aux | grep apache2
# www-data 확인

# 5. 방화벽 재확인
sudo ufw status
```

---

## ✅ 최종 확인

### 체크리스트

```bash
# 1. 서비스 상태 확인
sudo systemctl status apache2
sudo systemctl status mysql
sudo systemctl status postfix
sudo systemctl status dovecot

# 2. 포트 확인
sudo netstat -tulpn | grep -E ':(80|3306|25|143)'

# 3. 방화벽 확인
sudo ufw status

# 4. 데이터베이스 확인
sudo mysql -u root -p -e "SHOW DATABASES;"

# 5. 웹 접속 테스트
curl http://localhost/website
curl http://localhost/webmail

# 6. 로그 확인
sudo tail -20 /var/log/apache2/1xinv_website_error.log
sudo tail -20 /var/log/apache2/webmail_error.log
```

### 브라우저 테스트

```
Website:
http://1xinv.local
또는
http://SERVER_IP/website

Webmail:
http://webmail.1xinv.local
또는
http://SERVER_IP/webmail

회원가입:
http://webmail.1xinv.local/?_task=login&_action=plugin.user_registration
```

---

## 🎯 빠른 명령어 모음

### 원 라인 설치 (복사 후 붙여넣기)

```bash
# 전체 설치 (한 번에)
sudo apt update && sudo apt upgrade -y && \
sudo apt install -y apache2 php php-cli php-common php-json php-xml \
  php-mbstring php-curl php-gd php-zip php-intl php-mysql php-imap php-ldap \
  mariadb-server mariadb-client postfix dovecot-core dovecot-imapd \
  git curl wget vim net-tools unzip && \
sudo a2enmod rewrite headers deflate expires ssl && \
sudo systemctl enable apache2 mysql postfix dovecot && \
sudo systemctl restart apache2 && \
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer && \
sudo ufw --force enable && \
sudo ufw allow 22/tcp && sudo ufw allow 80/tcp && sudo ufw allow 443/tcp && \
sudo ufw allow 25/tcp && sudo ufw allow 143/tcp && sudo ufw allow 587/tcp && \
echo "설치 완료!"
```

### 서비스 재시작 (한 번에)

```bash
sudo systemctl restart apache2 mysql postfix dovecot
```

### 로그 확인 (한 번에)

```bash
sudo tail -f /var/log/apache2/webmail_error.log
```

---

## 📊 완료 후 확인 사항

### 1. 서비스 상태
```bash
✅ Apache: sudo systemctl status apache2
✅ MySQL: sudo systemctl status mysql
✅ Postfix: sudo systemctl status postfix
✅ Dovecot: sudo systemctl status dovecot
```

### 2. 접속 테스트
```bash
✅ Website: http://1xinv.local
✅ Webmail: http://webmail.1xinv.local
✅ 회원가입: 페이지 표시 확인
```

### 3. DB 테스트
```bash
✅ ota_db: inquiries 테이블 존재
✅ roundcubemail: users 테이블 존재
✅ roundcubemail: registration_pending 테이블 존재
```

---

## 🆘 문제 해결

### 자주 발생하는 오류

**1. Apache 시작 실패**
```bash
sudo apache2ctl configtest
sudo journalctl -xe
```

**2. DB 접속 실패**
```bash
sudo systemctl status mysql
sudo mysql -u root -p
```

**3. 권한 오류**
```bash
sudo chown -R www-data:www-data /var/www/html
```

**4. Composer 오류**
```bash
cd /var/www/html/webmail
sudo -u www-data composer install
```

---

## 📞 지원

문제 발생 시:
1. 로그 확인: `/var/log/apache2/`
2. 서비스 상태 확인: `sudo systemctl status SERVICE_NAME`
3. IT 헬프데스크: support@1xinv.com

---

**배포 소요 시간: 약 15-20분** ⏱️

# 1xINV 시스템 완전 배포 가이드

> **하나의 문서로 모든 것을 해결합니다**
> Website + Webmail + Mail Server 통합 배포

---

## 📋 목차

1. [시스템 개요](#시스템-개요)
2. [사전 준비](#사전-준비)
3. [빠른 설치 (자동화)](#빠른-설치-자동화)
4. [수동 설치 (단계별)](#수동-설치-단계별)
5. [데이터베이스 설정](#데이터베이스-설정)
6. [파일 배포](#파일-배포)
7. [메일 서버 설정](#메일-서버-설정)
8. [웹 서버 설정](#웹-서버-설정)
9. [보안 설정](#보안-설정)
10. [최종 확인](#최종-확인)
11. [계정 정보](#계정-정보)
12. [문제 해결](#문제-해결)

---

## 🎯 시스템 개요

### 구성 요소

```
1xINV 통합 시스템
├── Website (공식 홈페이지)
│   ├── 제품 소개
│   ├── 문의 폼
│   └── 공지사항
│
├── Webmail (사내 메일)
│   ├── 웹메일 인터페이스
│   ├── 회원가입 시스템
│   └── 승인 관리
│
└── Mail Server
    ├── Postfix (SMTP)
    └── Dovecot (IMAP)
```

### 시스템 요구사항

- **OS**: Ubuntu 20.04 LTS 이상
- **CPU**: 2 Core 이상
- **메모리**: 4GB RAM 이상
- **디스크**: 20GB 이상
- **네트워크**: 고정 IP (내부망)

---

## 🔧 사전 준비

### 1. 서버 정보 확인

```bash
# 호스트명 확인
hostname

# IP 주소 확인
ip addr show

# Ubuntu 버전 확인
lsb_release -a

# 디스크 공간 확인
df -h
```

### 2. 프로젝트 파일 준비

```bash
# Git에서 클론 (또는 파일 복사)
git clone https://github.com/your-repo/HyundaiAutoever_ITsecurity_project4.git
cd HyundaiAutoever_ITsecurity_project4/1x_inv

# 또는 파일 업로드
scp -r 1x_inv/ user@server:/tmp/
```

---

## 🚀 빠른 설치 (자동화)

### 전체 자동 설치 스크립트

```bash
# 설치 스크립트 생성
cat > /tmp/1xinv_full_install.sh << 'SCRIPT_EOF'
#!/bin/bash
set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() { echo -e "${GREEN}[✓]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[!]${NC} $1"; }

echo "========================================="
echo "1xINV 통합 시스템 자동 설치"
echo "========================================="
echo ""

# 1. 시스템 업데이트
print_status "시스템 업데이트 중..."
apt update && apt upgrade -y

# 2. 필수 패키지 설치
print_status "필수 패키지 설치 중..."
apt install -y git curl wget vim net-tools unzip software-properties-common

# 3. Apache 설치
print_status "Apache 웹서버 설치 중..."
apt install -y apache2
a2enmod rewrite headers deflate expires ssl
systemctl enable apache2
systemctl start apache2

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

# 7. 메일 서버 설치
print_status "Postfix/Dovecot 설치 중..."
DEBIAN_FRONTEND=noninteractive apt install -y postfix dovecot-core dovecot-imapd dovecot-mysql
systemctl enable postfix dovecot
systemctl start postfix dovecot

# 8. 방화벽 설정
print_status "방화벽 설정 중..."
ufw --force enable
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 25/tcp
ufw allow 143/tcp
ufw allow 587/tcp

# 9. 타임존 설정
print_status "타임존 설정 중..."
timedatectl set-timezone Asia/Seoul

# 10. 로케일 설정
print_status "로케일 설정 중..."
locale-gen ko_KR.UTF-8

print_status "기본 설치 완료!"
echo ""
print_warning "다음 단계:"
print_warning "1. MySQL 보안 설정: sudo mysql_secure_installation"
print_warning "2. 데이터베이스 생성"
print_warning "3. 파일 배포 및 설정"
SCRIPT_EOF

# 실행
chmod +x /tmp/1xinv_full_install.sh
sudo /tmp/1xinv_full_install.sh
```

**설치 시간: 약 10-15분**

---

## 📦 수동 설치 (단계별)

자동 설치 대신 단계별로 진행하려면 아래를 따르세요.

### STEP 1: 시스템 준비

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl wget vim net-tools unzip
sudo timedatectl set-timezone Asia/Seoul
sudo locale-gen ko_KR.UTF-8
```

### STEP 2: 웹서버 설치

```bash
sudo apt install -y apache2
sudo a2enmod rewrite headers deflate expires ssl
sudo systemctl enable apache2
sudo systemctl start apache2
```

### STEP 3: PHP 설치

```bash
sudo apt install -y php php-cli php-common php-json php-xml \
  php-mbstring php-curl php-gd php-zip php-intl \
  php-mysql php-imap php-ldap

# 버전 확인
php -v
```

### STEP 4: 데이터베이스 설치

```bash
sudo apt install -y mariadb-server mariadb-client
sudo systemctl enable mariadb
sudo systemctl start mariadb

# 보안 설정
sudo mysql_secure_installation
```

**mysql_secure_installation 설정:**
```
Set root password? Y → 강력한 비밀번호 입력
Remove anonymous users? Y
Disallow root login remotely? Y
Remove test database? Y
Reload privilege tables? Y
```

### STEP 5: 메일 서버 설치

```bash
# Postfix 설치
sudo DEBIAN_FRONTEND=noninteractive apt install -y postfix
sudo systemctl enable postfix
sudo systemctl start postfix

# Dovecot 설치
sudo apt install -y dovecot-core dovecot-imapd dovecot-mysql
sudo systemctl enable dovecot
sudo systemctl start dovecot
```

### STEP 6: Composer 설치

```bash
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
composer --version
```

### STEP 7: 방화벽 설정

```bash
sudo ufw --force enable
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw allow 25/tcp   # SMTP
sudo ufw allow 143/tcp  # IMAP
sudo ufw allow 587/tcp  # SMTP Submission
sudo ufw status verbose
```

---

## 🗄️ 데이터베이스 설정

### 한 번에 모든 DB 생성

```bash
cat > /tmp/create_all_databases.sql << 'SQL_EOF'
-- ================================================================
-- 1xINV 시스템 데이터베이스 통합 생성 스크립트
-- ================================================================

-- Website 데이터베이스
CREATE DATABASE IF NOT EXISTS ota_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'webadmin'@'localhost' IDENTIFIED BY '1xINV!web2025';
GRANT ALL PRIVILEGES ON ota_db.* TO 'webadmin'@'localhost';

-- Webmail 데이터베이스
CREATE DATABASE IF NOT EXISTS webmail_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'mailadmin'@'localhost' IDENTIFIED BY '1xINV!mail2025';
GRANT ALL PRIVILEGES ON webmail_db.* TO 'mailadmin'@'localhost';

-- Mail Server 데이터베이스
CREATE DATABASE IF NOT EXISTS mail_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'mailserver'@'localhost' IDENTIFIED BY '1xINV!smtp2025';
GRANT ALL PRIVILEGES ON mail_db.* TO 'mailserver'@'localhost';

FLUSH PRIVILEGES;

-- 확인
SHOW DATABASES;
SELECT User, Host FROM mysql.user WHERE User IN ('webadmin', 'mailadmin', 'mailserver');
SQL_EOF

# 실행
sudo mysql -u root -p < /tmp/create_all_databases.sql
```

### 개별 DB 생성 (수동)

```bash
sudo mysql -u root -p
```

```sql
-- Website DB
CREATE DATABASE ota_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'webadmin'@'localhost' IDENTIFIED BY '1xINV!web2025';
GRANT ALL PRIVILEGES ON ota_db.* TO 'webadmin'@'localhost';

-- Webmail DB
CREATE DATABASE webmail_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mailadmin'@'localhost' IDENTIFIED BY '1xINV!mail2025';
GRANT ALL PRIVILEGES ON webmail_db.* TO 'mailadmin'@'localhost';

-- Mail Server DB
CREATE DATABASE mail_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mailserver'@'localhost' IDENTIFIED BY '1xINV!smtp2025';
GRANT ALL PRIVILEGES ON mail_db.* TO 'mailserver'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

---

## 📂 파일 배포

### 자동 배포 스크립트

```bash
cat > /tmp/deploy_all_files.sh << 'DEPLOY_EOF'
#!/bin/bash
set -e

GREEN='\033[0;32m'
NC='\033[0m'

echo -e "${GREEN}[+]${NC} 파일 배포 시작..."

# 프로젝트 경로 (수정 필요!)
PROJECT_PATH="/path/to/1x_inv"

if [ ! -d "$PROJECT_PATH" ]; then
    echo "오류: $PROJECT_PATH 경로가 존재하지 않습니다!"
    echo "스크립트를 수정하여 PROJECT_PATH를 올바르게 설정하세요."
    exit 1
fi

# 1. Website 배포
echo -e "${GREEN}[+]${NC} Website 배포 중..."
cp -r $PROJECT_PATH/website /var/www/html/
chown -R www-data:www-data /var/www/html/website
chmod -R 755 /var/www/html/website
mkdir -p /var/www/html/website/uploads
chmod -R 777 /var/www/html/website/uploads

# 2. Webmail 배포
echo -e "${GREEN}[+]${NC} Webmail 배포 중..."
cp -r $PROJECT_PATH/webmail /var/www/html/
chown -R www-data:www-data /var/www/html/webmail
chmod -R 755 /var/www/html/webmail
chmod -R 777 /var/www/html/webmail/temp
chmod -R 777 /var/www/html/webmail/logs

# 3. Composer 의존성 설치
echo -e "${GREEN}[+]${NC} Composer 의존성 설치 중..."
cd /var/www/html/webmail
sudo -u www-data composer install --no-dev --optimize-autoloader

# 4. DB 초기화
echo -e "${GREEN}[+]${NC} 데이터베이스 초기화 중..."

# Website DB
mysql -u webadmin -p'1xINV!web2025' ota_db < /var/www/html/website/init-db.sql

# Webmail DB
mysql -u mailadmin -p'1xINV!mail2025' webmail_db < /var/www/html/webmail/SQL/mysql.initial.sql
mysql -u mailadmin -p'1xINV!mail2025' webmail_db < /var/www/html/webmail/REGISTRATION_SETUP.sql

# Mail Server DB
mysql -u mailserver -p'1xINV!smtp2025' mail_db < $PROJECT_PATH/MAIL_SERVER_SETUP.sql

echo -e "${GREEN}[+]${NC} 배포 완료!"
DEPLOY_EOF

chmod +x /tmp/deploy_all_files.sh
```

**실행 전에 PROJECT_PATH를 수정하세요!**

```bash
# 스크립트 수정
sudo nano /tmp/deploy_all_files.sh
# PROJECT_PATH="/path/to/1x_inv" → 실제 경로로 변경

# 실행
sudo /tmp/deploy_all_files.sh
```

---

## 📧 메일 서버 설정

### 1. Dovecot 설정

#### dovecot-sql.conf.ext 생성

```bash
sudo nano /etc/dovecot/dovecot-sql.conf.ext
```

**내용:**
```conf
driver = mysql
connect = host=localhost dbname=mail_db user=mailserver password=1xINV!smtp2025

default_pass_scheme = PLAIN

password_query = SELECT email as user, password FROM virtual_users WHERE email='%u' AND active=1

user_query = SELECT email as user, 'maildir:/var/mail/vhosts/%d/%n' as mail, 5000 AS uid, 5000 AS gid FROM virtual_users WHERE email='%u' AND active=1
```

#### 10-auth.conf 수정

```bash
sudo nano /etc/dovecot/conf.d/10-auth.conf
```

**변경:**
```conf
# 이 라인 주석 해제
!include auth-sql.conf.ext

# 이 라인 주석 처리
#!include auth-system.conf.ext
```

#### 10-mail.conf 수정

```bash
sudo nano /etc/dovecot/conf.d/10-mail.conf
```

**변경:**
```conf
mail_location = maildir:/var/mail/vhosts/%d/%n
mail_privileged_group = mail
```

#### 10-master.conf 수정 (Postfix 연동)

```bash
sudo nano /etc/dovecot/conf.d/10-master.conf
```

**service auth 섹션 수정:**
```conf
service auth {
  unix_listener /var/spool/postfix/private/auth {
    mode = 0666
    user = postfix
    group = postfix
  }

  unix_listener auth-userdb {
    mode = 0600
    user = vmail
  }
}
```

#### 메일 디렉토리 생성

```bash
sudo mkdir -p /var/mail/vhosts/1xinv.com
sudo groupadd -g 5000 vmail
sudo useradd -g vmail -u 5000 vmail -d /var/mail -s /usr/sbin/nologin
sudo chown -R vmail:vmail /var/mail
```

### 2. Postfix 설정

#### main.cf 수정

```bash
sudo nano /etc/postfix/main.cf
```

**추가:**
```conf
# 호스트명 설정
myhostname = mail.1xinv.com
mydomain = 1xinv.com
myorigin = $mydomain

# 네트워크 설정
inet_interfaces = all
inet_protocols = ipv4

# Virtual mailbox 설정
virtual_mailbox_domains = 1xinv.com
virtual_mailbox_base = /var/mail/vhosts
virtual_mailbox_maps = mysql:/etc/postfix/mysql-virtual-mailbox-maps.cf
virtual_minimum_uid = 5000
virtual_uid_maps = static:5000
virtual_gid_maps = static:5000

# Dovecot SASL 인증
smtpd_sasl_type = dovecot
smtpd_sasl_path = private/auth
smtpd_sasl_auth_enable = yes
broken_sasl_auth_clients = yes

# TLS 설정 (선택)
# smtpd_tls_cert_file=/etc/ssl/certs/ssl-cert-snakeoil.pem
# smtpd_tls_key_file=/etc/ssl/private/ssl-cert-snakeoil.key
# smtpd_use_tls=yes
```

#### MySQL 맵 파일 생성

```bash
cat > /tmp/mysql-virtual-mailbox-maps.cf << 'MAP_EOF'
user = mailserver
password = 1xINV!smtp2025
hosts = localhost
dbname = mail_db
query = SELECT CONCAT(email, '/') FROM virtual_users WHERE email='%s' AND active=1
MAP_EOF

sudo mv /tmp/mysql-virtual-mailbox-maps.cf /etc/postfix/
sudo chmod 640 /etc/postfix/mysql-virtual-mailbox-maps.cf
sudo chown root:postfix /etc/postfix/mysql-virtual-mailbox-maps.cf
```

### 3. 서비스 재시작

```bash
sudo systemctl restart dovecot
sudo systemctl restart postfix

# 상태 확인
sudo systemctl status dovecot
sudo systemctl status postfix
```

---

## 🌐 웹 서버 설정

### 1. Webmail 설정 파일 수정

```bash
sudo nano /var/www/html/webmail/config/config.inc.php
```

**필수 수정:**
```php
// 1. 데이터베이스 비밀번호
$config['db_dsnw'] = 'mysql://mailadmin:1xINV!mail2025@localhost/webmail_db';

// 2. 암호화 키 생성 (24자)
// 생성: openssl rand -base64 24 | cut -c1-24
$config['des_key'] = 'GENERATED-KEY-HERE-24CH';

// 3. 메일 서버 주소
$config['imap_host'] = 'localhost:143';
$config['smtp_host'] = 'localhost:25';

// 4. 제품 이름
$config['product_name'] = '1xINV 사내 웹메일';
```

**암호화 키 생성:**
```bash
openssl rand -base64 24 | cut -c1-24
# 출력된 값을 config.inc.php의 des_key에 입력
```

### 2. IP 제한 설정

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

### 3. Apache 가상호스트 설정

#### Website 가상호스트

```bash
cat > /tmp/1xinv-website.conf << 'WEBSITE_EOF'
<VirtualHost *:80>
    ServerName 1xinv.local
    ServerAlias www.1xinv.local
    ServerAdmin webadmin@1xinv.com
    DocumentRoot /var/www/html/website

    <Directory /var/www/html/website>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/1xinv_website_error.log
    CustomLog ${APACHE_LOG_DIR}/1xinv_website_access.log combined
</VirtualHost>
WEBSITE_EOF

sudo mv /tmp/1xinv-website.conf /etc/apache2/sites-available/
```

#### Webmail 가상호스트

```bash
cat > /tmp/1xinv-webmail.conf << 'WEBMAIL_EOF'
<VirtualHost *:80>
    ServerName webmail.1xinv.local
    ServerAdmin mailadmin@1xinv.com
    DocumentRoot /var/www/html/webmail

    <Directory /var/www/html/webmail>
        Options -Indexes +FollowSymLinks
        AllowOverride All

        # IP 제한 (실제 네트워크로 변경)
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

    # 보안 헤더
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
WEBMAIL_EOF

sudo mv /tmp/1xinv-webmail.conf /etc/apache2/sites-available/
```

#### 사이트 활성화

```bash
# 기본 사이트 비활성화
sudo a2dissite 000-default.conf

# 새 사이트 활성화
sudo a2ensite 1xinv-website.conf
sudo a2ensite 1xinv-webmail.conf

# 설정 테스트
sudo apache2ctl configtest

# Apache 재시작
sudo systemctl restart apache2
```

### 4. /etc/hosts 수정

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

### 필수 보안 조치

```bash
# 1. installer 디렉토리 삭제 (매우 중요!)
sudo rm -rf /var/www/html/webmail/installer

# 2. 파일 권한 재확인
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html/website
sudo chmod -R 755 /var/www/html/webmail
sudo chmod -R 777 /var/www/html/website/uploads
sudo chmod -R 777 /var/www/html/webmail/temp
sudo chmod -R 777 /var/www/html/webmail/logs

# 3. 설정 파일 권한 강화
sudo chmod 600 /var/www/html/webmail/config/config.inc.php
sudo chmod 640 /etc/postfix/mysql-virtual-mailbox-maps.cf
sudo chmod 600 /etc/dovecot/dovecot-sql.conf.ext

# 4. 민감 정보 파일 보호
sudo chmod 600 /path/to/ACCOUNTS.txt  # 실제 경로로 변경

# 5. 방화벽 상태 확인
sudo ufw status verbose

# 6. SELinux/AppArmor 확인 (있는 경우)
# sudo aa-status
```

### 추가 보안 권장사항

```bash
# 1. 자동 업데이트 활성화
sudo apt install unattended-upgrades
sudo dpkg-reconfigure --priority=low unattended-upgrades

# 2. Fail2Ban 설치 (무차별 대입 공격 방지)
sudo apt install fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# 3. 로그 모니터링 설정
sudo tail -f /var/log/apache2/webmail_error.log
sudo tail -f /var/log/mail.log
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

# 3. 데이터베이스 확인
sudo mysql -u root -p -e "SHOW DATABASES;"
sudo mysql -u webadmin -p'1xINV!web2025' ota_db -e "SHOW TABLES;"
sudo mysql -u mailadmin -p'1xINV!mail2025' webmail_db -e "SHOW TABLES;"
sudo mysql -u mailserver -p'1xINV!smtp2025' mail_db -e "SELECT COUNT(*) FROM virtual_users;"

# 4. 웹 접속 테스트
curl -I http://localhost/website
curl -I http://localhost/webmail

# 5. 로그 확인
sudo tail -20 /var/log/apache2/1xinv_website_error.log
sudo tail -20 /var/log/apache2/webmail_error.log
sudo tail -20 /var/log/mail.log
```

### 브라우저 테스트

**Website:**
```
http://1xinv.local
또는
http://SERVER_IP/website
```

**Webmail:**
```
http://webmail.1xinv.local
또는
http://SERVER_IP/webmail
```

**로그인 테스트:**
```
이메일: devkim99@1xinv.com
비밀번호: codingK1m!dev
```

**회원가입 페이지:**
```
http://webmail.1xinv.local/?_task=login&_action=plugin.user_registration
```

### IMAP/SMTP 테스트

```bash
# IMAP 테스트
telnet localhost 143

# 입력:
a1 LOGIN devkim99@1xinv.com codingK1m!dev
a2 LIST "" "*"
a3 LOGOUT

# 메일 로그 확인
sudo tail -f /var/log/mail.log
```

---

## 🔑 계정 정보

**상세 정보는 `ACCOUNTS.txt` 파일 참조**

### 데이터베이스 계정
```
webadmin / 1xINV!web2025    → ota_db
mailadmin / 1xINV!mail2025  → webmail_db
mailserver / 1xINV!smtp2025 → mail_db
```

### 웹메일 계정 (10개)
```
1. ceo@1xinv.com             / Leader!2025#boss
2. devkim99@1xinv.com        / codingK1m!dev
3. junhyuk2@1xinv.com        / jun2Park@dev
4. minji0developer@1xinv.com / minDev0!Choi
5. sunny88@1xinv.com         / saleS88lee!
6. daeun77@1xinv.com         / jeongDE77$
7. hrmanager25@1xinv.com     / hrKang25!boss
8. finance01@1xinv.com       / money01Yoon$
9. sohee93@1xinv.com         / han93Finance#
10. support@1xinv.com        / help1xinv!2025
```

---

## 🆘 문제 해결

### Apache 시작 실패

```bash
# 설정 테스트
sudo apache2ctl configtest

# 자세한 로그 확인
sudo journalctl -xe

# 포트 충돌 확인
sudo netstat -tulpn | grep :80
```

### DB 접속 실패

```bash
# MySQL 상태 확인
sudo systemctl status mysql

# 수동 접속 테스트
sudo mysql -u webadmin -p'1xINV!web2025' ota_db

# 권한 확인
sudo mysql -u root -p -e "SELECT User, Host FROM mysql.user;"
```

### 권한 오류

```bash
# 전체 권한 재설정
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo chmod -R 777 /var/www/html/website/uploads
sudo chmod -R 777 /var/www/html/webmail/temp
sudo chmod -R 777 /var/www/html/webmail/logs
```

### Composer 오류

```bash
cd /var/www/html/webmail
sudo -u www-data composer install --no-dev
sudo -u www-data composer dump-autoload
```

### 메일 서버 오류

```bash
# Dovecot 설정 테스트
sudo doveconf -n

# Postfix 설정 테스트
sudo postconf -n

# 로그 확인
sudo tail -f /var/log/mail.log
sudo tail -f /var/log/syslog

# 서비스 재시작
sudo systemctl restart dovecot
sudo systemctl restart postfix
```

### 방화벽 문제

```bash
# UFW 상태 확인
sudo ufw status verbose

# 필요한 포트 다시 열기
sudo ufw allow 80/tcp
sudo ufw allow 143/tcp
sudo ufw allow 25/tcp

# 방화벽 재시작
sudo ufw reload
```

---

## 🎯 빠른 명령어 모음

### 모든 서비스 재시작

```bash
sudo systemctl restart apache2 mysql postfix dovecot
```

### 모든 로그 확인

```bash
# 웹 로그
sudo tail -f /var/log/apache2/webmail_error.log

# 메일 로그
sudo tail -f /var/log/mail.log

# 시스템 로그
sudo tail -f /var/log/syslog
```

### 권한 일괄 수정

```bash
sudo chown -R www-data:www-data /var/www/html
sudo chown -R vmail:vmail /var/mail
```

---

## 📞 지원

문제 발생 시:
1. 로그 파일 확인: `/var/log/`
2. 서비스 상태 확인: `sudo systemctl status SERVICE_NAME`
3. 설정 파일 검토: `config.inc.php`, `main.cf`, `dovecot.conf`
4. IT 헬프데스크: support@1xinv.com

---

**배포 완료 소요 시간: 약 30-40분** ⏱️

**작성일**: 2025-01-27
**버전**: 1.0
**담당**: 1xINV IT팀

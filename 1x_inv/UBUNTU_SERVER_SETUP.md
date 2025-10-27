# 1xINV 통합 서버 구축 가이드 (Ubuntu)

## 📋 목차
1. [시스템 개요](#시스템-개요)
2. [서버 요구사항](#서버-요구사항)
3. [전체 아키텍처](#전체-아키텍처)
4. [Ubuntu 서버 초기 설정](#ubuntu-서버-초기-설정)
5. [Website 배포](#website-배포)
6. [Webmail 배포](#webmail-배포)
7. [메일 서버 구축](#메일-서버-구축)
8. [통합 테스트](#통합-테스트)
9. [보안 강화](#보안-강화)
10. [문제 해결](#문제-해결)

---

## 🎯 시스템 개요

이 가이드는 **1xINV** 회사의 다음 두 시스템을 단일 Ubuntu 서버에 배포합니다:

### 배포 시스템
1. **공식 웹사이트** (`/var/www/html/website`)
   - 회사 소개, 제품, 공지사항, 문의 기능
   - PHP + MySQL
   - 포트: 80 (HTTP)

2. **사내 웹메일** (`/var/www/html/webmail`)
   - Roundcube 기반 내부 메일 시스템
   - IMAP/SMTP 연동
   - 내부망 전용 (IP 제한)

---

## 💻 서버 요구사항

### 최소 사양
- **OS**: Ubuntu 20.04 LTS 이상 (권장: 22.04 LTS)
- **CPU**: 2 Core 이상
- **RAM**: 4GB 이상 (권장: 8GB)
- **디스크**: 50GB 이상
- **네트워크**: 고정 IP (내부망)

### 필수 소프트웨어
```
Apache 2.4+
PHP 7.4+ (or 8.0+)
MySQL 8.0+ / MariaDB 10.5+
Postfix (SMTP)
Dovecot (IMAP)
Composer
```

---

## 🏗️ 전체 아키텍처

```
┌─────────────────────────────────────────────────────┐
│              Ubuntu Server (192.168.x.x)             │
├─────────────────────────────────────────────────────┤
│                                                       │
│  ┌─────────────────┐      ┌──────────────────┐      │
│  │  Apache :80     │      │  Postfix :25     │      │
│  │  ├─ /website    │      │  (SMTP Server)   │      │
│  │  └─ /webmail    │      └──────────────────┘      │
│  └─────────────────┘               │                 │
│          │                         │                 │
│          ▼                         ▼                 │
│  ┌─────────────────┐      ┌──────────────────┐      │
│  │  MySQL :3306    │      │  Dovecot :143    │      │
│  │  ├─ ota_db      │      │  (IMAP Server)   │      │
│  │  ├─ roundcube   │      └──────────────────┘      │
│  │  └─ mail_db     │                                 │
│  └─────────────────┘                                 │
│                                                       │
└─────────────────────────────────────────────────────┘

접속 주소:
- 웹사이트: http://1xinv.local  or  http://SERVER_IP/website
- 웹메일:   http://webmail.1xinv.local  or  http://SERVER_IP/webmail
```

---

## 🚀 Ubuntu 서버 초기 설정

### 1단계: 시스템 업데이트
```bash
# 패키지 목록 업데이트
sudo apt update
sudo apt upgrade -y

# 시스템 재부팅 (필요시)
sudo reboot
```

### 2단계: 방화벽 설정
```bash
# UFW 방화벽 활성화
sudo ufw enable

# 필수 포트 오픈
sudo ufw allow 22/tcp      # SSH
sudo ufw allow 80/tcp      # HTTP
sudo ufw allow 443/tcp     # HTTPS (향후 사용)
sudo ufw allow 25/tcp      # SMTP
sudo ufw allow 143/tcp     # IMAP
sudo ufw allow 587/tcp     # SMTP Submission

# 상태 확인
sudo ufw status verbose
```

### 3단계: 호스트명 설정
```bash
# 호스트명 변경
sudo hostnamectl set-hostname 1xinv-server

# /etc/hosts 수정
sudo nano /etc/hosts
```

```
127.0.0.1       localhost
192.168.1.100   1xinv-server 1xinv.local webmail.1xinv.local

# IPv6 비활성화 (선택사항)
# ::1     localhost ip6-localhost ip6-loopback
```

### 4단계: 필수 패키지 설치
```bash
# 기본 도구
sudo apt install -y git curl wget vim net-tools

# 타임존 설정
sudo timedatectl set-timezone Asia/Seoul

# 로케일 설정
sudo locale-gen ko_KR.UTF-8
```

---

## 🌐 Website 배포

### 1단계: LAMP 스택 설치

#### Apache 설치
```bash
sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2

# Apache 모듈 활성화
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl
sudo systemctl restart apache2
```

#### PHP 설치
```bash
# PHP 8.1 및 확장 설치
sudo apt install php php-cli php-common php-mysql php-xml \
  php-mbstring php-curl php-gd php-zip php-json php-intl -y

# PHP 버전 확인
php -v
```

#### MySQL 설치
```bash
# MariaDB 설치 (MySQL 호환)
sudo apt install mariadb-server mariadb-client -y
sudo systemctl enable mariadb
sudo systemctl start mariadb

# 보안 설정
sudo mysql_secure_installation
```

**mysql_secure_installation 설정:**
```
- Set root password? Y → 강력한 비밀번호 설정
- Remove anonymous users? Y
- Disallow root login remotely? Y
- Remove test database? Y
- Reload privilege tables? Y
```

### 2단계: Website 데이터베이스 생성
```bash
sudo mysql -u root -p
```

```sql
-- Website용 데이터베이스
CREATE DATABASE ota_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 사용자 생성 및 권한 부여
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON ota_db.* TO 'admin'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

### 3단계: Website 파일 배포
```bash
# 웹 루트 디렉토리로 이동
cd /var/www/html

# 기존 파일 백업/삭제
sudo mv index.html index.html.bak

# Git에서 프로젝트 클론 (또는 파일 복사)
# 방법 1: Git 사용
sudo git clone https://github.com/YOUR_REPO/HyundaiAutoever_ITsecurity_project4.git
sudo mv HyundaiAutoever_ITsecurity_project4/1x_inv/website ./website

# 방법 2: 직접 복사 (로컬에서 scp)
# 로컬 PC에서: scp -r /path/to/1x_inv/website user@SERVER_IP:/tmp/
# 서버에서: sudo mv /tmp/website /var/www/html/

# 소유권 변경
sudo chown -R www-data:www-data /var/www/html/website
sudo chmod -R 755 /var/www/html/website

# uploads 디렉토리 쓰기 권한
sudo chmod -R 777 /var/www/html/website/uploads
```

### 4단계: Website DB 초기화
```bash
# init-db.sql 실행
sudo mysql -u admin -p ota_db < /var/www/html/website/init-db.sql

# 정상 생성 확인
sudo mysql -u admin -p ota_db -e "SHOW TABLES;"
```

### 5단계: Apache 가상호스트 설정
```bash
sudo nano /etc/apache2/sites-available/1xinv-website.conf
```

```apache
<VirtualHost *:80>
    ServerName 1xinv.local
    ServerAlias www.1xinv.local
    ServerAdmin admin@1xinv.com

    DocumentRoot /var/www/html/website

    <Directory /var/www/html/website>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # PHP 설정
        php_value upload_max_filesize 10M
        php_value post_max_size 10M
    </Directory>

    # uploads 디렉토리
    <Directory /var/www/html/website/uploads>
        Options -Indexes
        AllowOverride None
        Require all granted
    </Directory>

    # 로그
    ErrorLog ${APACHE_LOG_DIR}/1xinv_website_error.log
    CustomLog ${APACHE_LOG_DIR}/1xinv_website_access.log combined
</VirtualHost>
```

### 6단계: Website 활성화
```bash
# 사이트 활성화
sudo a2ensite 1xinv-website.conf

# Apache 설정 테스트
sudo apache2ctl configtest

# Apache 재시작
sudo systemctl restart apache2
```

### 7단계: Website 테스트
```bash
# 로컬 브라우저에서 접속
http://1xinv.local
또는
http://SERVER_IP/website
```

---

## 📧 Webmail 배포

### 1단계: 추가 PHP 확장 설치
```bash
# Webmail 전용 PHP 확장
sudo apt install php-imap php-ldap -y
sudo systemctl restart apache2
```

### 2단계: Webmail 파일 배포
```bash
cd /var/www/html

# Webmail 디렉토리 복사
# 방법 1: Git
sudo cp -r HyundaiAutoever_ITsecurity_project4/1x_inv/webmail ./webmail

# 방법 2: 직접 복사
# scp -r /path/to/1x_inv/webmail user@SERVER_IP:/tmp/
# sudo mv /tmp/webmail /var/www/html/

# 소유권 및 권한 설정
sudo chown -R www-data:www-data /var/www/html/webmail
sudo chmod -R 755 /var/www/html/webmail
sudo chmod -R 777 /var/www/html/webmail/temp
sudo chmod -R 777 /var/www/html/webmail/logs
```

### 3단계: Composer 의존성 설치
```bash
# Composer 설치 (미설치시)
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Webmail 의존성 설치
cd /var/www/html/webmail
sudo -u www-data composer install --no-dev --optimize-autoloader
```

### 4단계: Roundcube 데이터베이스 생성
```bash
sudo mysql -u root -p
```

```sql
-- Roundcube 데이터베이스
CREATE DATABASE roundcubemail CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 사용자 생성
CREATE USER 'roundcube'@'localhost' IDENTIFIED BY 'roundcube_strong_password';
GRANT ALL PRIVILEGES ON roundcubemail.* TO 'roundcube'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

### 5단계: Roundcube DB 초기화
```bash
sudo mysql -u roundcube -p roundcubemail < /var/www/html/webmail/SQL/mysql.initial.sql

# 테이블 생성 확인
sudo mysql -u roundcube -p roundcubemail -e "SHOW TABLES;"
```

### 6단계: Webmail 설정 파일 수정
```bash
sudo nano /var/www/html/webmail/config/config.inc.php
```

**필수 수정 사항:**
```php
// 1. 데이터베이스 비밀번호
$config['db_dsnw'] = 'mysql://roundcube:roundcube_strong_password@localhost/roundcubemail';

// 2. 암호화 키 생성 (24자)
// 아래 명령으로 생성: openssl rand -base64 24 | cut -c1-24
$config['des_key'] = 'YOUR-GENERATED-24CHAR-KEY!';

// 3. 메일 서버 주소 (나중에 설정)
$config['imap_host'] = 'localhost:143';
$config['smtp_host'] = 'localhost:25';
```

**암호화 키 생성:**
```bash
openssl rand -base64 24 | cut -c1-24
# 출력된 키를 config.inc.php의 des_key에 입력
```

### 7단계: .htaccess IP 제한 수정
```bash
sudo nano /var/www/html/webmail/.htaccess
```

**실제 회사 IP 대역으로 수정:**
```apache
<RequireAll>
    # 실제 내부망 IP 대역으로 변경
    Require ip 192.168.1.0/24
    Require ip 10.0.0.0/8
    Require ip 127.0.0.1
</RequireAll>
```

### 8단계: Apache 가상호스트 설정
```bash
sudo nano /etc/apache2/sites-available/1xinv-webmail.conf
```

```apache
<VirtualHost *:80>
    ServerName webmail.1xinv.local
    ServerAdmin support@1xinv.com

    DocumentRoot /var/www/html/webmail

    <Directory /var/www/html/webmail>
        Options -Indexes +FollowSymLinks
        AllowOverride All

        # 내부망 IP 제한
        <RequireAll>
            Require ip 192.168.0.0/16
            Require ip 10.0.0.0/8
            Require ip 127.0.0.1
        </RequireAll>
    </Directory>

    # installer 차단
    <Directory /var/www/html/webmail/installer>
        Require all denied
    </Directory>

    # 민감한 디렉토리 차단
    <DirectoryMatch "^/var/www/html/webmail/(config|temp|logs|bin|SQL)">
        Require all denied
    </DirectoryMatch>

    ErrorLog ${APACHE_LOG_DIR}/webmail_error.log
    CustomLog ${APACHE_LOG_DIR}/webmail_access.log combined

    # 보안 헤더
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

### 9단계: Webmail 활성화
```bash
# 사이트 활성화
sudo a2ensite 1xinv-webmail.conf

# Apache 재시작
sudo systemctl restart apache2

# installer 디렉토리 삭제 (설정 완료 후!)
sudo rm -rf /var/www/html/webmail/installer
```

---

## 📬 메일 서버 구축 (Postfix + Dovecot)

### 1단계: Postfix 설치 (SMTP)
```bash
sudo apt install postfix -y
```

**설치 중 설정:**
- General type: **Internet Site**
- System mail name: **1xinv.com**

### 2단계: Dovecot 설치 (IMAP)
```bash
sudo apt install dovecot-core dovecot-imapd -y
```

### 3단계: 메일 데이터베이스 생성
```bash
sudo mysql -u root -p
```

```sql
-- 메일 사용자 데이터베이스
CREATE DATABASE mail_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 사용자 생성
CREATE USER 'mailuser'@'localhost' IDENTIFIED BY 'mail_password';
GRANT ALL PRIVILEGES ON mail_db.* TO 'mailuser'@'localhost';

FLUSH PRIVILEGES;

USE mail_db;

-- 가상 사용자 테이블
CREATE TABLE virtual_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    active TINYINT(1) DEFAULT 1,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10개 테스트 계정 추가 (비밀번호는 해시 필요!)
-- 임시로 평문 삽입 (나중에 해시로 변경)
INSERT INTO virtual_users (email, password, name, department) VALUES
('ceo@1xinv.com', 'ceo2025admin', '대표이사', '경영진'),
('kim.chulsu@1xinv.com', 'kimcs1234', '김철수', '개발팀'),
('lee.younghee@1xinv.com', 'leeyh5678', '이영희', '기획팀'),
('park.minsu@1xinv.com', 'parkms9012', '박민수', '영업팀'),
('choi.jihye@1xinv.com', 'choijh3456', '최지혜', '마케팅팀'),
('jung.woojin@1xinv.com', 'jungwj7890', '정우진', '기술지원팀'),
('kang.mira@1xinv.com', 'kangmr2468', '강미라', '인사팀'),
('yoon.seongho@1xinv.com', 'yoonsh1357', '윤성호', '재무팀'),
('han.sujeong@1xinv.com', 'hansj8024', '한수정', '연구개발팀'),
('support@1xinv.com', '1xinvrhksfl13', '고객지원팀', '고객서비스');

EXIT;
```

### 4단계: Postfix 설정
```bash
sudo nano /etc/postfix/main.cf
```

**주요 설정 추가/수정:**
```conf
# 기본 설정
myhostname = 1xinv.local
mydomain = 1xinv.com
myorigin = $mydomain
mydestination = $myhostname, localhost.$mydomain, localhost, $mydomain
relayhost =
mynetworks = 127.0.0.0/8, 192.168.0.0/16, 10.0.0.0/8
inet_interfaces = all
inet_protocols = ipv4

# 가상 사용자 설정
virtual_mailbox_domains = 1xinv.com
virtual_mailbox_base = /var/mail/vhosts
virtual_mailbox_maps = mysql:/etc/postfix/mysql-virtual-mailbox-maps.cf
virtual_uid_maps = static:5000
virtual_gid_maps = static:5000

# SMTP 인증
smtpd_sasl_type = dovecot
smtpd_sasl_path = private/auth
smtpd_sasl_auth_enable = yes
smtpd_recipient_restrictions = permit_mynetworks, permit_sasl_authenticated, reject_unauth_destination

# 메시지 크기 제한
message_size_limit = 26214400
```

### 5단계: MySQL 연동 설정
```bash
# 가상 메일박스 맵 파일 생성
sudo nano /etc/postfix/mysql-virtual-mailbox-maps.cf
```

```conf
user = mailuser
password = mail_password
hosts = localhost
dbname = mail_db
query = SELECT CONCAT(email, '/') FROM virtual_users WHERE email='%s' AND active=1
```

### 6단계: 메일 디렉토리 생성
```bash
# vmail 사용자 생성
sudo groupadd -g 5000 vmail
sudo useradd -g vmail -u 5000 vmail -d /var/mail/vhosts -m

# 권한 설정
sudo chown -R vmail:vmail /var/mail/vhosts
sudo chmod -R 770 /var/mail/vhosts
```

### 7단계: Dovecot 설정
```bash
# 메인 설정
sudo nano /etc/dovecot/dovecot.conf
```

```conf
protocols = imap
listen = *
```

```bash
# 메일 위치 설정
sudo nano /etc/dovecot/conf.d/10-mail.conf
```

```conf
mail_location = maildir:/var/mail/vhosts/%d/%n
mail_privileged_group = vmail

first_valid_uid = 5000
last_valid_uid = 5000
```

```bash
# 인증 설정
sudo nano /etc/dovecot/conf.d/10-auth.conf
```

```conf
disable_plaintext_auth = no
auth_mechanisms = plain login

# SQL 인증 활성화
!include auth-sql.conf.ext
```

```bash
# SQL 인증 상세 설정
sudo nano /etc/dovecot/conf.d/auth-sql.conf.ext
```

```conf
passdb {
  driver = sql
  args = /etc/dovecot/dovecot-sql.conf.ext
}

userdb {
  driver = static
  args = uid=vmail gid=vmail home=/var/mail/vhosts/%d/%n
}
```

```bash
# SQL 연결 설정
sudo nano /etc/dovecot/dovecot-sql.conf.ext
```

```conf
driver = mysql
connect = host=localhost dbname=mail_db user=mailuser password=mail_password

default_pass_scheme = PLAIN

password_query = SELECT email as user, password FROM virtual_users WHERE email='%u' AND active=1
```

### 8단계: Postfix-Dovecot 연동
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
}
```

### 9단계: 서비스 재시작
```bash
# Postfix 재시작
sudo systemctl restart postfix
sudo systemctl enable postfix

# Dovecot 재시작
sudo systemctl restart dovecot
sudo systemctl enable dovecot

# 상태 확인
sudo systemctl status postfix
sudo systemctl status dovecot
```

### 10단계: 비밀번호 해시화 (보안 강화)
```bash
# 각 계정의 비밀번호를 해시로 변경
# 예시: ceo@1xinv.com
doveadm pw -s SHA512-CRYPT -p ceo2025admin
# 출력: {SHA512-CRYPT}$6$...해시값...

# MySQL에서 비밀번호 업데이트
sudo mysql -u mailuser -p mail_db
```

```sql
-- 해시된 비밀번호로 업데이트 (위에서 생성한 해시값 사용)
UPDATE virtual_users SET password = '{SHA512-CRYPT}$6$...' WHERE email = 'ceo@1xinv.com';

-- 또는 dovecot-sql.conf.ext에서 default_pass_scheme = SHA512-CRYPT 설정 후
-- 비밀번호 필드에 해시값만 저장 ('{SHA512-CRYPT}' 접두사 제외)
```

---

## 🧪 통합 테스트

### 1. Website 테스트
```bash
# 웹 접속
curl http://localhost/website
curl http://1xinv.local

# 문의 폼 DB 확인
sudo mysql -u admin -p ota_db -e "SELECT * FROM inquiries LIMIT 5;"
```

### 2. Webmail 테스트
```bash
# 웹 접속
curl http://webmail.1xinv.local
curl http://localhost/webmail

# Roundcube DB 확인
sudo mysql -u roundcube -p roundcubemail -e "SHOW TABLES;"
```

### 3. IMAP 테스트
```bash
# telnet으로 IMAP 연결 테스트
telnet localhost 143

# 로그인 시도 (위에서 실행)
a1 LOGIN ceo@1xinv.com ceo2025admin
a2 LIST "" "*"
a3 LOGOUT
```

### 4. SMTP 테스트
```bash
# telnet으로 SMTP 테스트
telnet localhost 25

# 명령어 입력
EHLO 1xinv.com
MAIL FROM:<ceo@1xinv.com>
RCPT TO:<support@1xinv.com>
DATA
Subject: Test Email

This is a test message.
.
QUIT
```

### 5. Webmail 로그인 테스트
브라우저에서:
```
http://webmail.1xinv.local

계정: ceo@1xinv.com
비밀번호: ceo2025admin
```

---

## 🔒 보안 강화

### 1. 방화벽 내부망 제한
```bash
# 특정 IP 대역만 웹메일 접근 허용
sudo ufw delete allow 80/tcp
sudo ufw allow from 192.168.1.0/24 to any port 80
```

### 2. fail2ban 설치 (brute-force 방지)
```bash
sudo apt install fail2ban -y

# Postfix jail 설정
sudo nano /etc/fail2ban/jail.local
```

```ini
[postfix]
enabled = true
port = smtp
filter = postfix
logpath = /var/log/mail.log
maxretry = 5

[dovecot]
enabled = true
port = imap
filter = dovecot
logpath = /var/log/mail.log
maxretry = 5
```

```bash
sudo systemctl restart fail2ban
```

### 3. 정기 백업 스크립트
```bash
sudo nano /usr/local/bin/backup_1xinv.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/backup/1xinv"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Website 백업
tar -czf $BACKUP_DIR/website_$DATE.tar.gz /var/www/html/website
mysqldump -u admin -p'strong_password_here' ota_db | gzip > $BACKUP_DIR/ota_db_$DATE.sql.gz

# Webmail 백업
tar -czf $BACKUP_DIR/webmail_$DATE.tar.gz /var/www/html/webmail
mysqldump -u roundcube -p'roundcube_strong_password' roundcubemail | gzip > $BACKUP_DIR/roundcube_db_$DATE.sql.gz

# Mail 백업
mysqldump -u mailuser -p'mail_password' mail_db | gzip > $BACKUP_DIR/mail_db_$DATE.sql.gz
tar -czf $BACKUP_DIR/mailboxes_$DATE.tar.gz /var/mail/vhosts

# 7일 이상 백업 삭제
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
sudo chmod +x /usr/local/bin/backup_1xinv.sh

# 크론 등록 (매일 새벽 3시)
sudo crontab -e
# 추가: 0 3 * * * /usr/local/bin/backup_1xinv.sh >> /var/log/backup.log 2>&1
```

### 4. 로그 모니터링
```bash
# 실시간 로그 확인
sudo tail -f /var/log/apache2/webmail_error.log
sudo tail -f /var/log/mail.log
sudo tail -f /var/www/html/webmail/logs/errors.log
```

---

## 🔧 문제 해결

### Website 관련

**문제: 페이지가 표시되지 않음**
```bash
# Apache 상태 확인
sudo systemctl status apache2

# 에러 로그 확인
sudo tail -50 /var/log/apache2/1xinv_website_error.log

# 권한 확인
ls -la /var/www/html/website
```

**문제: DB 연결 오류**
```bash
# MySQL 연결 테스트
mysql -u admin -p ota_db

# DB 사용자 권한 확인
sudo mysql -u root -p -e "SHOW GRANTS FOR 'admin'@'localhost';"
```

### Webmail 관련

**문제: 로그인 불가**
```bash
# IMAP 서버 연결 확인
telnet localhost 143

# Dovecot 로그 확인
sudo tail -50 /var/log/mail.log

# 사용자 인증 테스트
doveadm auth test ceo@1xinv.com ceo2025admin
```

**문제: 메일 발송 안됨**
```bash
# Postfix 큐 확인
sudo postqueue -p

# 로그 확인
sudo tail -50 /var/log/mail.log

# Postfix 재시작
sudo systemctl restart postfix
```

**문제: Composer 오류**
```bash
cd /var/www/html/webmail
sudo -u www-data composer install --no-dev
```

### 메일 서버 관련

**문제: 메일 수신 안됨**
```bash
# 메일박스 확인
sudo ls -la /var/mail/vhosts/1xinv.com/

# 권한 확인
sudo chown -R vmail:vmail /var/mail/vhosts
sudo chmod -R 770 /var/mail/vhosts
```

**문제: SQL 인증 오류**
```bash
# MySQL 연결 테스트
mysql -u mailuser -p mail_db -e "SELECT * FROM virtual_users LIMIT 5;"

# Dovecot SQL 설정 확인
sudo doveconf -n | grep sql
```

---

## 📊 시스템 상태 확인

### 종합 상태 체크 스크립트
```bash
#!/bin/bash
echo "=== 1xINV 시스템 상태 체크 ==="
echo

echo "[Apache]"
systemctl status apache2 | grep Active

echo
echo "[MySQL]"
systemctl status mysql | grep Active

echo
echo "[Postfix]"
systemctl status postfix | grep Active

echo
echo "[Dovecot]"
systemctl status dovecot | grep Active

echo
echo "[디스크 사용량]"
df -h | grep -E "Filesystem|/dev/sd"

echo
echo "[메모리 사용량]"
free -h

echo
echo "[Apache 사이트]"
apache2ctl -S | grep 1xinv

echo
echo "[메일 큐]"
postqueue -p | tail -1

echo
echo "[최근 로그인 (Webmail)]"
tail -5 /var/www/html/webmail/logs/userlogins.log

echo
echo "=== 체크 완료 ==="
```

---

## ✅ 배포 완료 체크리스트

### Website
- [ ] Apache 설치 및 실행 중
- [ ] PHP 설치 및 동작 확인
- [ ] MySQL/MariaDB 설치 및 실행 중
- [ ] ota_db 데이터베이스 생성
- [ ] website 파일 배포 완료
- [ ] Apache 가상호스트 설정
- [ ] http://1xinv.local 접속 가능
- [ ] 문의 폼 작동 확인

### Webmail
- [ ] Roundcube 파일 배포 완료
- [ ] Composer 의존성 설치
- [ ] roundcubemail DB 생성 및 초기화
- [ ] config.inc.php 설정 (DB, 암호화키)
- [ ] .htaccess IP 제한 설정
- [ ] installer 디렉토리 삭제
- [ ] http://webmail.1xinv.local 접속 가능
- [ ] 로그인 화면 표시

### 메일 서버
- [ ] Postfix 설치 및 실행 중
- [ ] Dovecot 설치 및 실행 중
- [ ] mail_db 데이터베이스 생성
- [ ] 10개 테스트 계정 등록
- [ ] IMAP 연결 테스트 성공
- [ ] SMTP 연결 테스트 성공
- [ ] Webmail 로그인 성공
- [ ] 메일 송수신 테스트 성공

### 보안
- [ ] 방화벽 설정 완료
- [ ] 내부 IP 대역 제한
- [ ] fail2ban 설치 및 설정
- [ ] 백업 스크립트 작성 및 크론 등록
- [ ] 로그 모니터링 설정

---

## 📞 지원 및 문의

- **Email**: support@1xinv.com
- **로그 위치**:
  - Website: `/var/log/apache2/1xinv_website_error.log`
  - Webmail: `/var/log/apache2/webmail_error.log`
  - Mail: `/var/log/mail.log`

---

**작성일**: 2025년 10월 27일
**작성자**: 1xINV IT Security Team
**버전**: 1.0

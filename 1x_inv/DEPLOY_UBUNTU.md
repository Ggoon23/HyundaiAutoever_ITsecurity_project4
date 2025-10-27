# 1xINV 사내 웹메일 Ubuntu 배포 가이드

## 📋 개요
Roundcube 기반 회사 내부망 전용 웹메일 시스템 구축 가이드

## 🔧 시스템 요구사항

### 최소 사양
- **OS**: Ubuntu 20.04 LTS 이상
- **웹서버**: Apache 2.4+ 또는 Nginx
- **PHP**: 7.3 이상 (권장: 7.4 or 8.0)
- **데이터베이스**: MySQL 5.7+ / MariaDB 10.3+ / PostgreSQL
- **메모리**: 최소 2GB RAM
- **디스크**: 최소 10GB 여유 공간

### 필수 PHP 확장
```bash
php-cli php-common php-json php-xml php-mbstring php-zip
php-intl php-curl php-gd php-mysql php-ldap php-imap
```

---

## 📦 1단계: 시스템 준비

### 1.1 패키지 업데이트
```bash
sudo apt update
sudo apt upgrade -y
```

### 1.2 Apache + PHP + MySQL 설치
```bash
# Apache 웹서버
sudo apt install apache2 -y

# PHP 및 필수 확장
sudo apt install php php-cli php-common php-json php-xml \
  php-mbstring php-zip php-intl php-curl php-gd \
  php-mysql php-ldap php-imap -y

# MySQL/MariaDB
sudo apt install mariadb-server mariadb-client -y

# 추가 유틸리티
sudo apt install unzip composer -y
```

### 1.3 Apache 모듈 활성화
```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod deflate
sudo a2enmod expires
sudo systemctl restart apache2
```

---

## 🗄️ 2단계: 데이터베이스 설정

### 2.1 MySQL 보안 설정
```bash
sudo mysql_secure_installation
```
- root 비밀번호 설정
- 익명 사용자 제거
- 원격 root 로그인 비활성화
- test 데이터베이스 제거

### 2.2 Roundcube 데이터베이스 생성
```bash
sudo mysql -u root -p
```

```sql
-- 데이터베이스 생성
CREATE DATABASE roundcubemail CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 사용자 생성 (비밀번호 변경 필수!)
CREATE USER 'roundcube'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';

-- 권한 부여
GRANT ALL PRIVILEGES ON roundcubemail.* TO 'roundcube'@'localhost';

-- 권한 적용
FLUSH PRIVILEGES;

-- 종료
EXIT;
```

---

## 📂 3단계: Roundcube 설치

### 3.1 파일 배치
```bash
# 웹 루트로 이동
cd /var/www/html

# 프로젝트 파일 복사
sudo cp -r /path/to/1x_inv/webmail /var/www/html/webmail

# 소유권 변경
sudo chown -R www-data:www-data /var/www/html/webmail

# 권한 설정
sudo chmod -R 755 /var/www/html/webmail
sudo chmod -R 777 /var/www/html/webmail/temp
sudo chmod -R 777 /var/www/html/webmail/logs
```

### 3.2 Composer 의존성 설치
```bash
cd /var/www/html/webmail
sudo -u www-data composer install --no-dev --optimize-autoloader
```

### 3.3 데이터베이스 초기화
```bash
# MySQL 스키마 가져오기
sudo mysql -u roundcube -p roundcubemail < /var/www/html/webmail/SQL/mysql.initial.sql
```

---

## ⚙️ 4단계: 설정 파일 구성

### 4.1 config.inc.php 수정
```bash
sudo nano /var/www/html/webmail/config/config.inc.php
```

**필수 수정 항목:**
```php
// 1. 데이터베이스 비밀번호 변경
$config['db_dsnw'] = 'mysql://roundcube:YOUR_DB_PASSWORD@localhost/roundcubemail';

// 2. 암호화 키 생성 (24자)
$config['des_key'] = 'RANDOM-24CHAR-KEY-HERE!';

// 3. 회사 메일 서버 설정
$config['imap_host'] = 'mail.company.local:143';  // IMAP 서버 주소
$config['smtp_host'] = 'mail.company.local:25';   // SMTP 서버 주소

// 4. 회사 정보
$config['product_name'] = '1xINV 사내 웹메일';
$config['support_url'] = 'http://helpdesk.company.local';
```

**암호화 키 생성 명령:**
```bash
# 24자 랜덤 키 생성
openssl rand -base64 24 | cut -c1-24
```

### 4.2 .htaccess IP 제한 수정
```bash
sudo nano /var/www/html/webmail/.htaccess
```

**회사 내부 IP 대역으로 변경:**
```apache
<RequireAll>
    # 실제 회사 네트워크 IP 대역으로 수정
    Require ip 192.168.1.0/24      # 예시: 사무실 네트워크
    Require ip 10.10.0.0/16        # 예시: 내부 VPN
    Require ip 127.0.0.1           # 로컬호스트
</RequireAll>
```

---

## 🌐 5단계: Apache 가상호스트 설정

### 5.1 가상호스트 파일 생성
```bash
sudo nano /etc/apache2/sites-available/webmail.conf
```

```apache
<VirtualHost *:80>
    ServerName webmail.company.local
    ServerAdmin admin@company.local
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

    # 로그 설정
    ErrorLog ${APACHE_LOG_DIR}/webmail_error.log
    CustomLog ${APACHE_LOG_DIR}/webmail_access.log combined

    # 보안 헤더
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

### 5.2 사이트 활성화
```bash
# 기본 사이트 비활성화
sudo a2dissite 000-default.conf

# 웹메일 사이트 활성화
sudo a2ensite webmail.conf

# Apache 재시작
sudo systemctl restart apache2
```

---

## 🔒 6단계: 보안 강화

### 6.1 installer 디렉토리 삭제
```bash
# 설치 완료 후 반드시 삭제!
sudo rm -rf /var/www/html/webmail/installer
```

### 6.2 파일 권한 재확인
```bash
cd /var/www/html/webmail

# 읽기 전용으로 설정
sudo chmod -R 755 .

# 쓰기 필요 디렉토리만 예외
sudo chmod -R 777 temp logs
```

### 6.3 방화벽 설정
```bash
# UFW 방화벽 활성화
sudo ufw enable

# HTTP 허용
sudo ufw allow 80/tcp

# SSH 허용 (관리용)
sudo ufw allow 22/tcp

# 특정 IP 대역만 허용 (예시)
sudo ufw allow from 192.168.1.0/24 to any port 80

# 상태 확인
sudo ufw status
```

### 6.4 PHP 보안 설정
```bash
sudo nano /etc/php/7.4/apache2/php.ini
```

```ini
# 보안 설정 강화
display_errors = Off
expose_php = Off
max_execution_time = 300
memory_limit = 128M
upload_max_filesize = 25M
post_max_size = 25M
session.cookie_httponly = 1
session.cookie_secure = 0  # HTTP 사용시
session.cookie_samesite = Strict
```

---

## 🧪 7단계: 테스트

### 7.1 웹 접속 테스트
브라우저에서 접속:
```
http://webmail.company.local
또는
http://서버IP주소/webmail
```

### 7.2 로그 확인
```bash
# Apache 에러 로그
sudo tail -f /var/log/apache2/webmail_error.log

# Roundcube 로그
sudo tail -f /var/www/html/webmail/logs/errors.log
```

### 7.3 테스트 계정 로그인
- 회사 메일 계정으로 로그인 시도
- 메일 송수신 테스트
- 첨부파일 업로드/다운로드 테스트

---

## 🔧 문제 해결

### 데이터베이스 연결 오류
```bash
# MySQL 서비스 확인
sudo systemctl status mysql

# 연결 테스트
mysql -u roundcube -p roundcubemail
```

### 권한 문제
```bash
# 소유권 재설정
sudo chown -R www-data:www-data /var/www/html/webmail

# 디렉토리 권한
sudo chmod -R 755 /var/www/html/webmail
sudo chmod -R 777 /var/www/html/webmail/temp
sudo chmod -R 777 /var/www/html/webmail/logs
```

### IMAP/SMTP 연결 오류
```bash
# 메일 서버 연결 테스트
telnet mail.company.local 143  # IMAP
telnet mail.company.local 25   # SMTP

# 방화벽 확인
sudo ufw status
```

### Apache 모듈 확인
```bash
# 활성화된 모듈 확인
apache2ctl -M | grep rewrite
apache2ctl -M | grep headers

# 설정 문법 검사
sudo apache2ctl configtest
```

---

## 📊 모니터링

### 로그 모니터링 스크립트
```bash
# 실시간 로그 감시
watch -n 5 'tail -20 /var/www/html/webmail/logs/errors.log'
```

### 디스크 용량 확인
```bash
# 디렉토리별 용량
du -sh /var/www/html/webmail/*

# temp 디렉토리 정리 (크론잡 등록 권장)
find /var/www/html/webmail/temp -type f -mtime +7 -delete
```

---

## 🔄 업데이트 및 유지보수

### Roundcube 업데이트
```bash
cd /var/www/html/webmail

# 백업
sudo tar -czf ~/webmail_backup_$(date +%Y%m%d).tar.gz .

# Composer 업데이트
sudo -u www-data composer update

# 데이터베이스 마이그레이션
sudo -u www-data php bin/updatedb.sh --package=roundcube
```

### 자동 백업 스크립트
```bash
sudo nano /usr/local/bin/backup_webmail.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/backup/webmail"
DATE=$(date +%Y%m%d_%H%M%S)

# 디렉토리 생성
mkdir -p $BACKUP_DIR

# 파일 백업
tar -czf $BACKUP_DIR/webmail_files_$DATE.tar.gz /var/www/html/webmail

# DB 백업
mysqldump -u roundcube -p'PASSWORD' roundcubemail | gzip > $BACKUP_DIR/webmail_db_$DATE.sql.gz

# 7일 이상된 백업 삭제
find $BACKUP_DIR -type f -mtime +7 -delete
```

```bash
# 실행 권한 부여
sudo chmod +x /usr/local/bin/backup_webmail.sh

# 크론 등록 (매일 새벽 2시)
sudo crontab -e
# 추가: 0 2 * * * /usr/local/bin/backup_webmail.sh
```

---

## 📞 지원

문제 발생 시:
1. 로그 파일 확인: `/var/www/html/webmail/logs/`
2. Apache 로그: `/var/log/apache2/`
3. IT 헬프데스크 문의

---

## ✅ 체크리스트

배포 완료 전 확인사항:

- [ ] 데이터베이스 생성 및 초기화 완료
- [ ] config.inc.php 설정 완료 (DB, 암호화키, 메일서버)
- [ ] .htaccess IP 제한 설정 완료
- [ ] Apache 가상호스트 설정 완료
- [ ] installer 디렉토리 삭제 완료
- [ ] 파일 권한 설정 완료 (temp, logs 777)
- [ ] 방화벽 규칙 설정 완료
- [ ] 테스트 계정 로그인 성공
- [ ] 메일 송수신 테스트 성공
- [ ] 백업 스크립트 설정 완료
- [ ] 모니터링 도구 설정 완료

---

**배포 완료!** 🎉

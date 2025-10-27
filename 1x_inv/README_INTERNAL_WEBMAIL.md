# 1xINV 사내 웹메일 시스템

## 🏢 개요

Roundcube 1.6.6 기반의 회사 내부망 전용 웹메일 시스템입니다.

---

## 📁 디렉토리 구조

```
1x_inv/webmail/
├── config/
│   ├── config.inc.php          # 메인 설정 파일 (내부망 전용)
│   ├── defaults.inc.php        # 기본 설정
│   └── mimetypes.php           # MIME 타입
├── program/                    # 핵심 애플리케이션
├── plugins/                    # 플러그인 (35개)
├── skins/                      # UI 테마
├── public_html/                # 웹 리소스
├── logs/                       # 로그 파일
├── temp/                       # 임시 파일
├── .htaccess                   # Apache 보안 설정 (IP 제한)
└── index.php                   # 메인 엔트리 포인트
```

---

## ⚙️ 주요 설정

### 1. **내부망 IP 제한** (`.htaccess`)
- 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16 대역만 허용
- 실제 회사 네트워크에 맞게 수정 필요

### 2. **메일 서버 연결** (`config/config.inc.php`)
```php
$config['imap_host'] = 'mail.company.local:143';
$config['smtp_host'] = 'mail.company.local:25';
```

### 3. **보안 강화**
- CSRF 방지
- 세션 보안 (HttpOnly, SameSite)
- XSS 방지 헤더
- 파일 업로드 제한 (25MB)
- 외부 이미지 차단

### 4. **한국어 최적화**
```php
$config['language'] = 'ko_KR';
$config['timezone'] = 'Asia/Seoul';
$config['product_name'] = '1xINV 사내 웹메일';
```

---

## 🚀 설치 가이드

상세 설치 가이드는 [DEPLOY_UBUNTU.md](DEPLOY_UBUNTU.md) 참조

### 빠른 시작 (요약)

1. **시스템 준비**
```bash
sudo apt install apache2 php php-mysql mariadb-server
```

2. **데이터베이스 생성**
```sql
CREATE DATABASE roundcubemail;
CREATE USER 'roundcube'@'localhost' IDENTIFIED BY 'password';
GRANT ALL ON roundcubemail.* TO 'roundcube'@'localhost';
```

3. **설정 파일 수정**
```bash
cd /var/www/html/webmail/config
cp config.inc.php.sample config.inc.php
nano config.inc.php  # DB, 메일서버, 암호화키 설정
```

4. **권한 설정**
```bash
sudo chown -R www-data:www-data /var/www/html/webmail
sudo chmod -R 777 /var/www/html/webmail/temp
sudo chmod -R 777 /var/www/html/webmail/logs
```

5. **설치 완료 후 보안 조치**
```bash
sudo rm -rf /var/www/html/webmail/installer
```

---

## 🔒 보안 기능

### Apache 레벨 (.htaccess)
- ✅ 내부 IP 대역만 접근 허용
- ✅ installer 디렉토리 완전 차단
- ✅ 민감한 파일 접근 차단 (config, logs, SQL 등)
- ✅ 보안 헤더 강제 적용 (XSS, CSP, Frame-Options)
- ✅ 숨김 파일 접근 차단

### 애플리케이션 레벨 (config.inc.php)
- ✅ 로그인 시도 횟수 제한 (Rate Limiting)
- ✅ 세션 보안 강화
- ✅ 외부 이미지/리소스 차단
- ✅ 안전한 URL 화이트리스트
- ✅ 로그인/세션 로깅

### PHP 레벨
- ✅ display_errors Off
- ✅ expose_php Off
- ✅ Session Cookie 보안 설정
- ✅ 파일 업로드 크기 제한

---

## 📊 시스템 요구사항

### 최소 사양
- Ubuntu 20.04 LTS 이상
- PHP 7.3+ (권장: 7.4 or 8.0)
- MySQL 5.7+ / MariaDB 10.3+
- Apache 2.4+ (mod_rewrite, mod_headers 필수)
- 2GB RAM, 10GB 디스크

### 필수 PHP 확장
```
php-cli php-json php-xml php-mbstring php-zip
php-intl php-curl php-gd php-mysql php-imap
```

---

## 🔧 수정 필요 항목

### 필수 수정 (배포 전)

1. **데이터베이스 비밀번호** ([config/config.inc.php](webmail/config/config.inc.php#L28))
```php
$config['db_dsnw'] = 'mysql://roundcube:YOUR_DB_PASSWORD@localhost/roundcubemail';
```

2. **암호화 키 (24자)** ([config/config.inc.php](webmail/config/config.inc.php#L51))
```bash
# 생성 명령어
openssl rand -base64 24 | cut -c1-24
```

3. **메일 서버 주소** ([config/config.inc.php](webmail/config/config.inc.php#L32))
```php
$config['imap_host'] = 'YOUR_MAIL_SERVER:143';
$config['smtp_host'] = 'YOUR_MAIL_SERVER:25';
```

4. **내부 IP 대역** ([.htaccess](webmail/.htaccess#L10))
```apache
Require ip 192.168.1.0/24  # 실제 회사 네트워크
```

---

## 📝 활성화된 플러그인

기본 설정에서 활성화된 플러그인:

- **archive**: 메일 아카이브 기능
- **zipdownload**: 첨부파일 일괄 다운로드
- **markasjunk**: 스팸 메일 표시
- **password**: 비밀번호 변경 (선택)
- **newmail_notifier**: 신규 메일 알림

추가 플러그인은 [webmail/plugins/](webmail/plugins) 참조

---

## 🐛 문제 해결

### 로그인 불가
- 메일 서버 연결 확인: `telnet mail.company.local 143`
- 로그 확인: `tail -f logs/errors.log`
- DB 연결 테스트: `mysql -u roundcube -p`

### 권한 오류
```bash
sudo chown -R www-data:www-data /var/www/html/webmail
sudo chmod -R 755 /var/www/html/webmail
sudo chmod -R 777 /var/www/html/webmail/temp
sudo chmod -R 777 /var/www/html/webmail/logs
```

### IP 차단 오류
- `.htaccess` 파일에서 현재 IP 대역 추가
- Apache 에러 로그 확인: `tail -f /var/log/apache2/error.log`

### 데이터베이스 연결 오류
- MySQL 서비스 상태: `sudo systemctl status mysql`
- config.inc.php의 DB 설정 확인
- DB 사용자 권한 확인

---

## 📖 관련 문서

- [Ubuntu 배포 가이드](DEPLOY_UBUNTU.md) - 상세 설치 및 설정
- [Roundcube 공식 문서](https://github.com/roundcube/roundcubemail/wiki)
- [보안 설정 가이드](https://github.com/roundcube/roundcubemail/wiki/Configuration#security-and-privacy)

---

## 🔄 유지보수

### 로그 관리
```bash
# 에러 로그 확인
tail -f /var/www/html/webmail/logs/errors.log

# 로그인 기록 확인
tail -f /var/www/html/webmail/logs/userlogins.log

# 임시 파일 정리 (7일 이상)
find temp/ -type f -mtime +7 -delete
```

### 백업
```bash
# 파일 백업
tar -czf webmail_backup_$(date +%Y%m%d).tar.gz webmail/

# 데이터베이스 백업
mysqldump -u roundcube -p roundcubemail > webmail_db_$(date +%Y%m%d).sql
```

### 업데이트
```bash
cd webmail
composer update
php bin/updatedb.sh --package=roundcube
```

---

## 📞 지원

- **IT 헬프데스크**: http://helpdesk.company.local
- **로그 위치**: `/var/www/html/webmail/logs/`
- **설정 파일**: `/var/www/html/webmail/config/config.inc.php`

---

## ⚠️ 중요 보안 알림

### 배포 전 필수 확인
- [ ] installer 디렉토리 삭제됨
- [ ] 암호화 키 변경됨 (기본값 사용 금지!)
- [ ] DB 비밀번호 강력한 것으로 변경됨
- [ ] 내부 IP 대역 정확히 설정됨
- [ ] 외부망에서 접근 불가 확인됨

### 정기 점검 항목
- 로그 파일 모니터링 (의심스러운 접속 시도)
- temp 디렉토리 용량 확인
- 데이터베이스 백업 상태
- 플러그인 및 Roundcube 버전 업데이트

---

**1xINV IT Security Team**

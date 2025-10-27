# User Registration Plugin for Roundcube

## 개요

CVE-2025-49113 취약점 테스트를 위한 회원가입 및 승인 시스템 플러그인

## 주요 기능

### 1. 회원가입 시스템
- 누구나 `@1xinv.com` 도메인으로 가입 가능
- 가입 즉시 로그인 가능
- 승인 전에는 기능 제한 (읽기 전용)

### 2. 승인 대기 상태
**허용되는 기능:**
- ✅ 로그인
- ✅ 웹메일 UI 탐색
- ✅ 설정 메뉴 접근
- ✅ **파일 업로드** (CVE-2025-49113 테스트 가능!)

**차단되는 기능:**
- ❌ 메일 발송
- ❌ 환경설정 저장
- ❌ 주소록 수정

### 3. 인사팀 승인 시스템
- 인사팀 계정(`kang.mira@1xinv.com`)만 승인 권한 보유
- 승인 관리 페이지에서 승인/거부 처리
- 승인 후 전체 기능 사용 가능

## 설치 방법

### 1. 플러그인 활성화
```php
// config/config.inc.php
$config['plugins'] = [
    // ... 다른 플러그인
    'user_registration',
];
```

### 2. 데이터베이스 테이블 생성
플러그인이 자동으로 `registration_pending` 테이블을 생성합니다.

```sql
CREATE TABLE registration_pending (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    approved_at DATETIME NULL,
    UNIQUE KEY unique_user (user_id),
    UNIQUE KEY unique_email (email),
    INDEX idx_status (status)
);
```

## 사용 방법

### 사용자: 회원가입
1. 로그인 페이지에서 "회원가입" 링크 클릭
2. 정보 입력 (`@1xinv.com` 도메인 필수)
3. 가입 완료 후 즉시 로그인 가능
4. 승인 대기 중 배너 표시됨

### 인사팀: 승인 관리
1. `kang.mira@1xinv.com`으로 로그인
2. 다음 URL 접속:
   ```
   /?_task=login&_action=plugin.user_approval
   ```
3. 가입 신청 목록 확인
4. 승인 또는 거부 버튼 클릭

## CVE-2025-49113 테스트

### 왜 이 구조가 필요한가?

CVE-2025-49113는 **Post-Auth RCE** 취약점으로:
- 로그인된 사용자만 공격 가능
- 파일 업로드 기능 필요
- 하지만 메일 발송 등 실제 서비스 기능은 불필요

이 플러그인은:
- ✅ 로그인 가능 (인증 통과)
- ✅ 파일 업로드 가능 (취약점 악용 경로)
- ❌ 실제 메일 발송 차단 (피해 최소화)

### 테스트 시나리오
```bash
# 1. 테스트 계정 생성
http://webmail.1xinv.local/?_task=login&_action=plugin.user_registration
Email: attacker@1xinv.com
Password: hacker123

# 2. 로그인 (승인 대기 상태)
로그인 → 승인 대기 배너 표시

# 3. 파일 업로드 접근
Settings → Identities → Edit → Upload Photo

# 4. 악성 파일 업로드
파일명: 12xxx|b:0;test|O:4:"test":0:{}xxx|3.png
→ CVE-2025-49113 Exploit!
```

## 파일 구조

```
plugins/user_registration/
├── user_registration.php           # 메인 플러그인 파일
├── composer.json                   # 플러그인 메타데이터
├── README.md                       # 이 파일
└── skins/
    └── elastic/
        ├── templates/
        │   ├── registration_form.html    # 회원가입 폼
        │   └── approval_page.html        # 승인 관리 페이지
        └── user_registration.css         # 스타일시트
```

## API

### Actions
- `plugin.user_registration` - 회원가입 폼 표시
- `plugin.user_registration_submit` - 회원가입 처리
- `plugin.user_approval` - 승인 관리 페이지 (인사팀 전용)
- `plugin.user_approve` - 사용자 승인
- `plugin.user_reject` - 사용자 거부

### Hooks
- `template_object_loginform` - 로그인 폼에 회원가입 링크 추가
- `startup` - 사용자 상태 확인 및 권한 제한
- `message_before_send` - 승인 대기 사용자 메일 발송 차단
- `preferences_save` - 승인 대기 사용자 설정 변경 차단
- `contact_*` - 승인 대기 사용자 주소록 수정 차단

## 보안 고려사항

### ⚠️ 주의
이 플러그인은 **취약한 Roundcube 버전(1.6.6)**과 함께 사용되도록 설계되었습니다.
- 교육 및 테스트 목적으로만 사용
- 절대 프로덕션 환경에 배포 금지
- 반드시 격리된 내부망에서만 사용

### 권한 제한 우회 가능성
현재 구현에서는 **파일 업로드가 허용**됩니다.
- CVE-2025-49113 테스트를 위해 의도적으로 허용
- 실제 환경에서는 파일 업로드도 차단해야 함

## 라이선스

GPL-3.0-or-later

## 제작자

1xINV IT Security Team
support@1xinv.com

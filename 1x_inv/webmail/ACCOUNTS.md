# 1xINV 사내 웹메일 - 테스트 계정 목록

## 📧 이메일 계정 정보

### 🎯 대표 계정
| 이름 | 이메일 | 비밀번호 | 역할 |
|------|--------|---------|------|
| 대표이사 | ceo@1xinv.com | ceo2025admin | 최고경영자 |

---

### 👥 직원 계정 (8명)

| 이름 | 이메일 | 비밀번호 | 부서 |
|------|--------|---------|------|
| 김철수 | kim.chulsu@1xinv.com | kimcs1234 | 개발팀 |
| 이영희 | lee.younghee@1xinv.com | leeyh5678 | 기획팀 |
| 박민수 | park.minsu@1xinv.com | parkms9012 | 영업팀 |
| 최지혜 | choi.jihye@1xinv.com | choijh3456 | 마케팅팀 |
| 정우진 | jung.woojin@1xinv.com | jungwj7890 | 기술지원팀 |
| 강미라 | kang.mira@1xinv.com | kangmr2468 | 인사팀 |
| 윤성호 | yoon.seongho@1xinv.com | yoonsh1357 | 재무팀 |
| 한수정 | han.sujeong@1xinv.com | hansj8024 | 연구개발팀 |

---

### 💬 문의사항 계정
| 이름 | 이메일 | 비밀번호 | 역할 |
|------|--------|---------|------|
| 고객지원 | support@1xinv.com | 1xinvrhksfl13 | 고객문의 전용 |

---

## 📋 계정 생성 스크립트

### MySQL 사용자 생성 (메일 서버용)

```sql
-- 메일 데이터베이스 생성 (필요시)
CREATE DATABASE IF NOT EXISTS mail CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mail;

-- 가상 사용자 테이블 (Postfix/Dovecot용)
CREATE TABLE IF NOT EXISTS virtual_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 비밀번호는 bcrypt 또는 SHA512-CRYPT로 해시화 필요
-- 아래는 평문 예시 (실제 사용시 해시 필요!)

-- 대표 계정
INSERT INTO virtual_users (email, password, name, department) VALUES
('ceo@1xinv.com', 'ceo2025admin', '대표이사', '경영진');

-- 직원 계정
INSERT INTO virtual_users (email, password, name, department) VALUES
('kim.chulsu@1xinv.com', 'kimcs1234', '김철수', '개발팀'),
('lee.younghee@1xinv.com', 'leeyh5678', '이영희', '기획팀'),
('park.minsu@1xinv.com', 'parkms9012', '박민수', '영업팀'),
('choi.jihye@1xinv.com', 'choijh3456', '최지혜', '마케팅팀'),
('jung.woojin@1xinv.com', 'jungwj7890', '정우진', '기술지원팀'),
('kang.mira@1xinv.com', 'kangmr2468', '강미라', '인사팀'),
('yoon.seongho@1xinv.com', 'yoonsh1357', '윤성호', '재무팀'),
('han.sujeong@1xinv.com', 'hansj8024', '한수정', '연구개발팀');

-- 고객지원 계정
INSERT INTO virtual_users (email, password, name, department) VALUES
('support@1xinv.com', '1xinvrhksfl13', '고객지원팀', '고객서비스');
```

---

## 🔐 비밀번호 해시 생성

실제 메일 서버 배포시 비밀번호를 해시화해야 합니다.

### Dovecot용 SHA512-CRYPT 생성
```bash
# 각 비밀번호에 대해 실행
doveadm pw -s SHA512-CRYPT -p ceo2025admin
doveadm pw -s SHA512-CRYPT -p kimcs1234
doveadm pw -s SHA512-CRYPT -p leeyh5678
# ... 나머지 계정도 동일하게
```

### PHP bcrypt 해시 생성
```php
<?php
$accounts = [
    'ceo@1xinv.com' => 'ceo2025admin',
    'kim.chulsu@1xinv.com' => 'kimcs1234',
    'lee.younghee@1xinv.com' => 'leeyh5678',
    'park.minsu@1xinv.com' => 'parkms9012',
    'choi.jihye@1xinv.com' => 'choijh3456',
    'jung.woojin@1xinv.com' => 'jungwj7890',
    'kang.mira@1xinv.com' => 'kangmr2468',
    'yoon.seongho@1xinv.com' => 'yoonsh1357',
    'han.sujeong@1xinv.com' => 'hansj8024',
    'support@1xinv.com' => '1xinvrhksfl13'
];

foreach ($accounts as $email => $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    echo "UPDATE virtual_users SET password = '$hash' WHERE email = '$email';\n";
}
?>
```

---

## 🧪 테스트 방법

### 1. IMAP 연결 테스트
```bash
# telnet으로 IMAP 테스트
telnet mail.company.local 143

# 로그인 테스트
a1 LOGIN ceo@1xinv.com ceo2025admin
a2 LIST "" "*"
a3 LOGOUT
```

### 2. SMTP 연결 테스트
```bash
# telnet으로 SMTP 테스트
telnet mail.company.local 25

# EHLO 테스트
EHLO 1xinv.com
MAIL FROM:<ceo@1xinv.com>
RCPT TO:<support@1xinv.com>
DATA
Subject: Test

This is a test email.
.
QUIT
```

### 3. 웹메일 로그인 테스트
브라우저에서:
```
http://webmail.company.local
또는
http://서버IP/webmail
```

각 계정으로 로그인 테스트 진행

---

## 📊 계정 관리

### 계정 추가
```sql
INSERT INTO virtual_users (email, password, name, department)
VALUES ('new.user@1xinv.com', 'hashed_password', '이름', '부서');
```

### 계정 비활성화
```sql
UPDATE virtual_users SET active = 0 WHERE email = 'user@1xinv.com';
```

### 계정 삭제
```sql
DELETE FROM virtual_users WHERE email = 'user@1xinv.com';
```

### 모든 계정 조회
```sql
SELECT email, name, department, active, created_at
FROM virtual_users
ORDER BY created_at DESC;
```

---

## ⚠️ 보안 주의사항

1. **비밀번호 변경 권장**
   - 초기 비밀번호는 테스트용
   - 실운영시 각 사용자가 반드시 변경

2. **비밀번호 정책**
   - 최소 8자 이상
   - 영문 소문자 + 숫자 조합
   - 실운영시 대문자, 특수문자 추가 권장

3. **계정 보안**
   - support@ 계정 비밀번호는 관리자만 공유
   - 퇴사자 계정은 즉시 비활성화
   - 로그인 실패 로그 정기 모니터링

4. **문서 보안**
   - 이 파일은 내부망에서만 접근 가능하도록 관리
   - 외부 유출 주의
   - Git 저장소에 커밋하지 말 것 (필요시 .gitignore 추가)

---

## 📞 문의

계정 관련 문제 발생시:
- **IT 헬프데스크**: support@1xinv.com
- **전화**: 02-000-0000
- **웹메일 로그**: /var/www/html/webmail/logs/userlogins.log

---

**생성일**: 2025년 10월 27일
**관리자**: 1xINV IT Security Team

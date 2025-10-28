# 1xINV 시스템 변경 사항 요약

**작업 완료일**: 2025-01-27
**작업 내용**: 통합 문서 작성, Roundcube 브랜딩 제거, DB 계정 회사 맞춤

---

## ✅ 완료된 작업

### 1. 통합 마스터 문서 생성
- **파일**: `MASTER_DEPLOY_GUIDE.md`
- **내용**: Website + Webmail + Mail Server 완전 통합 배포 가이드
- **특징**:
  - 자동 설치 스크립트 포함
  - 수동 설치 단계별 가이드
  - 문제 해결 섹션 포함
  - 한 파일로 모든 것 해결 가능

### 2. 계정 정보 정리 파일 생성
- **파일**: `ACCOUNTS.txt`
- **내용**:
  - 데이터베이스 관리자 계정 (4개)
  - 웹메일 사용자 계정 (10개)
  - 시스템 계정 정보
  - 접속 정보
  - 보안 주의사항

### 3. Roundcube 브랜딩 제거
**변경된 표현:**
- `roundcubemail` → `webmail_db` (데이터베이스명)
- `roundcube` → `mailadmin` (사용자명)
- `Roundcube` → `1xINV 사내 웹메일` (제품명)

**수정된 파일:**
- [x] `QUICK_DEPLOY_GUIDE.md` (모든 참조 변경)
- [x] `webmail/config/config.inc.php` (DB 연결 정보)
- [x] `webmail/REGISTRATION_SETUP.sql` (DB 이름, 주석)
- [x] `MAIL_SERVER_SETUP.sql` (사용자명)
- [x] `website/api/submit_inquiry.php` (DB 계정)

### 4. DB 계정명 회사 맞춤 변경

#### 변경 전:
```
admin / Website@2025!      → ota_db
roundcube / Roundcube@2025! → roundcubemail
mailuser / MailUser@2025!  → mail_db
```

#### 변경 후:
```
webadmin / 1xINV!web2025    → ota_db
mailadmin / 1xINV!mail2025  → webmail_db
mailserver / 1xINV!smtp2025 → mail_db
```

### 5. 웹메일 계정 개성화
기존 단조로운 계정에서 개성있는 계정으로 변경:
- @ 앞에 . 제거
- 소문자와 숫자 조합
- 부서별 분류 (경영진 1, 개발 3, 영업 2, 인사 1, 재무 2, 고객지원 1)

---

## 📁 파일 구조

### 주요 문서 파일
```
1x_inv/
├── MASTER_DEPLOY_GUIDE.md       ⭐ [NEW] 통합 배포 가이드
├── ACCOUNTS.txt                  ⭐ [NEW] 계정 정보 (보안 주의!)
├── CHANGES_SUMMARY.md            ⭐ [NEW] 변경 사항 요약
├── QUICK_DEPLOY_GUIDE.md         🔄 [UPDATED] 빠른 배포 가이드
├── UBUNTU_SERVER_SETUP.md        📌 [참고용] 상세 설정 가이드
├── DEPLOY_UBUNTU.md              📌 [참고용] Ubuntu 배포
├── README_INTERNAL_WEBMAIL.md    📌 [참고용] 웹메일 개요
└── MAIL_SERVER_SETUP.sql         ⭐ [NEW] 메일 서버 계정 생성
```

### 데이터베이스 스크립트
```
1x_inv/
├── MAIL_SERVER_SETUP.sql         🔄 [UPDATED] mail_db 계정 생성
├── website/init-db.sql           ✅ [OK] ota_db 초기화
└── webmail/
    ├── REGISTRATION_SETUP.sql    🔄 [UPDATED] webmail_db 회원가입
    └── SQL/mysql.initial.sql     ✅ [OK] webmail_db 초기 스키마
```

### 설정 파일
```
1x_inv/
├── webmail/config/config.inc.php 🔄 [UPDATED] DB 연결 정보
└── website/api/submit_inquiry.php 🔄 [UPDATED] DB 계정
```

---

## 🔑 전체 계정 목록

### 데이터베이스 계정
| 사용자 | 비밀번호 | 데이터베이스 | 용도 |
|--------|----------|-------------|------|
| webadmin | 1xINV!web2025 | ota_db | Website |
| mailadmin | 1xINV!mail2025 | webmail_db | Webmail |
| mailserver | 1xINV!smtp2025 | mail_db | Mail Server |

### 웹메일 사용자 계정 (10개)
| # | 이메일 | 비밀번호 | 이름 | 부서 |
|---|--------|----------|------|------|
| 1 | ceo@1xinv.com | Leader!2025#boss | 대표이사 | 경영진 |
| 2 | devkim99@1xinv.com | codingK1m!dev | 김철수 | 개발팀 |
| 3 | junhyuk2@1xinv.com | jun2Park@dev | 박준혁 | 개발팀 |
| 4 | minji0developer@1xinv.com | minDev0!Choi | 최민지 | 개발팀 |
| 5 | sunny88@1xinv.com | saleS88lee! | 이태양 | 영업팀 |
| 6 | daeun77@1xinv.com | jeongDE77$ | 정다은 | 영업팀 |
| 7 | hrmanager25@1xinv.com | hrKang25!boss | 강혜린 | 인사팀 |
| 8 | finance01@1xinv.com | money01Yoon$ | 윤서준 | 재무팀 |
| 9 | sohee93@1xinv.com | han93Finance# | 한소희 | 재무팀 |
| 10 | support@1xinv.com | help1xinv!2025 | 고객지원팀 | 고객서비스 |

---

## 🚀 배포 순서

### 방법 1: 자동 설치 (권장)
```bash
# MASTER_DEPLOY_GUIDE.md의 자동 설치 스크립트 사용
sudo /tmp/1xinv_full_install.sh
```

### 방법 2: 빠른 설치
```bash
# QUICK_DEPLOY_GUIDE.md의 스크립트 사용
sudo /tmp/1xinv_install.sh
```

### 방법 3: 수동 설치
```bash
# MASTER_DEPLOY_GUIDE.md 또는 UBUNTU_SERVER_SETUP.md 참고
# 단계별로 직접 설치
```

---

## ⚠️ 중요 변경 사항

### DB 연결 정보 변경 필요
모든 설정 파일에서 DB 연결 정보가 변경되었습니다:

**config.inc.php:**
```php
$config['db_dsnw'] = 'mysql://mailadmin:1xINV!mail2025@localhost/webmail_db';
```

**submit_inquiry.php:**
```php
$db_user = 'webadmin';
$db_pass = '1xINV!web2025';
```

**dovecot-sql.conf.ext:**
```conf
connect = host=localhost dbname=mail_db user=mailserver password=1xINV!smtp2025
```

### SQL 스크립트 실행 순서
```bash
# 1. Website DB
mysql -u webadmin -p'1xINV!web2025' ota_db < website/init-db.sql

# 2. Webmail DB
mysql -u mailadmin -p'1xINV!mail2025' webmail_db < webmail/SQL/mysql.initial.sql
mysql -u mailadmin -p'1xINV!mail2025' webmail_db < webmail/REGISTRATION_SETUP.sql

# 3. Mail Server DB
mysql -u mailserver -p'1xINV!smtp2025' mail_db < MAIL_SERVER_SETUP.sql
```

---

## 📝 다음 단계

### 배포 전 필수 작업
1. [ ] `ACCOUNTS.txt` 백업 및 보안 저장
2. [ ] 암호화 키 생성 (config.inc.php)
3. [ ] IP 제한 설정 (.htaccess)
4. [ ] installer 디렉토리 삭제

### 배포 후 확인 사항
1. [ ] 모든 서비스 정상 작동 확인
2. [ ] 웹사이트 접속 테스트
3. [ ] 웹메일 로그인 테스트 (10개 계정)
4. [ ] IMAP/SMTP 테스트
5. [ ] 로그 파일 확인

---

## 🔐 보안 주의사항

### 절대 하면 안 되는 것
❌ `ACCOUNTS.txt` 파일을 Git에 커밋
❌ 기본 비밀번호 그대로 사용
❌ 외부망에서 접근 허용
❌ installer 디렉토리 미삭제
❌ 로그 파일 미확인

### 반드시 해야 하는 것
✅ 모든 비밀번호 변경 (운영 환경)
✅ HTTPS 적용
✅ 방화벽 활성화
✅ 정기적인 보안 업데이트
✅ 접근 로그 모니터링

---

## 📞 문제 발생 시

1. **MASTER_DEPLOY_GUIDE.md** 문제 해결 섹션 참조
2. 로그 파일 확인:
   - `/var/log/apache2/webmail_error.log`
   - `/var/log/mail.log`
   - `/var/log/syslog`
3. 서비스 상태 확인: `sudo systemctl status SERVICE_NAME`
4. IT 헬프데스크: support@1xinv.com

---

**작성자**: 1xINV IT팀
**버전**: 1.0
**최종 수정일**: 2025-01-27

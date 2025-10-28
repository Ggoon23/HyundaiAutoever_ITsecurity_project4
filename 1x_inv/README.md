# 1xINV 통합 시스템

> **Website + Webmail + Mail Server** 완전 통합 솔루션

---

## 🎯 시스템 구성

```
1xINV System
├── 공식 웹사이트 (Website)
│   └── 제품 소개, 문의 폼, 공지사항
│
├── 사내 웹메일 (Webmail)
│   └── 이메일 시스템 + 회원가입 + 승인 관리
│
└── 메일 서버 (Mail Server)
    └── Postfix (SMTP) + Dovecot (IMAP)
```

---

## 📚 문서 구조

| 파일 | 설명 | 용도 |
|------|------|------|
| **[MASTER_DEPLOY_GUIDE.md](MASTER_DEPLOY_GUIDE.md)** | 📘 **통합 배포 가이드** | 시스템 설치의 모든 것 |
| **[ACCOUNTS.txt](ACCOUNTS.txt)** | 🔑 계정 정보 | DB + 웹메일 계정 (보안 주의!) |
| **[CHANGES_SUMMARY.md](CHANGES_SUMMARY.md)** | 📝 변경사항 요약 | 작업 내역 및 변경점 |
| **[MAIL_SERVER_SETUP.sql](MAIL_SERVER_SETUP.sql)** | 💾 메일 서버 스크립트 | 가상 사용자 생성 |

---

## 🚀 빠른 시작

### 1단계: 시스템 설치

```bash
# MASTER_DEPLOY_GUIDE.md 참조
# 자동 설치 스크립트로 약 15분 소요
```

### 2단계: 접속

**공식 웹사이트**
```
http://1xinv.local
```

**사내 웹메일**
```
http://webmail.1xinv.local
```

### 3단계: 로그인

```
계정 예시:
이메일: devkim99@1xinv.com
비밀번호: codingK1m!dev

전체 계정: ACCOUNTS.txt 참조
```

---

## 🔐 계정 정보 (요약)

### 데이터베이스
```
webadmin    / 1xINV!web2025    → ota_db
mailadmin   / 1xINV!mail2025   → webmail_db
mailserver  / 1xINV!smtp2025   → mail_db
```

### 웹메일 사용자 (10개)
- **경영진** (1): ceo@1xinv.com
- **개발팀** (3): devkim99, junhyuk2, minji0developer
- **영업팀** (2): sunny88, daeun77
- **인사팀** (1): hrmanager25
- **재무팀** (2): finance01, sohee93
- **고객지원** (1): support

**전체 비밀번호: [ACCOUNTS.txt](ACCOUNTS.txt) 참조**

---

## 📁 디렉토리 구조

```
1x_inv/
├── README.md                    # 📖 이 파일
├── MASTER_DEPLOY_GUIDE.md       # 📘 완전 배포 가이드
├── CHANGES_SUMMARY.md           # 📝 변경사항 요약
├── ACCOUNTS.txt                 # 🔑 계정 정보 (민감!)
├── MAIL_SERVER_SETUP.sql        # 💾 메일 서버 스크립트
│
├── website/                     # 🌐 공식 웹사이트
│   ├── index.html
│   ├── api/
│   │   └── submit_inquiry.php
│   └── init-db.sql
│
└── webmail/                     # 📧 사내 웹메일
    ├── config/
    │   └── config.inc.php
    ├── plugins/
    │   └── user_registration/
    ├── SQL/
    │   └── mysql.initial.sql
    └── REGISTRATION_SETUP.sql
```

---

## ⚠️ 보안 주의사항

### ❌ 절대 하면 안 되는 것
- ACCOUNTS.txt를 Git에 커밋
- 기본 비밀번호 그대로 사용
- 외부망에서 접근 허용
- installer 디렉토리 미삭제

### ✅ 반드시 해야 하는 것
- 암호화 키 생성 (config.inc.php)
- IP 제한 설정 (.htaccess)
- HTTPS 적용 (운영 환경)
- 정기적인 보안 업데이트

---

## 🛠️ 기술 스택

### Backend
- **PHP** 7.4+
- **MySQL/MariaDB** 10.3+
- **Apache** 2.4+
- **Postfix** (SMTP)
- **Dovecot** (IMAP)

### Frontend
- **HTML5/CSS3**
- **JavaScript**
- **Bootstrap** (Website)

### Framework
- **Webmail**: 오픈소스 기반 (브랜딩 제거됨)
- **Website**: 커스텀 PHP

---

## 📞 지원

### 문제 발생 시
1. **[MASTER_DEPLOY_GUIDE.md](MASTER_DEPLOY_GUIDE.md)** 문제 해결 섹션 참조
2. 로그 확인: `/var/log/apache2/`, `/var/log/mail.log`
3. IT 헬프데스크: support@1xinv.com

### 문서 위치
- **전체 설치**: MASTER_DEPLOY_GUIDE.md
- **계정 정보**: ACCOUNTS.txt
- **변경 내역**: CHANGES_SUMMARY.md

---

## 📊 시스템 사양

### 최소 요구사항
- Ubuntu 20.04 LTS
- 2 Core CPU
- 4GB RAM
- 20GB Disk

### 권장 사양
- Ubuntu 22.04 LTS
- 4 Core CPU
- 8GB RAM
- 50GB SSD

### 네트워크
- 고정 IP (내부망)
- 포트: 80, 443, 25, 143, 587

---

## 📝 라이선스

**1xINV Internal Use Only**
- 사내 전용 시스템
- 외부 배포 금지
- 기밀 정보 포함

---

## 🔄 업데이트 로그

### v1.0 (2025-01-27)
- ✅ 통합 배포 시스템 구축
- ✅ Roundcube 브랜딩 제거
- ✅ DB 계정 회사 맞춤
- ✅ 웹메일 계정 10개 생성
- ✅ 완전 통합 문서 작성

---

**작성**: 1xINV IT팀
**최종 수정**: 2025-01-27
**버전**: 1.0

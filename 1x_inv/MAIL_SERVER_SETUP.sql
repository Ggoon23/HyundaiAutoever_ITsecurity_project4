-- ================================================================
-- Mail Server (Dovecot/Postfix) Setup
-- 1xINV 메일 서버 계정 생성
-- ================================================================
--
-- 사용법:
-- sudo mysql -u mailserver -p mail_db < MAIL_SERVER_SETUP.sql
--
-- ================================================================

USE mail_db;

-- ================================================================
-- 1. virtual_users 테이블 생성
-- ================================================================
CREATE TABLE IF NOT EXISTS virtual_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    department VARCHAR(100),
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- 2. 10개 계정 추가 (평문 비밀번호 - 테스트용)
-- ================================================================
-- 주의: default_pass_scheme = PLAIN 설정 시 사용
-- 운영 환경에서는 아래 3번 섹션의 해시 버전 사용 권장!

INSERT INTO virtual_users (email, password, name, department) VALUES
('ceo@1xinv.com', 'Leader!2025#boss', '대표이사', '경영진'),
('devkim99@1xinv.com', 'codingK1m!dev', '김철수', '개발팀'),
('junhyuk2@1xinv.com', 'jun2Park@dev', '박준혁', '개발팀'),
('minji0developer@1xinv.com', 'minDev0!Choi', '최민지', '개발팀'),
('sunny88@1xinv.com', 'saleS88lee!', '이태양', '영업팀'),
('daeun77@1xinv.com', 'jeongDE77$', '정다은', '영업팀'),
('hrmanager25@1xinv.com', 'hrKang25!boss', '강혜린', '인사팀'),
('finance01@1xinv.com', 'money01Yoon$', '윤서준', '재무팀'),
('sohee93@1xinv.com', 'han93Finance#', '한소희', '재무팀'),
('support@1xinv.com', 'help1xinv!2025', '고객지원팀', '고객서비스')
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    name = VALUES(name),
    department = VALUES(department);

-- ================================================================
-- 3. 비밀번호 해시화 (보안 강화 - 운영 환경 권장)
-- ================================================================
--
-- 사용 방법:
-- 1. dovecot-sql.conf.ext에서 설정 변경:
--    default_pass_scheme = SHA512-CRYPT
--
-- 2. 각 비밀번호의 해시 생성:
--    doveadm pw -s SHA512-CRYPT -p 'Leader!2025#boss'
--    doveadm pw -s SHA512-CRYPT -p 'codingK1m!dev'
--    doveadm pw -s SHA512-CRYPT -p 'jun2Park@dev'
--    ... (나머지 비밀번호도 동일하게)
--
-- 3. 아래 UPDATE 쿼리 실행 (해시값은 위에서 생성한 값 사용):
--
-- UPDATE virtual_users SET password = '{SHA512-CRYPT}$6$...' WHERE email = 'ceo@1xinv.com';
-- UPDATE virtual_users SET password = '{SHA512-CRYPT}$6$...' WHERE email = 'devkim99@1xinv.com';
-- UPDATE virtual_users SET password = '{SHA512-CRYPT}$6$...' WHERE email = 'junhyuk2@1xinv.com';
-- UPDATE virtual_users SET password = '{SHA512-CRYPT}$6$...' WHERE email = 'minji0developer@1xinv.com';
-- UPDATE virtual_users SET password = '{SHA512-CRYPT}$6$...' WHERE email = 'sunny88@1xinv.com';
-- UPDATE virtual_users SET password = '{SHA512-CRYPT}$6$...' WHERE email = 'daeun77@1xinv.com';
-- UPDATE virtual_users SET password = '{SHA512-CRYPT}$6$...' WHERE email = 'hrmanager25@1xinv.com';
-- UPDATE virtual_users SET password = '{SHA512-CRYPT}$6$...' WHERE email = 'finance01@1xinv.com';
-- UPDATE virtual_users SET password = '{SHA512-CRYPT}$6$...' WHERE email = 'sohee93@1xinv.com';
-- UPDATE virtual_users SET password = '{SHA512-CRYPT}$6$...' WHERE email = 'support@1xinv.com';

-- ================================================================
-- 4. 확인 쿼리
-- ================================================================
SELECT
    email,
    name,
    department,
    active,
    created_at
FROM virtual_users
ORDER BY department, email;

-- 통계
SELECT
    department,
    COUNT(*) as user_count
FROM virtual_users
WHERE active = 1
GROUP BY department
ORDER BY user_count DESC;

-- ================================================================
-- 완료 메시지
-- ================================================================
SELECT '✅ 메일 서버 계정 설정 완료!' as status;
SELECT CONCAT('총 계정 수: ', COUNT(*), '개') as total FROM virtual_users;
SELECT CONCAT('활성 계정: ', COUNT(*), '개') as active FROM virtual_users WHERE active = 1;

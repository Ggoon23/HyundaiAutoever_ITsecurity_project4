-- ================================================================
-- User Registration Plugin - Database Setup
-- 1xINV 사내 웹메일 회원가입 시스템
-- CVE-2025-49113 테스트 환경
-- ================================================================

USE roundcubemail;

-- ================================================================
-- 1. registration_pending 테이블 생성
-- ================================================================
-- 회원가입 신청 및 승인 상태 관리
CREATE TABLE IF NOT EXISTS registration_pending (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'users 테이블의 user_id (FK)',
    email VARCHAR(255) NOT NULL COMMENT '사용자 이메일',
    password_hash VARCHAR(255) NOT NULL COMMENT '비밀번호 해시 (bcrypt)',
    name VARCHAR(100) NOT NULL COMMENT '사용자 이름',
    department VARCHAR(100) NOT NULL COMMENT '부서',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' COMMENT '승인 상태',
    created_at DATETIME NOT NULL COMMENT '가입 신청일',
    approved_at DATETIME NULL COMMENT '승인/거부 처리일',
    approved_by VARCHAR(255) NULL COMMENT '승인 처리자 이메일',
    reject_reason TEXT NULL COMMENT '거부 사유',

    UNIQUE KEY unique_user (user_id),
    UNIQUE KEY unique_email (email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='회원가입 승인 대기 목록';

-- ================================================================
-- 2. 기존 10개 계정을 승인된 상태로 등록
-- ================================================================
-- ACCOUNTS.md에 정의된 10개 계정은 자동 승인 처리

-- 대표 계정
INSERT INTO registration_pending (user_id, email, password_hash, name, department, status, created_at, approved_at, approved_by)
SELECT user_id, username, '$2y$10$auto.approved.hash', '대표이사', '경영진', 'approved', NOW(), NOW(), 'system@1xinv.com'
FROM users WHERE username = 'ceo@1xinv.com'
ON DUPLICATE KEY UPDATE status = 'approved';

-- 직원 계정 (8명)
INSERT INTO registration_pending (user_id, email, password_hash, name, department, status, created_at, approved_at, approved_by)
SELECT user_id, username, '$2y$10$auto.approved.hash', '김철수', '개발팀', 'approved', NOW(), NOW(), 'system@1xinv.com'
FROM users WHERE username = 'kim.chulsu@1xinv.com'
ON DUPLICATE KEY UPDATE status = 'approved';

INSERT INTO registration_pending (user_id, email, password_hash, name, department, status, created_at, approved_at, approved_by)
SELECT user_id, username, '$2y$10$auto.approved.hash', '이영희', '기획팀', 'approved', NOW(), NOW(), 'system@1xinv.com'
FROM users WHERE username = 'lee.younghee@1xinv.com'
ON DUPLICATE KEY UPDATE status = 'approved';

INSERT INTO registration_pending (user_id, email, password_hash, name, department, status, created_at, approved_at, approved_by)
SELECT user_id, username, '$2y$10$auto.approved.hash', '박민수', '영업팀', 'approved', NOW(), NOW(), 'system@1xinv.com'
FROM users WHERE username = 'park.minsu@1xinv.com'
ON DUPLICATE KEY UPDATE status = 'approved';

INSERT INTO registration_pending (user_id, email, password_hash, name, department, status, created_at, approved_at, approved_by)
SELECT user_id, username, '$2y$10$auto.approved.hash', '최지혜', '마케팅팀', 'approved', NOW(), NOW(), 'system@1xinv.com'
FROM users WHERE username = 'choi.jihye@1xinv.com'
ON DUPLICATE KEY UPDATE status = 'approved';

INSERT INTO registration_pending (user_id, email, password_hash, name, department, status, created_at, approved_at, approved_by)
SELECT user_id, username, '$2y$10$auto.approved.hash', '정우진', '기술지원팀', 'approved', NOW(), NOW(), 'system@1xinv.com'
FROM users WHERE username = 'jung.woojin@1xinv.com'
ON DUPLICATE KEY UPDATE status = 'approved';

INSERT INTO registration_pending (user_id, email, password_hash, name, department, status, created_at, approved_at, approved_by)
SELECT user_id, username, '$2y$10$auto.approved.hash', '강미라', '인사팀', 'approved', NOW(), NOW(), 'system@1xinv.com'
FROM users WHERE username = 'kang.mira@1xinv.com'
ON DUPLICATE KEY UPDATE status = 'approved';

INSERT INTO registration_pending (user_id, email, password_hash, name, department, status, created_at, approved_at, approved_by)
SELECT user_id, username, '$2y$10$auto.approved.hash', '윤성호', '재무팀', 'approved', NOW(), NOW(), 'system@1xinv.com'
FROM users WHERE username = 'yoon.seongho@1xinv.com'
ON DUPLICATE KEY UPDATE status = 'approved';

INSERT INTO registration_pending (user_id, email, password_hash, name, department, status, created_at, approved_at, approved_by)
SELECT user_id, username, '$2y$10$auto.approved.hash', '한수정', '연구개발팀', 'approved', NOW(), NOW(), 'system@1xinv.com'
FROM users WHERE username = 'han.sujeong@1xinv.com'
ON DUPLICATE KEY UPDATE status = 'approved';

-- 고객지원 계정
INSERT INTO registration_pending (user_id, email, password_hash, name, department, status, created_at, approved_at, approved_by)
SELECT user_id, username, '$2y$10$auto.approved.hash', '고객지원팀', '고객서비스', 'approved', NOW(), NOW(), 'system@1xinv.com'
FROM users WHERE username = 'support@1xinv.com'
ON DUPLICATE KEY UPDATE status = 'approved';

-- ================================================================
-- 3. CVE 테스트용 계정
-- ================================================================
-- 테스트 계정은 별도로 직접 생성하세요.
-- 회원가입 페이지: http://webmail.1xinv.local/?_task=login&_action=plugin.user_registration
--
-- 예시:
-- - 웹 브라우저로 회원가입 페이지 접속
-- - @1xinv.com 도메인으로 계정 생성
-- - 로그인하면 "승인 대기 중" 페이지가 표시됨

-- ================================================================
-- 4. 통계 뷰 생성 (옵션)
-- ================================================================
CREATE OR REPLACE VIEW v_registration_stats AS
SELECT
    COUNT(*) as total_users,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
    AVG(TIMESTAMPDIFF(HOUR, created_at, COALESCE(approved_at, NOW()))) as avg_approval_hours
FROM registration_pending;

-- ================================================================
-- 5. 확인 쿼리
-- ================================================================

-- 전체 사용자 목록
SELECT
    r.id,
    r.email,
    r.name,
    r.department,
    r.status,
    r.created_at,
    r.approved_at
FROM registration_pending r
ORDER BY r.created_at DESC;

-- 승인 대기 목록
SELECT
    email,
    name,
    department,
    TIMESTAMPDIFF(HOUR, created_at, NOW()) as hours_waiting
FROM registration_pending
WHERE status = 'pending'
ORDER BY created_at;

-- 통계
SELECT * FROM v_registration_stats;

-- ================================================================
-- 완료 메시지
-- ================================================================
SELECT '✅ User Registration Plugin 데이터베이스 설정 완료!' as status;
SELECT CONCAT('총 사용자: ', COUNT(*), '명') as user_count FROM registration_pending;
SELECT CONCAT('승인 대기: ', COUNT(*), '명') as pending_count FROM registration_pending WHERE status = 'pending';
SELECT CONCAT('승인 완료: ', COUNT(*), '명') as approved_count FROM registration_pending WHERE status = 'approved';

-- ================================================================
-- 비밀번호 해시 생성 참고
-- ================================================================
-- PHP에서 bcrypt 해시 생성:
-- php -r "echo password_hash('your_password', PASSWORD_BCRYPT);"
--
-- 현재 사용된 해시:
-- hacker123 → $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- test1234  → $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

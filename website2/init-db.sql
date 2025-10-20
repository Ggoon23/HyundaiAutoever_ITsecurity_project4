-- Website Database Initialization
-- Create inquiries table for contact form

CREATE DATABASE IF NOT EXISTS ota_db;
USE ota_db;

-- Inquiries table for website contact form
CREATE TABLE IF NOT EXISTS inquiries (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    company VARCHAR(200),
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    category ENUM('product', 'technical', 'sales', 'partnership', 'other') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    image_path VARCHAR(512),
    is_locked BOOLEAN DEFAULT FALSE,
    password VARCHAR(255),
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    created_at DATETIME(6) DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data
INSERT INTO inquiries (name, company, email, phone, category, subject, message, is_locked, password, status, created_at) VALUES
('김철수', '현대자동차', 'kim@hyundai.com', '010-1234-5678', 'product', '제품 문의 드립니다',
'안녕하세요. 현대자동차 Connected Car팀 김철수입니다.

귀사의 OTA 펌웨어 업데이트 솔루션에 대해 문의드립니다.

1. OTA 업데이트 방식
   - 전체 펌웨어 업데이트인가요, 아니면 차분 업데이트를 지원하나요?
   - 업데이트 중 실패 시 롤백 기능이 있나요?

2. 보안 관련
   - 펌웨어 무결성 검증은 어떤 방식으로 진행되나요?
   - 암호화 전송을 지원하나요?

3. 차량 적용
   - 다양한 ECU에 동시 배포가 가능한가요?
   - 차량이 주행 중일 때는 어떻게 처리되나요?

자세한 기술 자료와 데모 일정 조율 부탁드립니다.
감사합니다.',
FALSE, NULL, 'completed', '2025-09-10 09:30:00'),

('이영희', 'SK텔레콤', 'lee@sktelecom.com', '010-2345-6789', 'technical', '기술 지원 요청',
'SF텔레콤 IoT사업부 이영희 과장입니다.

현재 진행 중인 커넥티드카 프로젝트에서 귀사의 내비게이션 시스템을 검토 중입니다.

[문의 사항]
- LTE/5G 통신 모듈 연동 가능 여부
- 실시간 교통정보 API 연동 방법
- 클라우드 서버 요구사항
- 예상 데이터 사용량

긴급히 기술 지원이 필요한 상황이라 빠른 회신 부탁드립니다.

※ 본 문의는 사내 기밀사항이 포함되어 있어 비공개 처리하였습니다.',
TRUE, '1234', 'in_progress', '2025-09-25 14:20:00'),

('박민수', '삼성전자', 'park@samsung.com', '010-3456-7890', 'partnership', '파트너십 제안',
'삼상전자 Automotive Solution팀 박민수 부장입니다.

차세대 내비게이션 시스템 공동 개발 건으로 연락드립니다.

[제안 개요]
• 대상: 2026년 출시 예정 차세대 내비게이션
• 협력 분야: OTA 펌웨어 업데이트 시스템 통합
• 예상 물량: 연간 50만 대
• 개발 일정: 2025년 Q2 착수

[미팅 안건]
1. 기술 로드맵 공유
2. 공동 개발 방안 논의
3. 계약 조건 협의
4. 일정 수립

다음 주 중 미팅 가능하시면 일정 조율 부탁드립니다.
제안서는 별도로 메일 발송드리겠습니다.

감사합니다.',
FALSE, NULL, 'pending', '2025-10-15 11:45:00');

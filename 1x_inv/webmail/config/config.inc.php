<?php

/*
 +-----------------------------------------------------------------------+
 | Local configuration for Company Internal Webmail                      |
 | 회사 내부망 전용 웹메일 설정                                               |
 +-----------------------------------------------------------------------+
*/

$config = [];

// ----------------------------------
// 데이터베이스 설정 (Database)
// ----------------------------------
// MySQL 데이터베이스 연결 설정
$config['db_dsnw'] = 'mysql://mailadmin:1xINV!mail2025@localhost/webmail_db';

// 데이터베이스 접두사 (선택사항)
$config['db_prefix'] = '';

// ----------------------------------
// IMAP 메일 서버 설정
// ----------------------------------
// 회사 내부 IMAP 서버 주소
// 예: 'mail.company.local:143' 또는 'mail.company.local:993' (SSL)
$config['imap_host'] = 'mail.company.local:143';

// IMAP 연결 옵션
$config['imap_conn_options'] = [
    'ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
    ],
];

// IMAP 캐시 설정
$config['imap_cache'] = 'db';
$config['messages_cache'] = 'db';

// ----------------------------------
// SMTP 발송 서버 설정
// ----------------------------------
// 회사 내부 SMTP 서버 주소
$config['smtp_host'] = 'mail.company.local:25';

// SMTP 인증 설정 (현재 사용자 계정 정보 사용)
$config['smtp_user'] = '%u';
$config['smtp_pass'] = '%p';

// SMTP 연결 옵션
$config['smtp_conn_options'] = [
    'ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
    ],
];

// ----------------------------------
// 보안 설정
// ----------------------------------
// 세션 암호화 키 (24자 필수 - 반드시 변경하세요!)
$config['des_key'] = 'YOUR-RANDOM-24CHAR-KEY!';

// 내부망 전용 - IP 제한 설정
// Apache/Nginx에서도 설정 권장
$config['login_rate_limit'] = 5; // 5분당 최대 로그인 시도 횟수

// 세션 수명 설정 (분 단위)
$config['session_lifetime'] = 30;

// CSRF 보호 활성화
$config['request_token_ttl'] = 300;

// ----------------------------------
// 내부망 접근 제한
// ----------------------------------
// 허용된 내부 IP 대역 (필요시 주석 해제 및 수정)
// $config['client_ip_check'] = true;

// ----------------------------------
// 회사 정보 설정
// ----------------------------------
// 서비스 이름
$config['product_name'] = '1xINV 사내 웹메일';

// 지원 URL (IT 부서 연락처)
$config['support_url'] = 'mailto:support@1xinv.com';

// 로고 표시 설정
$config['skin_logo'] = 'skins/elastic/images/logo.svg';
$config['display_product_info'] = 1;

// 기본 언어
$config['language'] = 'ko_KR';

// 기본 시간대
$config['timezone'] = 'Asia/Seoul';

// ----------------------------------
// 사용자 인터페이스
// ----------------------------------
// 스킨 테마
$config['skin'] = 'elastic';

// 로고 이미지 (회사 로고로 변경 가능)
// $config['skin_logo'] = '/path/to/company-logo.png';

// ----------------------------------
// 플러그인 설정
// ----------------------------------
$config['plugins'] = [
    'archive',              // 메일 아카이브
    'zipdownload',          // 첨부파일 일괄 다운로드
    'markasjunk',          // 스팸 표시
    'password',            // 비밀번호 변경 (옵션)
    'newmail_notifier',    // 신규 메일 알림
    'user_registration',   // 회원가입 및 승인 시스템 (CVE-2025-49113 테스트용)
];

// ----------------------------------
// 첨부파일 설정
// ----------------------------------
// 최대 첨부파일 크기 (25MB)
$config['max_message_size'] = '25M';

// 파일 업로드 크기
$config['max_filesize'] = '25M';

// ----------------------------------
// 로그 설정
// ----------------------------------
// 로그 디렉토리
$config['log_dir'] = 'logs/';

// 로그 레벨 (4자리: errors, warnings, sql, logins)
$config['log_logins'] = true;
$config['log_session'] = true;

// SQL 쿼리 로깅 (개발 환경에서만)
$config['sql_debug'] = false;

// ----------------------------------
// 기타 설정
// ----------------------------------
// 자동 로그아웃 활성화
$config['refresh_interval'] = 60;

// HTML 편집기 사용
$config['htmleditor'] = 1;

// 메일 미리보기
$config['preview_pane'] = true;

// 기본 폴더
$config['create_default_folders'] = true;

// HTTPS 강제 (내부망에서는 옵션)
// $config['force_https'] = true;

// IP 주소 기록
$config['log_driver'] = 'file';

// ----------------------------------
// 내부망 보안 강화 옵션
// ----------------------------------
// 외부 이미지 차단
$config['show_images'] = 0;

// 외부 리소스 차단
$config['enable_spellcheck'] = true;

// 안전한 URL만 허용
$config['safe_urls'] = ['http://company.local', 'https://company.local'];

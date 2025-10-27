<?php
/**
 * User Registration Plugin
 *
 * 회원가입 시스템 - 인사팀 승인 필요
 * CVE-2025-49113 테스트를 위한 플러그인
 *
 * - 사용자는 자유롭게 회원가입 가능
 * - 로그인은 가능하지만 승인 전까지는 기능 제한 (읽기 전용)
 * - 인사팀(kang.mira@1xinv.com)이 승인해야만 전체 기능 사용 가능
 *
 * @version 1.0
 * @author 1xINV IT Security Team
 * @license GPL-3.0
 */

class user_registration extends rcube_plugin
{
    public $task = '?(?!login|logout).*';
    private $rc;

    public function init()
    {
        $this->rc = rcmail::get_instance();

        // 로그인 폼에 회원가입 링크 추가
        $this->add_hook('template_object_loginform', array($this, 'add_registration_link'));

        // 회원가입 액션 등록
        $this->register_action('plugin.user_registration', array($this, 'registration_form'));
        $this->register_action('plugin.user_registration_submit', array($this, 'registration_submit'));

        // 승인 관리 페이지 (인사팀 전용)
        $this->register_action('plugin.user_approval', array($this, 'approval_page'));
        $this->register_action('plugin.user_approve', array($this, 'approve_user'));
        $this->register_action('plugin.user_reject', array($this, 'reject_user'));

        // 사용자 권한 체크 (승인되지 않은 사용자 기능 제한)
        $this->add_hook('startup', array($this, 'check_user_status'));

        // 템플릿 및 스타일 추가
        $this->include_stylesheet($this->local_skin_path() . '/user_registration.css');
    }

    /**
     * 로그인 폼에 회원가입 링크 추가
     */
    public function add_registration_link($args)
    {
        $registration_link = html::a(array(
            'href' => $this->rc->url(array('_action' => 'plugin.user_registration')),
            'class' => 'registration-link'
        ), '회원가입 (Sign Up)');

        $args['content'] .= html::div(array('class' => 'registration-link-wrapper'), $registration_link);

        return $args;
    }

    /**
     * 회원가입 폼 표시
     */
    public function registration_form()
    {
        $this->rc->output->set_pagetitle('회원가입 - 1xINV 사내 웹메일');
        $this->rc->output->send('user_registration.registration_form');
    }

    /**
     * 회원가입 처리
     */
    public function registration_submit()
    {
        $email = rcube_utils::get_input_string('_email', rcube_utils::INPUT_POST);
        $password = rcube_utils::get_input_string('_password', rcube_utils::INPUT_POST);
        $password_confirm = rcube_utils::get_input_string('_password_confirm', rcube_utils::INPUT_POST);
        $name = rcube_utils::get_input_string('_name', rcube_utils::INPUT_POST);
        $department = rcube_utils::get_input_string('_department', rcube_utils::INPUT_POST);

        // 유효성 검사
        $errors = array();

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '유효한 이메일 주소를 입력하세요.';
        }

        if (!preg_match('/@1xinv\.com$/', $email)) {
            $errors[] = '1xINV 도메인(@1xinv.com)만 가입 가능합니다.';
        }

        if (empty($password) || strlen($password) < 6) {
            $errors[] = '비밀번호는 최소 6자 이상이어야 합니다.';
        }

        if ($password !== $password_confirm) {
            $errors[] = '비밀번호가 일치하지 않습니다.';
        }

        if (empty($name)) {
            $errors[] = '이름을 입력하세요.';
        }

        if (empty($department)) {
            $errors[] = '부서를 입력하세요.';
        }

        // 중복 체크
        if (empty($errors)) {
            $db = $this->rc->get_dbh();

            // users 테이블에서 중복 확인
            $query = "SELECT user_id FROM users WHERE username = ?";
            $result = $db->query($query, $email);

            if ($db->num_rows($result) > 0) {
                $errors[] = '이미 등록된 이메일 주소입니다.';
            }
        }

        // 에러가 있으면 폼으로 돌아가기
        if (!empty($errors)) {
            $this->rc->output->show_message(implode('<br>', $errors), 'error');
            $this->rc->output->send('user_registration.registration_form');
            return;
        }

        // 사용자 등록
        $db = $this->rc->get_dbh();

        // 1. users 테이블에 삽입 (Roundcube 기본 테이블)
        $query = "INSERT INTO users (username, mail_host, language, created) VALUES (?, ?, ?, NOW())";
        $result = $db->query($query, $email, 'localhost', 'ko_KR');

        if (!$result) {
            $this->rc->output->show_message('회원가입 중 오류가 발생했습니다.', 'error');
            $this->rc->output->send('user_registration.registration_form');
            return;
        }

        $user_id = $db->insert_id();

        // 2. registration_pending 테이블에 추가 정보 저장
        $this->create_registration_table();

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $query = "INSERT INTO registration_pending
                  (user_id, email, password_hash, name, department, status, created_at)
                  VALUES (?, ?, ?, ?, ?, 'pending', NOW())";

        $result = $db->query($query, $user_id, $email, $password_hash, $name, $department);

        if (!$result) {
            // 롤백: users 테이블에서 삭제
            $db->query("DELETE FROM users WHERE user_id = ?", $user_id);
            $this->rc->output->show_message('회원가입 중 오류가 발생했습니다.', 'error');
            $this->rc->output->send('user_registration.registration_form');
            return;
        }

        // 3. 메일 서버 DB에도 사용자 추가 (비활성 상태)
        $this->add_to_mail_server($email, $password, $name, $department);

        // 성공 메시지
        $this->rc->output->show_message(
            '회원가입이 완료되었습니다!<br>' .
            '인사팀의 승인 후 전체 기능을 사용하실 수 있습니다.<br>' .
            '승인 전에는 로그인만 가능합니다.',
            'confirmation'
        );

        // 로그인 페이지로 리다이렉트
        $this->rc->output->redirect(array('_task' => 'login'));
    }

    /**
     * 사용자 상태 확인 및 기능 제한
     */
    public function check_user_status($args)
    {
        if (!isset($_SESSION['user_id'])) {
            return $args;
        }

        $user_id = $_SESSION['user_id'];
        $db = $this->rc->get_dbh();

        // 승인 상태 확인
        $query = "SELECT status, name, department FROM registration_pending WHERE user_id = ?";
        $result = $db->query($query, $user_id);

        if ($row = $db->fetch_assoc($result)) {
            $status = $row['status'];

            // 승인 대기 중인 경우
            if ($status === 'pending') {
                $_SESSION['user_status'] = 'pending';
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['user_department'] = $row['department'];

                // 완전 차단 모드 활성화 (로그인만 가능)
                $this->enable_lockdown_mode();

                // 경고 메시지 표시
                $this->rc->output->show_message(
                    '[승인 대기 중] 인사팀의 승인 후 전체 기능을 사용하실 수 있습니다. 현재는 로그인 상태만 유지됩니다.',
                    'warning',
                    null,
                    true,
                    -1
                );
            }
            // 거부된 경우
            else if ($status === 'rejected') {
                $this->rc->output->show_message('계정이 거부되었습니다. 관리자에게 문의하세요.', 'error');
                $this->rc->kill_session();
                $this->rc->output->redirect(array('_task' => 'login'));
            }
        }

        return $args;
    }

    /**
     * 완전 차단 모드 활성화
     * 승인되지 않은 사용자는 로그인 상태만 유지, 모든 기능 차단
     */
    private function enable_lockdown_mode()
    {
        // 모든 작업 차단 (메일, 설정, 주소록, 파일 업로드 등)
        $this->add_hook('message_before_send', array($this, 'block_action'));
        $this->add_hook('preferences_save', array($this, 'block_action'));
        $this->add_hook('contact_create', array($this, 'block_action'));
        $this->add_hook('contact_update', array($this, 'block_action'));
        $this->add_hook('contact_delete', array($this, 'block_action'));

        // 파일 업로드 차단
        $this->add_hook('attachment_upload', array($this, 'block_action'));

        // 메일 작성 차단
        $this->add_hook('message_compose', array($this, 'block_action'));

        // 메일 답장/전달 차단
        $this->add_hook('message_reply', array($this, 'block_action'));

        // 폴더 작업 차단
        $this->add_hook('folder_create', array($this, 'block_action'));
        $this->add_hook('folder_update', array($this, 'block_action'));
        $this->add_hook('folder_delete', array($this, 'block_action'));

        // UI 접근 제한 - 메일 작성 버튼 등 숨기기
        $this->add_hook('render_page', array($this, 'hide_ui_elements'));

        // 태스크 전환 제한 - mail, settings, addressbook 모두 차단
        $this->add_hook('startup', array($this, 'restrict_tasks'));
    }

    /**
     * 모든 작업 차단 (통합)
     */
    public function block_action($args)
    {
        if (isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'pending') {
            $args['abort'] = true;
            $this->rc->output->show_message(
                '승인 대기 중인 계정은 기능을 사용할 수 없습니다. 인사팀의 승인을 기다려주세요.',
                'error'
            );
        }
        return $args;
    }

    /**
     * UI 요소 숨기기 (버튼 등)
     */
    public function hide_ui_elements($args)
    {
        if (isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'pending') {
            // CSS로 버튼 및 UI 요소 숨기기
            $css = "
            <style>
            /* 승인 대기 중 - 모든 작업 버튼 숨기기 */
            #messagetoolbar,
            #compose,
            .button.compose,
            .button.reply,
            .button.reply-all,
            .button.forward,
            .button.delete,
            .button.markasjunk,
            .button.move,
            #uploadform,
            .attachmentlist .buttons,
            #settingstoolbar button,
            .contactoptions .buttons,
            #folderlist-content .contextmenu {
                display: none !important;
            }

            /* 승인 대기 배너 스타일 강화 */
            .boxwarning {
                background: #FFF3E0 !important;
                border: 2px solid #FF9800 !important;
                font-weight: bold !important;
                text-align: center !important;
                padding: 15px !important;
            }
            </style>
            ";

            $this->rc->output->add_footer($css);
        }

        return $args;
    }

    /**
     * 태스크 접근 제한
     */
    public function restrict_tasks($args)
    {
        if (isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'pending') {
            $allowed_tasks = array('login', 'logout');
            $current_task = $this->rc->task;

            // 허용되지 않은 태스크 접근 시 로그아웃 페이지로 리다이렉트
            if (!in_array($current_task, $allowed_tasks)) {
                $this->rc->output->show_message(
                    '승인 대기 중인 계정은 이 기능에 접근할 수 없습니다.',
                    'error'
                );

                // 빈 페이지 표시 (로그인은 유지)
                $this->rc->output->send('user_registration.pending_page');
                exit;
            }
        }

        return $args;
    }

    /**
     * 승인 관리 페이지 (인사팀 전용)
     */
    public function approval_page()
    {
        // 인사팀 권한 체크
        if (!$this->is_hr_team()) {
            $this->rc->output->show_message('접근 권한이 없습니다.', 'error');
            $this->rc->output->redirect(array('_task' => 'mail'));
            return;
        }

        $this->rc->output->set_pagetitle('회원 승인 관리');
        $this->rc->output->send('user_registration.approval_page');
    }

    /**
     * 사용자 승인
     */
    public function approve_user()
    {
        if (!$this->is_hr_team()) {
            $this->rc->output->show_message('접근 권한이 없습니다.', 'error');
            return;
        }

        $user_id = rcube_utils::get_input_string('_user_id', rcube_utils::INPUT_POST);
        $db = $this->rc->get_dbh();

        // 상태 업데이트
        $query = "UPDATE registration_pending SET status = 'approved', approved_at = NOW() WHERE user_id = ?";
        $result = $db->query($query, $user_id);

        if ($result) {
            // 메일 서버 DB에서도 활성화
            $query = "SELECT email FROM registration_pending WHERE user_id = ?";
            $result = $db->query($query, $user_id);

            if ($row = $db->fetch_assoc($result)) {
                $this->activate_mail_account($row['email']);
            }

            $this->rc->output->show_message('사용자가 승인되었습니다.', 'confirmation');
        } else {
            $this->rc->output->show_message('승인 처리 중 오류가 발생했습니다.', 'error');
        }

        $this->approval_page();
    }

    /**
     * 사용자 거부
     */
    public function reject_user()
    {
        if (!$this->is_hr_team()) {
            $this->rc->output->show_message('접근 권한이 없습니다.', 'error');
            return;
        }

        $user_id = rcube_utils::get_input_string('_user_id', rcube_utils::INPUT_POST);
        $db = $this->rc->get_dbh();

        $query = "UPDATE registration_pending SET status = 'rejected', approved_at = NOW() WHERE user_id = ?";
        $result = $db->query($query, $user_id);

        if ($result) {
            $this->rc->output->show_message('사용자가 거부되었습니다.', 'confirmation');
        } else {
            $this->rc->output->show_message('거부 처리 중 오류가 발생했습니다.', 'error');
        }

        $this->approval_page();
    }

    /**
     * 인사팀 권한 체크
     */
    private function is_hr_team()
    {
        $username = $_SESSION['username'];
        // 인사팀: kang.mira@1xinv.com
        return ($username === 'kang.mira@1xinv.com');
    }

    /**
     * registration_pending 테이블 생성
     */
    private function create_registration_table()
    {
        $db = $this->rc->get_dbh();

        $query = "CREATE TABLE IF NOT EXISTS registration_pending (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $db->query($query);
    }

    /**
     * 메일 서버 DB에 사용자 추가 (비활성 상태)
     */
    private function add_to_mail_server($email, $password, $name, $department)
    {
        // 별도 메일 DB 연결이 필요한 경우
        // 현재는 Roundcube DB만 사용
        // 실제 배포시 mail_db에 연결하여 virtual_users 테이블에 삽입
    }

    /**
     * 메일 계정 활성화
     */
    private function activate_mail_account($email)
    {
        // 메일 서버 DB에서 활성화
        // 실제 배포시 구현
    }
}

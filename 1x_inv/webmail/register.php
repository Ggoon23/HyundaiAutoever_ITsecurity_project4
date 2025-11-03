<?php
/**
 * 독립 회원가입 페이지
 * 1xINV 사내 웹메일 회원가입
 */

// Roundcube 초기화
define('INSTALL_PATH', __DIR__ . '/');
require_once INSTALL_PATH . 'program/include/iniset.php';

$rcmail = rcmail::get_instance();
$db = $rcmail->get_dbh();

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = rcube_utils::get_input_string('_email', rcube_utils::INPUT_POST);
    $password = rcube_utils::get_input_string('_password', rcube_utils::INPUT_POST);
    $password_confirm = rcube_utils::get_input_string('_password_confirm', rcube_utils::INPUT_POST);
    $name = rcube_utils::get_input_string('_name', rcube_utils::INPUT_POST);
    $department = rcube_utils::get_input_string('_department', rcube_utils::INPUT_POST);

    $errors = [];

    // 유효성 검사
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
        $errors[] = '부서를 선택하세요.';
    }

    // 중복 체크
    if (empty($errors)) {
        $query = "SELECT user_id FROM users WHERE username = ?";
        $result = $db->query($query, $email);

        if ($db->num_rows($result) > 0) {
            $errors[] = '이미 등록된 이메일 주소입니다.';
        }
    }

    // 회원가입 처리
    if (empty($errors)) {
        // 1. users 테이블에 삽입
        $query = "INSERT INTO users (username, mail_host, language, created) VALUES (?, ?, ?, NOW())";
        $result = $db->query($query, $email, 'localhost', 'ko_KR');

        if ($result) {
            $user_id = $db->insert_id();

            // 2. registration_pending 테이블 생성 및 데이터 삽입
            $db->query("CREATE TABLE IF NOT EXISTS registration_pending (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $query = "INSERT INTO registration_pending
                      (user_id, email, password_hash, name, department, status, created_at)
                      VALUES (?, ?, ?, ?, ?, 'pending', NOW())";

            $result = $db->query($query, $user_id, $email, $password_hash, $name, $department);

            if ($result) {
                $success = true;
                $success_msg = '회원가입이 완료되었습니다!<br>인사팀의 승인 후 전체 기능을 사용하실 수 있습니다.';
            } else {
                $db->query("DELETE FROM users WHERE user_id = ?", $user_id);
                $errors[] = '회원가입 중 오류가 발생했습니다.';
            }
        } else {
            $errors[] = '회원가입 중 오류가 발생했습니다.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원가입 - 1xINV 사내 웹메일</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .registration-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .company-name {
            color: #2196F3;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-tagline {
            color: #666;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .registration-header h1 {
            color: #2196F3;
            font-size: 24px;
            text-align: center;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .registration-header p {
            color: #666;
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 13px;
        }

        .alert-info {
            background: #E3F2FD;
            border-left: 4px solid #2196F3;
            color: #1565C0;
        }

        .alert-error {
            background: #FFEBEE;
            border-left: 4px solid #F44336;
            color: #C62828;
        }

        .alert-success {
            background: #E8F5E9;
            border-left: 4px solid #4CAF50;
            color: #2E7D32;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2196F3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        .form-note {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .submit-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-login a {
            color: #2196F3;
            text-decoration: none;
            font-size: 14px;
        }

        .back-to-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="logo-section">
            <div class="company-name">1x INV</div>
            <div class="company-tagline">Navigation & OTA Firmware Solutions</div>
        </div>

        <div class="registration-header">
            <h1>회원가입</h1>
            <p>1xINV 사내 웹메일 신규 계정 등록</p>
        </div>

        <div class="alert alert-info">
            <strong>📝 안내사항</strong><br>
            • 회원가입 후 인사팀의 승인이 필요합니다<br>
            • 승인 전에는 로그인만 가능합니다<br>
            • 전체 기능은 승인 후 사용 가능합니다
        </div>

        <?php if (isset($success) && $success): ?>
        <div class="alert alert-success">
            <?php echo $success_msg; ?>
        </div>
        <div class="back-to-login">
            <a href="./index.php">← 로그인 페이지로 이동</a>
        </div>
        <?php else: ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                • <?php echo htmlspecialchars($error); ?><br>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="post" action="register.php">
            <div class="form-group">
                <label for="email">이메일 주소 *</label>
                <input type="email" name="_email" id="email"
                       placeholder="yourname@1xinv.com" required
                       pattern=".+@1xinv\.com$"
                       value="<?php echo isset($_POST['_email']) ? htmlspecialchars($_POST['_email']) : ''; ?>">
                <div class="form-note">반드시 @1xinv.com 도메인을 사용하세요</div>
            </div>

            <div class="form-group">
                <label for="password">비밀번호 *</label>
                <input type="password" name="_password" id="password"
                       placeholder="최소 6자 이상" required minlength="6">
            </div>

            <div class="form-group">
                <label for="password_confirm">비밀번호 확인 *</label>
                <input type="password" name="_password_confirm" id="password_confirm"
                       placeholder="비밀번호를 다시 입력하세요" required>
            </div>

            <div class="form-group">
                <label for="name">이름 *</label>
                <input type="text" name="_name" id="name"
                       placeholder="홍길동" required
                       value="<?php echo isset($_POST['_name']) ? htmlspecialchars($_POST['_name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="department">부서 *</label>
                <select name="_department" id="department" required>
                    <option value="">-- 부서 선택 --</option>
                    <option value="경영진">경영진</option>
                    <option value="개발팀">개발팀</option>
                    <option value="기획팀">기획팀</option>
                    <option value="영업팀">영업팀</option>
                    <option value="마케팅팀">마케팅팀</option>
                    <option value="기술지원팀">기술지원팀</option>
                    <option value="인사팀">인사팀</option>
                    <option value="재무팀">재무팀</option>
                    <option value="연구개발팀">연구개발팀</option>
                    <option value="고객서비스">고객서비스</option>
                </select>
            </div>

            <button type="submit" class="submit-button">회원가입</button>

            <div class="back-to-login">
                <a href="./index.php">← 로그인 페이지로 돌아가기</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>

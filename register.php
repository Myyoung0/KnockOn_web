<?php
require_once __DIR__ . '/config/database.php';

$error = '';
$success = '';

// 회원가입 처리 로직
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // 필수 입력값 검증
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = '모든 필드를 입력하세요.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '유효한 이메일 주소를 입력하세요.';
    } elseif ($password !== $confirm_password) {
        $error = '비밀번호가 일치하지 않습니다.';
    } else {
        // 중복 사용자명 또는 이메일 확인
        $check_sql = "SELECT COUNT(*) FROM Users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $error = '이미 존재하는 사용자명 또는 이메일입니다.';
        } else {
            // 비밀번호 해싱
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 데이터베이스에 사용자 추가
            $sql = "INSERT INTO Users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('sss', $username, $email, $hashed_password);
                if ($stmt->execute()) {
                    $success = '회원가입이 완료되었습니다. 로그인 페이지로 이동하세요.';
                } else {
                    $error = '사용자 등록 중 오류가 발생했습니다. 다시 시도하세요.';
                }
                $stmt->close();
            } else {
                $error = '데이터베이스 오류가 발생했습니다.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원가입</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
<div class="register-container">
    <h1>회원가입</h1>
    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php elseif (!empty($success)): ?>
        <div class="success-message"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <form action="register.php" method="POST">
        <label for="username">사용자명</label>
        <input type="text" id="username" name="username" required>

        <label for="email">이메일</label>
        <input type="email" id="email" name="email" required>

        <label for="password">비밀번호</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">비밀번호 확인</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit">회원가입</button>
    </form>
    <p>이미 계정이 있나요? <a href="login.php">로그인</a></p>
</div>
</body>
</html>

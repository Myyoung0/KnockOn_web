<?php
require_once __DIR__ . '/config/database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = '모든 필드를 입력하세요.';
    } else {
        // 사용자 인증
        $sql = "SELECT user_id, username, password FROM Users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // 로그인 성공
                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                header('Location: index.php');
                exit;
            } else {
                $error = '비밀번호가 일치하지 않습니다.';
            }
        } else {
            $error = '존재하지 않는 사용자입니다.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
<div class="login-container">
    <h1>로그인</h1>
    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form action="login.php" method="POST">
        <label for="username">사용자명</label>
        <input type="text" id="username" name="username" required>

        <label for="password">비밀번호</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">로그인</button>
    </form>
    <p>계정이 없나요? <a href="register.php">회원가입</a></p>
</div>
</body>
</html>

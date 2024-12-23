<?php
require_once __DIR__ . '/config/database.php';

// 사용자 ID 가져오기
if (!isset($_GET['id'])) {
    die('잘못된 접근입니다.');
}

$user_id = (int)$_GET['id'];

// 사용자 정보 가져오기
$sql = "
    SELECT u.username, p.university, p.company, p.gender, p.age, p.bio
    FROM Users u
    LEFT JOIN User_Profile p ON u.user_id = p.user_id
    WHERE u.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

if (!$profile) {
    die('존재하지 않는 사용자입니다.');
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profile['username']) ?>님의 프로필</title>
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
<div class="container">
    <h1><?= htmlspecialchars($profile['username']) ?>님의 프로필</h1>
    <ul>
        <li>대학교: <?= htmlspecialchars($profile['university'] ?? '정보 없음') ?></li>
        <li>직장: <?= htmlspecialchars($profile['company'] ?? '정보 없음') ?></li>
        <li>성별: <?= htmlspecialchars($profile['gender'] ?? '정보 없음') ?></li>
        <li>나이: <?= htmlspecialchars($profile['age'] ?? '정보 없음') ?></li>
        <li>자기소개: <?= nl2br(htmlspecialchars($profile['bio'] ?? '정보 없음')) ?></li>
    </ul>
</div>
</body>
</html>

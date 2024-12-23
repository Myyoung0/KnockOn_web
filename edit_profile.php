<?php
require_once __DIR__ . '/config/database.php';

session_start();

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $university = trim($_POST['university'] ?? null);
    $company = trim($_POST['company'] ?? null);
    $gender = trim($_POST['gender'] ?? null);
    $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
    $bio = trim($_POST['bio'] ?? null);

    $sql = "
        INSERT INTO User_Profile (user_id, university, company, gender, age, bio)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            university = VALUES(university),
            company = VALUES(company),
            gender = VALUES(gender),
            age = VALUES(age),
            bio = VALUES(bio)
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isssis', $user_id, $university, $company, $gender, $age, $bio);
    $stmt->execute();
    $stmt->close();

    header("Location: profile.php");
    exit;
}

// 기존 데이터 가져오기
$sql = "
    SELECT university, company, gender, age, bio 
    FROM User_Profile 
    WHERE user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>프로필 수정</title>
    <link rel="stylesheet" href="css/edit_profile.css">
</head>
<body>
<div class="container">
    <h1>프로필 수정</h1>
    <form action="edit_profile.php" method="POST">
        <label for="university">대학교:</label>
        <input type="text" name="university" id="university" value="<?= htmlspecialchars($profile['university'] ?? '') ?>">
        
        <label for="company">직장:</label>
        <input type="text" name="company" id="company" value="<?= htmlspecialchars($profile['company'] ?? '') ?>">
        
        <label for="gender">성별:</label>
        <select name="gender" id="gender">
            <option value="남성" <?= $profile['gender'] === '남성' ? 'selected' : '' ?>>남성</option>
            <option value="여성" <?= $profile['gender'] === '여성' ? 'selected' : '' ?>>여성</option>
            <option value="기타" <?= $profile['gender'] === '기타' ? 'selected' : '' ?>>기타</option>
        </select>
        
        <label for="age">나이:</label>
        <input type="number" name="age" id="age" value="<?= htmlspecialchars($profile['age'] ?? '') ?>">
        
        <label for="bio">자기소개:</label>
        <textarea name="bio" id="bio" rows="5"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
        
        <button type="submit">저장하기</button>
        <button type="button" onclick="location.href='profile.php'">취소</button>
    </form>
</div>
</body>
</html>

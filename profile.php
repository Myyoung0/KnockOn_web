<?php
require_once __DIR__ . '/config/database.php';

session_start();

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 사용자 정보 가져오기
$sql = "
    SELECT p.university, p.company, p.gender, p.age, p.bio 
    FROM User_Profile p 
    WHERE p.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 사용자 게시글 가져오기
$sql = "
    SELECT post_id, title, created_at
    FROM Posts
    WHERE user_id = ?
    ORDER BY created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 사용자 댓글 가져오기
$sql = "
    SELECT c.comment_id, c.content, c.created_at, p.title AS post_title, p.post_id
    FROM Comments c
    JOIN Posts p ON c.post_id = p.post_id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>프로필</title>
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
<div class="container">
    <h1>내 프로필</h1>

    <!-- 프로필 정보 -->
    <div class="profile-info">
        <h2>프로필 정보</h2>
        <ul>
            <li>대학교: <?= htmlspecialchars($profile['university'] ?? '입력되지 않음') ?></li>
            <li>직장: <?= htmlspecialchars($profile['company'] ?? '입력되지 않음') ?></li>
            <li>성별: <?= htmlspecialchars($profile['gender'] ?? '입력되지 않음') ?></li>
            <li>나이: <?= htmlspecialchars($profile['age'] ?? '입력되지 않음') ?></li>
            <li>자기소개: <?= nl2br(htmlspecialchars($profile['bio'] ?? '입력되지 않음')) ?></li>
        </ul>
        <button onclick="location.href='edit_profile.php'">프로필 수정</button>
    </div>

    <!-- 게시글 리스트 -->
    <div class="my-posts">
        <h2>내 게시글</h2>
        <ul>
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <li>
                        <a href="post_view.php?id=<?= $post['post_id'] ?>">
                            <?= htmlspecialchars($post['title']) ?>
                        </a>
                        (<?= $post['created_at'] ?>)
                        <button onclick="location.href='edit_post.php?id=<?= $post['post_id'] ?>'">수정</button>
                        <button onclick="if(confirm('정말 삭제하시겠습니까?')) location.href='delete_post.php?id=<?= $post['post_id'] ?>'">삭제</button>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>작성한 게시글이 없습니다.</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- 댓글 리스트 -->
    <div class="my-comments">
        <h2>내 댓글</h2>
        <ul>
            <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                    <li>
                        <strong>[<?= htmlspecialchars($comment['post_title']) ?>]</strong> 
                        <a href="post_view.php?id=<?= $comment['post_id'] ?>">
                            <?= htmlspecialchars($comment['content']) ?>
                        </a>
                        (<?= $comment['created_at'] ?>)
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>작성한 댓글이 없습니다.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
</body>
</html>

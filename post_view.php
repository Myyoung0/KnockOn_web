<?php
require_once __DIR__ . '/config/database.php';

session_start();

// 로그인 상태 확인
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : null;
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// 게시글 ID 확인
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$post_id) {
    header("Location: index.php");
    exit;
}

// 게시글 데이터 가져오기
$sql = "SELECT p.*, u.username, u.user_id FROM Posts p JOIN Users u ON p.user_id = u.user_id WHERE p.post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    header("Location: index.php");
    exit;
}

// 첨부 파일 가져오기
$sql = "SELECT * FROM Files WHERE post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 좋아요 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like'])) {
    $type = 'POST';
    $like_sql = "
        INSERT INTO Likes (user_id, target_id, type) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE created_at = NOW()
    ";
    $stmt = $conn->prepare($like_sql);
    $stmt->bind_param('iis', $user_id, $post_id, $type);
    $stmt->execute();
    $stmt->close();
    header("Location: post_view.php?id=$post_id");
    exit;
}

// 댓글 작성 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $content = trim($_POST['content']);
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    if (!empty($content)) {
        $comment_sql = "
            INSERT INTO Comments (post_id, user_id, parent_id, content) 
            VALUES (?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($comment_sql);
        $stmt->bind_param('iiis', $post_id, $user_id, $parent_id, $content);
        $stmt->execute();
        $stmt->close();
        header("Location: post_view.php?id=$post_id");
        exit;
    }
}

// 좋아요 개수 가져오기
$sql = "SELECT COUNT(*) AS like_count FROM Likes WHERE target_id = ? AND type = 'POST'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$like_data = $stmt->get_result()->fetch_assoc();
$like_count = $like_data['like_count'] ?? 0;
$stmt->close();

// 댓글 가져오기
$sql = "
    SELECT c.*, u.username 
    FROM Comments c 
    JOIN Users u ON c.user_id = u.user_id 
    WHERE c.post_id = ? 
    ORDER BY COALESCE(c.parent_id, c.comment_id), c.comment_id
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$comments = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> - Hacker Board</title>
    <link rel="stylesheet" href="css/post_view.css">
</head>
<body>
<div class="container">
    <!-- 상단 바 -->
    <div class="top-bar">
        <button class="home-button" onclick="location.href='index.php'">홈</button>
    </div>

    <!-- 게시글 내용 -->
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <p>
    작성자: 
    <a href="view_profile.php?id=<?= $post['user_id'] ?>" class="author-link">
        <?= htmlspecialchars($post['username']) ?>
    </a> | 작성일: <?= htmlspecialchars($post['created_at']) ?>
</p>
    <div class="content"><?= nl2br(htmlspecialchars($post['content'])) ?></div>

    <!-- 첨부 파일 -->
    <?php if (!empty($files)): ?>
        <div class="attachments">
            <h3>첨부 파일</h3>
            <ul>
                <?php foreach ($files as $file): ?>
                    <?php if (strpos($file['file_type'], 'image/') === 0): ?>
                        <li>
                            <img src="<?= htmlspecialchars($file['file_path']) ?>" alt="<?= htmlspecialchars($file['file_name']) ?>" style="max-width: 200px;">
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="<?= htmlspecialchars($file['file_path']) ?>" download>
                                <?= htmlspecialchars($file['file_name']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- 좋아요 -->
    <div class="like-section">
        <form action="post_view.php?id=<?= $post_id ?>" method="POST">
            <button type="submit" name="like" <?= !$is_logged_in ? 'disabled' : '' ?>>
                👍 좋아요 (<?= $like_count ?>)
            </button>
        </form>
    </div>

    <!-- 댓글 섹션 -->
    <div class="comments-section">
        <h2>댓글</h2>
        <?php if ($is_logged_in): ?>
            <form action="post_view.php?id=<?= $post_id ?>" method="POST" class="comment-form">
                <textarea name="content" rows="3" placeholder="댓글을 입력하세요..." required></textarea>
                <button type="submit" name="comment">댓글 작성</button>
            </form>
        <?php else: ?>
            <p>댓글을 작성하려면 <a href="login.php">로그인</a>하세요.</p>
        <?php endif; ?>

        <ul class="comment-list">
            <?php 
            $comment_tree = [];
            while ($comment = $comments->fetch_assoc()) {
                if ($comment['parent_id']) {
                    $comment_tree[$comment['parent_id']]['replies'][] = $comment;
                } else {
                    $comment_tree[$comment['comment_id']] = $comment;
                    $comment_tree[$comment['comment_id']]['replies'] = [];
                }
            }

            function render_comments($comments) {
                foreach ($comments as $comment) {
                    echo "<li>";
                    echo "<strong>" . htmlspecialchars($comment['username']) . ":</strong> ";
                    echo nl2br(htmlspecialchars($comment['content']));
                    echo " <span class='comment-date'>(" . $comment['created_at'] . ")</span>";
                    echo "<ul>";
                    render_comments($comment['replies']);
                    echo "</ul>";
                    echo "</li>";
                }
            }

            render_comments($comment_tree);
            ?>
        </ul>
    </div>
</div>

</body>
</html>

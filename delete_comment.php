<?php
require_once __DIR__ . '/config/database.php';

session_start();

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die('잘못된 요청입니다.');
}

$comment_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// 댓글 삭제 쿼리
$sql = "DELETE FROM Comments WHERE comment_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $comment_id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: profile.php");
} else {
    echo "댓글 삭제에 실패했습니다.";
}
$stmt->close();
$conn->close();
?>

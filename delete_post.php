<?php
// DB 연결
require_once __DIR__ . '/config/database.php';

// 세션 시작
session_start();

// 로그인 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 게시글 ID 확인
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$post_id) {
    header("Location: index.php");
    exit;
}

// 게시글 작성자인지 확인
$sql = "SELECT user_id FROM Posts WHERE post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post || $post['user_id'] !== $_SESSION['user_id']) {
    header("Location: index.php");
    exit;
}

// 첨부 파일 삭제
$sql = "SELECT file_path FROM Files WHERE post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$result = $stmt->get_result();

while ($file = $result->fetch_assoc()) {
    if (file_exists($file['file_path'])) {
        unlink($file['file_path']); // 파일 삭제
    }
}
$stmt->close();

// 게시글 관련 파일 데이터 삭제
$sql = "DELETE FROM Files WHERE post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$stmt->close();

// 게시글 삭제
$sql = "DELETE FROM Posts WHERE post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$stmt->close();

// 삭제 완료 후 홈으로 리다이렉트
header("Location: index.php");
exit;

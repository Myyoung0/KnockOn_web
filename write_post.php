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

// 게시글 작성 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = (int)$_POST['category'];
    $error = '';

    if (!empty($title) && !empty($content)) {
        $conn->begin_transaction();
        try {
            // 게시글 저장
            $sql = "
                INSERT INTO Posts (user_id, category_id, title, content, view_count, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iiss', $user_id, $category_id, $title, $content);
            $stmt->execute();
            $post_id = $stmt->insert_id;
            $stmt->close();

            // 파일 업로드 처리
            if (!empty($_FILES['file']['name'])) {
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_name = basename($_FILES['file']['name']);
                $file_path = $upload_dir . $file_name;
                $file_type = mime_content_type($_FILES['file']['tmp_name']);

                if (move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
                    $file_sql = "
                        INSERT INTO Files (post_id, file_name, file_path, file_type)
                        VALUES (?, ?, ?, ?)
                    ";
                    $stmt = $conn->prepare($file_sql);
                    $stmt->bind_param('isss', $post_id, $file_name, $file_path, $file_type);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    throw new Exception("파일 업로드 실패");
                }
            }

            $conn->commit();
            // 작성 완료 후 홈(index.php)으로 리다이렉션
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = '게시글 작성 중 오류가 발생했습니다.';
        }
    } else {
        $error = '제목과 내용을 모두 입력해주세요.';
    }
}

// 카테고리 가져오기
$categories = [];
$sql = "SELECT category_id, name FROM Categories ORDER BY category_id";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시글 작성 - Hacker Board</title>
    <link rel="stylesheet" href="css/write_post.css">
</head>
<body>
<div class="container">
    <h1>게시글 작성</h1>

    <?php if (!empty($error)): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="write_post.php" method="POST" enctype="multipart/form-data" class="write-form">
        <div class="form-group">
            <label for="category">카테고리 선택</label>
            <select name="category" id="category" required>
                <option value="">카테고리를 선택하세요</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['category_id'] ?>">
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="title">제목</label>
            <input type="text" name="title" id="title" placeholder="제목을 입력하세요" required>
        </div>

        <div class="form-group">
            <label for="content">내용</label>
            <textarea name="content" id="content" rows="10" placeholder="내용을 입력하세요" required></textarea>
        </div>

        <div class="form-group">
            <label for="file">파일 업로드</label>
            <input type="file" name="file" id="file">
        </div>

        <button type="submit" class="btn submit">게시글 작성</button>
        <button type="button" class="btn cancel" onclick="location.href='index.php'">취소</button>
    </form>
</div>
</body>
</html>

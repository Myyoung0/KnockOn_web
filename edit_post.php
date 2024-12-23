<?php
require_once __DIR__ . '/config/database.php';

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

// 게시글 데이터 가져오기
$sql = "SELECT * FROM Posts WHERE post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    header("Location: index.php");
    exit;
}

// 기존 첨부 파일 가져오기
$sql = "SELECT * FROM Files WHERE post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 게시글 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = (int)$_POST['category'];

    if (!empty($title) && !empty($content)) {
        // 게시글 업데이트
        $sql = "
            UPDATE Posts
            SET title = ?, content = ?, category_id = ?, updated_at = NOW()
            WHERE post_id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssii', $title, $content, $category_id, $post_id);

        if ($stmt->execute()) {
            // 삭제할 파일 처리
            if (!empty($_POST['delete_files'])) {
                $delete_files = $_POST['delete_files'];
                foreach ($delete_files as $file_id) {
                    $file_sql = "SELECT file_path FROM Files WHERE file_id = ?";
                    $file_stmt = $conn->prepare($file_sql);
                    $file_stmt->bind_param('i', $file_id);
                    $file_stmt->execute();
                    $file_result = $file_stmt->get_result()->fetch_assoc();
                    if ($file_result) {
                        unlink($file_result['file_path']); // 실제 파일 삭제
                    }
                    $file_stmt->close();

                    $delete_sql = "DELETE FROM Files WHERE file_id = ?";
                    $stmt = $conn->prepare($delete_sql);
                    $stmt->bind_param('i', $file_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            // 새 파일 업로드
            if (!empty($_FILES['files']['name'][0])) {
                foreach ($_FILES['files']['name'] as $key => $file_name) {
                    $file_tmp = $_FILES['files']['tmp_name'][$key];
                    $file_size = $_FILES['files']['size'][$key];
                    $file_type = $_FILES['files']['type'][$key];
                    $file_path = 'uploads/' . time() . '_' . $file_name;

                    if (move_uploaded_file($file_tmp, $file_path)) {
                        $file_sql = "
                            INSERT INTO Files (post_id, file_name, file_path, file_type)
                            VALUES (?, ?, ?, ?)
                        ";
                        $stmt = $conn->prepare($file_sql);
                        $stmt->bind_param('isss', $post_id, $file_name, $file_path, $file_type);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            header("Location: post_view.php?id=$post_id");
            exit;
        } else {
            $error = '게시글 수정 중 오류가 발생했습니다.';
        }
        $stmt->close();
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
    <title>게시글 수정 - Hacker Board</title>
    <link rel="stylesheet" href="css/edit_post.css">
</head>
<body>
<div class="container">
    <h1>게시글 수정</h1>

    <?php if (!empty($error)): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="edit_post.php?id=<?= $post_id ?>" method="POST" enctype="multipart/form-data" class="edit-form">
        <div class="form-group">
            <label for="category">카테고리</label>
            <select name="category" id="category" required>
                <option value="">카테고리를 선택하세요</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['category_id'] ?>" <?= $category['category_id'] == $post['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="title">제목</label>
            <input type="text" name="title" id="title" value="<?= htmlspecialchars($post['title']) ?>" required>
        </div>

        <div class="form-group">
            <label for="content">내용</label>
            <textarea name="content" id="content" rows="10" required><?= htmlspecialchars($post['content']) ?></textarea>
        </div>

        <div class="form-group">
            <label>기존 첨부 파일</label>
            <ul>
                <?php foreach ($files as $file): ?>
                    <li>
                        <?= htmlspecialchars($file['file_name']) ?>
                        <input type="checkbox" name="delete_files[]" value="<?= $file['file_id'] ?>"> 삭제
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="form-group">
            <label for="files">새 파일 업로드</label>
            <input type="file" name="files[]" id="files" multiple>
        </div>

        <button type="submit" class="btn submit">수정</button>
        <button type="button" class="btn cancel" onclick="location.href='post_view.php?id=<?= $post_id ?>'">취소</button>
    </form>
</div>
</body>
</html>

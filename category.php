<?php
require_once __DIR__ . '/config/database.php'; 

$categoryName = $_GET['category'] ?? null;
if (!$categoryName) {
    die("카테고리 이름이 제공되지 않았습니다.");
}

function getPostsByCategory($conn, $categoryName) {
    $sql = "
        SELECT p.post_id, p.title, p.created_at
        FROM Posts p
        JOIN Categories c ON p.category_id = c.category_id
        WHERE c.name = ?
        ORDER BY p.created_at DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $categoryName);
    $stmt->execute();
    $result = $stmt->get_result();

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    $stmt->close();
    return $posts;
}

$posts = getPostsByCategory($conn, $categoryName);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($categoryName) ?> - Board</title>
    <link rel="stylesheet" href="css/category.css">
</head>
<body>
<div class="category-container">
    <h1><?= htmlspecialchars($categoryName) ?></h1>
    <ul class="post-list">
        <?php if (!empty($posts)) : ?>
            <?php foreach ($posts as $post) : ?>
                <li>
                    <a href="post_view.php?id=<?= $post['post_id'] ?>">
                        <?= htmlspecialchars($post['title']) ?>
                    </a>
                    (<?= substr($post['created_at'], 0, 10) ?>)
                </li>
            <?php endforeach; ?>
        <?php else : ?>
            <li>아직 게시물이 없습니다.</li>
        <?php endif; ?>
    </ul>
</div>
</body>
</html>

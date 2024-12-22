<?php
// DB 연결
require_once __DIR__ . '/config/database.php';

// 세션 시작
session_start();

// 사용자 로그인 상태 확인
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : null;

/**
 * 게시물 검색 또는 카테고리별 게시물 가져오기
 */
$searchResults = [];
$selectedCategory = null;

// 검색어 또는 카테고리 클릭 확인
if (!empty($_GET['search']) || !empty($_GET['category'])) {
    if (!empty($_GET['category'])) {
        // 카테고리별 게시물 가져오기
        $selectedCategory = trim($_GET['category']);
        $sql = "
            SELECT p.post_id, p.title, p.created_at, c.name AS category_name
            FROM Posts p
            JOIN Categories c ON p.category_id = c.category_id
            WHERE c.name = ?
            ORDER BY p.created_at DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $selectedCategory);
    } elseif (!empty($_GET['search'])) {
        // 검색어 기반 게시물 검색
        $searchQuery = trim($_GET['search']);
        $sql = "
            SELECT p.post_id, p.title, p.created_at, c.name AS category_name
            FROM Posts p
            JOIN Categories c ON p.category_id = c.category_id
            WHERE p.title LIKE ? OR p.content LIKE ?
            ORDER BY c.name, p.created_at DESC
        ";
        $stmt = $conn->prepare($sql);
        $likeSearch = '%' . $searchQuery . '%';
        $stmt->bind_param('ss', $likeSearch, $likeSearch);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $searchResults[$row['category_name']][] = $row;
    }
    $stmt->close();
}

// 기본 데이터: 공지, 팀원 모집, 이슈게시판, 질문
if (empty($_GET['search']) && empty($_GET['category'])) {
    function getLatestPosts($conn, $categoryName, $limit = 5) {
        $sql = "
            SELECT p.post_id, p.title, p.created_at
            FROM Posts p
            JOIN Categories c ON p.category_id = c.category_id
            WHERE c.name = ?
            ORDER BY p.created_at DESC
            LIMIT ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $categoryName, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
        $stmt->close();
        return $posts;
    }

    $noticePosts   = getLatestPosts($conn, '공지게시판', 5);
    $teamPosts     = getLatestPosts($conn, '팀원 모집', 5);
    $issuePosts    = getLatestPosts($conn, '이슈게시판', 5);
    $questionPosts = getLatestPosts($conn, '질문', 5);
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hacker Board - Index</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<!-- 헤더 -->
<div class="header">
    <div class="header-left">
        <!-- 햄버거 아이콘 -->
        <svg class="more-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" onclick="toggleSidebar()">
            <circle cx="5" cy="12" r="2"></circle>
            <circle cx="12" cy="12" r="2"></circle>
            <circle cx="19" cy="12" r="2"></circle>
        </svg>
    </div>

    <div class="header-center">
        <img src="images/logo.png" alt="Hacker Board Logo" class="logo">
    </div>

    <div class="header-right">
        <?php if ($is_logged_in): ?>
            <span class="welcome-message"><?= htmlspecialchars($username) ?>님, 환영합니다!</span>
            <button onclick="location.href='profile.php'">프로필</button>
            <button onclick="location.href='logout.php'">로그아웃</button>
            <button onclick="location.href='write_post.php'" class="write-button">작성</button>
        <?php else: ?>
            <button onclick="location.href='login.php'">로그인</button>
            <button onclick="location.href='register.php'">회원가입</button>
        <?php endif; ?>
        <button onclick="location.href='index.php'" class="home-button">홈</button>
        <form action="index.php" method="GET" class="search-form">
            <input type="text" name="search" class="search-box" placeholder="검색어를 입력하세요...">
            <button type="submit">검색</button>
        </form>
    </div>
</div>

<!-- 사이드바 -->
<div id="sidebar" class="sidebar">
    <div class="close-btn" onclick="toggleSidebar()">[ 닫기 ]</div>
    <ul>
        <li><a href="index.php?category=공지게시판">공지게시판</a></li>
        <li>
            Hacking Forum
            <ul>
                <li><a href="index.php?category=Web">Web</a></li>
                <li><a href="index.php?category=Pwnable">Pwnable</a></li>
                <li><a href="index.php?category=Network">Network</a></li>
                <li><a href="index.php?category=Reversing">Reversing</a></li>
                <li><a href="index.php?category=Forensic">Forensic</a></li>
                <li><a href="index.php?category=More">More</a></li>
            </ul>
        </li>
        <li>
            Grow With Us
            <ul>
                <li><a href="index.php?category=Hacking Story">Hacking Story</a></li>
                <li><a href="index.php?category=개인 Project 공유">개인 Project 공유</a></li>
                <li><a href="index.php?category=Hacking Tip">Hacking Tip</a></li>
                <li><a href="index.php?category=팀원 모집">팀원 모집</a></li>
            </ul>
        </li>
        <li>
            Community
            <ul>
                <li><a href="index.php?category=자유게시판">자유게시판</a></li>
                <li><a href="index.php?category=이슈게시판">이슈게시판</a></li>
                <li><a href="index.php?category=잡답">잡답</a></li>
                <li><a href="index.php?category=질문">질문</a></li>
            </ul>
        </li>
    </ul>
</div>

<div class="main">
    <?php if (!empty($searchResults)): ?>
        <h2>검색 결과</h2>
        <?php foreach ($searchResults as $categoryName => $posts): ?>
            <div class="section-box">
                <h3><?= htmlspecialchars($categoryName) ?></h3>
                <ul class="post-list">
                    <?php foreach ($posts as $post): ?>
                        <li>
                            <a href="post_view.php?id=<?= $post['post_id'] ?>">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                            (<?= substr($post['created_at'], 0, 10) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    <?php elseif (!empty($_GET['search'])): ?>
        <h2>검색 결과</h2>
        <p>검색어 "<strong><?= htmlspecialchars($_GET['search']) ?></strong>"에 대한 결과가 없습니다.</p>
    <?php elseif (!empty($categoryPosts)): ?>
        <h2><?= htmlspecialchars($selectedCategory) ?> 게시판</h2>
        <div class="section-box">
            <ul class="post-list">
                <?php foreach ($categoryPosts as $post): ?>
                    <li>
                        <a href="post_view.php?id=<?= $post['post_id'] ?>">
                            <?= htmlspecialchars($post['title']) ?>
                        </a>
                        <p><?= htmlspecialchars(substr($post['content'], 0, 100)) ?>...</p>
                        (<?= substr($post['created_at'], 0, 10) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($selectedCategory): ?>
        <h2><?= htmlspecialchars($selectedCategory) ?> 게시판</h2>
        <p>현재 게시판에 게시글이 없습니다.</p>
    <?php else: ?>
        <!-- 기본 섹션: 공지 -->
        <div class="section-box">
            <h2>공지</h2>
            <ul class="post-list">
                <?php if (!empty($noticePosts)): ?>
                    <?php foreach ($noticePosts as $post): ?>
                        <li>
                            <a href="post_view.php?id=<?= $post['post_id'] ?>">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                            (<?= substr($post['created_at'], 0, 10) ?>)
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>아직 공지 게시물이 없습니다.</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- 기본 섹션: 팀원 모집 -->
        <div class="section-box">
            <h2>팀원 모집</h2>
            <ul class="post-list">
                <?php if (!empty($teamPosts)): ?>
                    <?php foreach ($teamPosts as $post): ?>
                        <li>
                            <a href="post_view.php?id=<?= $post['post_id'] ?>">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                            (<?= substr($post['created_at'], 0, 10) ?>)
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>아직 팀원 모집 게시물이 없습니다.</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- 기본 섹션: 이슈 게시판 -->
        <div class="section-box">
            <h2>이슈게시판</h2>
            <ul class="post-list">
                <?php if (!empty($issuePosts)): ?>
                    <?php foreach ($issuePosts as $post): ?>
                        <li>
                            <a href="post_view.php?id=<?= $post['post_id'] ?>">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                            (<?= substr($post['created_at'], 0, 10) ?>)
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>아직 이슈 게시물이 없습니다.</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- 기본 섹션: 질문 -->
        <div class="section-box">
            <h2>질문</h2>
            <ul class="post-list">
                <?php if (!empty($questionPosts)): ?>
                    <?php foreach ($questionPosts as $post): ?>
                        <li>
                            <a href="post_view.php?id=<?= $post['post_id'] ?>">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                            (<?= substr($post['created_at'], 0, 10) ?>)
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>아직 질문 게시물이 없습니다.</li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('show');
}
</script>
</body>
</html>

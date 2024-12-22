<?php
// index.php

// DB 연결
require_once __DIR__ . '/config/database.php'; 

/**
 * 카테고리 이름을 받아 최신 게시글 목록을 반환하는 함수
 * @param mysqli $conn
 * @param string $categoryName  (예: '공지게시판')
 * @param int $limit
 * @return array
 */
function getLatestPosts($conn, $categoryName, $limit = 5) {
    // 카테고리 테이블의 name이 $categoryName인 것과
    // Posts를 JOIN하여 최신 글을 가져온다고 가정
    // (테이블/컬럼명은 실제 DB에 맞춰 수정!)
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
    while($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    $stmt->close();
    return $posts;
}

// 실제로 4개 섹션(공지, 팀원 모집, 이슈게시판, 질문) 데이터 조회
$noticePosts   = getLatestPosts($conn, '공지게시판', 5);
$teamPosts     = getLatestPosts($conn, '팀원 모집', 5);
$issuePosts    = getLatestPosts($conn, '이슈게시판', 5);
$questionPosts = getLatestPosts($conn, '질문', 5);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>Hacker Board - Index</title>
  <!-- 스타일시트 연결 -->
  <link rel="stylesheet" href="css/index.css">
</head>
<body>

<!-- 헤더 -->
<div class="header">
  <div class="header-left">
    <!-- 햄버거 아이콘(사이드바 열기) -->
    <img src="https://cdn-icons-png.flaticon.com/512/56/56763.png"
         alt="Menu" class="hamburger-icon" onclick="toggleSidebar()" />
  </div>

  <div class="header-center">
    <h1>Hacker Board</h1>
  </div>

  <div class="header-right">
    <!-- 예시: 로그인/회원가입/프로필/검색 -->
    <button onclick="location.href='login.php'">로그인</button>
    <button onclick="location.href='register.php'">회원가입</button>
    <button onclick="location.href='profile.php'">프로필</button>
    <input type="text" class="search-box" placeholder="Search...">
    <button>검색</button>
  </div>
</div>

<!-- 사이드바 -->
<div id="sidebar" class="sidebar">
  <div class="close-btn" onclick="toggleSidebar()">[ 닫기 ]</div>
  <ul>
    <!-- 1. 공지게시판 -->
    <li>공지게시판</li>

    <!-- 2. Hacking Forum (하위: Web, Pwnable, Network, Reversing, Forensic, More) -->
    <li>
      Hacking Forum
      <ul>
        <li>Web</li>
        <li>Pwnable</li>
        <li>Network</li>
        <li>Reversing</li>
        <li>Forensic</li>
        <li>More</li>
      </ul>
    </li>

    <!-- 3. Grow With Us (하위: Hacking Story, 개인 Project 공유, Hacking Tip, 팀원 모집) -->
    <li>
      Grow With Us
      <ul>
        <li>Hacking Story</li>
        <li>개인 Project 공유</li>
        <li>Hacking Tip</li>
        <li>팀원 모집</li>
      </ul>
    </li>

    <!-- 4. Community (하위: 자유게시판, 이슈게시판, 잡답, 질문) -->
    <li>
      Community
      <ul>
        <li>자유게시판</li>
        <li>이슈게시판</li>
        <li>잡답</li>
        <li>질문</li>
      </ul>
    </li>
  </ul>
</div>

<!-- 메인영역 (4 구역) -->
<div class="main">
  <!-- 1) 공지 -->
  <div class="section-box">
    <h2>공지</h2>
    <ul class="post-list">
      <?php if (!empty($noticePosts)) : ?>
        <?php foreach ($noticePosts as $post) : ?>
          <li>
            <a href="post_view.php?id=<?= $post['post_id'] ?>">
              <?= htmlspecialchars($post['title']) ?>
            </a>
            (<?= substr($post['created_at'], 0, 10) ?>)
          </li>
        <?php endforeach; ?>
      <?php else : ?>
        <li>아직 공지가 없습니다.</li>
      <?php endif; ?>
    </ul>
  </div>

  <!-- 2) 팀원 모집 -->
  <div class="section-box">
    <h2>팀원 모집</h2>
    <ul class="post-list">
      <?php if (!empty($teamPosts)) : ?>
        <?php foreach ($teamPosts as $post) : ?>
          <li>
            <a href="post_view.php?id=<?= $post['post_id'] ?>">
              <?= htmlspecialchars($post['title']) ?>
            </a>
            (<?= substr($post['created_at'], 0, 10) ?>)
          </li>
        <?php endforeach; ?>
      <?php else : ?>
        <li>아직 팀원 모집글이 없습니다.</li>
      <?php endif; ?>
    </ul>
  </div>

  <!-- 3) 이슈게시판 -->
  <div class="section-box">
    <h2>이슈게시판</h2>
    <ul class="post-list">
      <?php if (!empty($issuePosts)) : ?>
        <?php foreach ($issuePosts as $post) : ?>
          <li>
            <a href="post_view.php?id=<?= $post['post_id'] ?>">
              <?= htmlspecialchars($post['title']) ?>
            </a>
            (<?= substr($post['created_at'], 0, 10) ?>)
          </li>
        <?php endforeach; ?>
      <?php else : ?>
        <li>아직 이슈 글이 없습니다.</li>
      <?php endif; ?>
    </ul>
  </div>

  <!-- 4) 질문 -->
  <div class="section-box">
    <h2>질문</h2>
    <ul class="post-list">
      <?php if (!empty($questionPosts)) : ?>
        <?php foreach ($questionPosts as $post) : ?>
          <li>
            <a href="post_view.php?id=<?= $post['post_id'] ?>">
              <?= htmlspecialchars($post['title']) ?>
            </a>
            (<?= substr($post['created_at'], 0, 10) ?>)
          </li>
        <?php endforeach; ?>
      <?php else : ?>
        <li>아직 질문 게시물이 없습니다.</li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<!-- (선택) 푸터 -->
<div class="footer">
  © <?= date('Y') ?> Hacker Board. All rights reserved.
</div>

<!-- 자바스크립트: 사이드바 토글 -->
<script>
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  sidebar.classList.toggle('show');
}
</script>

</body>
</html>

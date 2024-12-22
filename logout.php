<?php
// logout.php

session_start();

// 세션 삭제
session_unset();
session_destroy();

// 쿠키 삭제
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/'); // 쿠키 만료
}

// 홈으로 리다이렉트
header("Location: index.php");
exit();
?>

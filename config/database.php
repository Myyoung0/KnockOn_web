<?php
$host = 'localhost';
$db   = 'knockon_web';
$user = 'root';
$pass = 'Daniel1209$';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("DB 연결 실패: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

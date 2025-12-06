<?php
// admin/config.php
// Auth is handled by Cloudflare Zero Trust; this is just DB + helpers.

$DB_HOST = 'localhost';
$DB_USER = 'ertracke_admin_user';   // change if needed
$DB_PASS = 'your_password_here';    // set a real password
$DB_NAME = 'ertracke_admin';        // matches your DB name

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

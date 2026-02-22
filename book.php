<?php
session_start();
require_once __DIR__ . '/includes/db_helper.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Check email verification
$verify_query = "SELECT email_verified FROM users WHERE id = ?";
$stmt = $db->prepare($verify_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = get_stmt_result($stmt);
$user = $result->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if (!$user['email_verified']) {
    header('Location: verify_email.php');
    exit;
}

// Rest of your book.php code... 
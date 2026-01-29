<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (isset($_GET['reply_id'])) {
    $replyId = intval($_GET['reply_id']);
    $stmt = $conn->prepare("UPDATE replies SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $replyId);
    $stmt->execute();
    $stmt->close();
}

// Redirect to the post after marking as read
if (isset($_GET['post_id'])) {
    header("Location: community.php?post_id=" . intval($_GET['post_id']));
} else {
    header("Location: dashboard.php");
}
$conn->close();
exit();
?>

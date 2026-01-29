<?php
session_start();
$conn = new mysqli("localhost","u739446465_empowerher_db","u739446465_Empowerher_db@","u739446465_empowerher_db");

$id = intval($_GET['id']);
$userId = $_SESSION['user_id'];

$conn->query("
    UPDATE user_alerts 
    SET is_read = 1 
    WHERE id = $id AND user_id = $userId
");

header("Location: community.php");
exit;
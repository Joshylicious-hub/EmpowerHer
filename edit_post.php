<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$postId  = intval($_POST['post_id']);
$title   = $conn->real_escape_string($_POST['title']);
$topic   = $conn->real_escape_string($_POST['topic']);
$content = $conn->real_escape_string($_POST['content']);
$userId  = $_SESSION['user_id'];

// Ensure only the post owner can edit
$query = "UPDATE posts SET title='$title', topic='$topic', content='$content' 
          WHERE id=$postId AND user_id=$userId";

if ($conn->query($query)) {
    header("Location: community.php?updated=1");
} else {
    echo "Error: " . $conn->error;
}
?>
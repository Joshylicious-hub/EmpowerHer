<?php
session_start();
$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) die(json_encode(["success" => false, "error" => "DB error"]));

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $postId = intval($_POST['post_id']);
    $check = $conn->query("SELECT * FROM posts WHERE id=$postId AND user_id=$userId");

    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM posts WHERE id=$postId");
        $conn->query("DELETE FROM replies WHERE post_id=$postId");
        $conn->query("DELETE FROM post_reactions WHERE post_id=$postId");
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Unauthorized"]);
    }
}

<?php
session_start();
$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) die(json_encode(["success" => false, "error" => "DB error"]));

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_post'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $topic = $conn->real_escape_string($_POST['topic']);
    $mediaPath = NULL;

    if (!empty($_FILES['media']['name'])) {
        $filename = time() . "_" . basename($_FILES['media']['name']);
        $targetDir = "uploads/posts/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $targetFile = $targetDir . $filename;
        move_uploaded_file($_FILES['media']['tmp_name'], $targetFile);
        $mediaPath = $targetFile;
    }

    $conn->query("INSERT INTO posts (user_id, user_name, title, content, topic, media, created_at)
                  VALUES ($userId, '$userName', '$title', '$content', '$topic', '$mediaPath', NOW())");

    $newId = $conn->insert_id;

    // fetch profile pic
    $res = $conn->query("SELECT profile_pic FROM users WHERE id=$userId");
    $user = $res->fetch_assoc();

    echo json_encode([
  "success" => true,
  "post_id" => $newId,
  "user_name" => $userName,
  "profile_pic" => $user['profile_pic'] ?? "images/profile.jpg",
  "title" => $title,
  "content" => nl2br(htmlspecialchars($content)),
  "topic" => $topic,
  "media" => $mediaPath,
  "created_at" => date("M d, Y g:i A"),
  "is_verified" => ($userName === "Joshua Andres")
]);

}

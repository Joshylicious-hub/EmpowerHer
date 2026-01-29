<?php
session_start();
header('Content-Type: application/json');
include 'db_connection.php'; // your DB connection

$input = json_decode(file_get_contents('php://input'), true);
$post_id = intval($input['post_id']);
$reply_text = trim($input['reply']);
$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id && $reply_text) {
    $stmt = $conn->prepare("INSERT INTO replies (post_id, user_id, reply, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $post_id, $user_id, $reply_text);
    if ($stmt->execute()) {
        $user = $conn->query("SELECT user_name, profile_pic FROM users WHERE id=$user_id")->fetch_assoc();
        echo json_encode([
            'success' => true,
            'reply' => htmlspecialchars($reply_text),
            'user_name' => htmlspecialchars($user['user_name']),
            'profile_pic' => !empty($user['profile_pic']) ? $user['profile_pic'] : 'images/profile.jpg'
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}

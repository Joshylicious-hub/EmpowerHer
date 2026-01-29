<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$post_id = intval($_POST['post_id']);
$user_id = $_SESSION['user_id'];
$reply = trim($_POST['reply']);

// Handle media upload
$mediaPath = '';
if (!empty($_FILES['media']['name'])) {
    $file = $_FILES['media'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $targetDir = "uploads/replies/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    $mediaPath = $targetDir . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $mediaPath)) {
        echo json_encode(['success' => false, 'error' => 'Failed to upload media']);
        exit;
    }
}

// Get user info
$userRes = $conn->query("SELECT fullName, profile_pic, verified FROM users WHERE id=$user_id");
$user = $userRes->fetch_assoc();
$profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : "images/profile.jpg";
$user_name = $user['fullName'];

// Insert reply
$stmt = $conn->prepare("INSERT INTO replies (post_id, user_id, user_name, reply, created_at, media) VALUES (?, ?, ?, ?, NOW(), ?)");
$stmt->bind_param("iisss", $post_id, $user_id, $user_name, $reply, $mediaPath);

if ($stmt->execute()) {
    $reply_id = $stmt->insert_id;

    // Determine verified status
    $is_verified = ($user['verified'] == 1 || $user_name === 'Joshua Andres' || $user_name === 'Issay');

    echo json_encode([
        'success' => true,
        'reply_id' => $reply_id,
        'user_name' => htmlspecialchars($user_name),
        'profile_pic' => $profile_pic . '?v=' . time(),
        'is_verified' => $is_verified,
        'reply' => nl2br(htmlspecialchars($reply)),
        'media' => $mediaPath,
        'created_at' => date("M d, Y H:i")
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save reply']);
}

$stmt->close();
$conn->close();

<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false, 'message'=>'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$lesson_id = $data['lesson_id'] ?? 0;

if($lesson_id <= 0){
    echo json_encode(['success'=>false, 'message'=>'Invalid lesson']);
    exit;
}

$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if($conn->connect_error){
    echo json_encode(['success'=>false, 'message'=>'DB connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'];

// ---------------------------
// Part 1: Update or insert into user_lessons
// ---------------------------
$stmt = $conn->prepare("SELECT * FROM user_lessons WHERE user_id=? AND lesson_id=?");
$stmt->bind_param("ii", $user_id, $lesson_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){
    // Update completed_at
    $stmtUpdate = $conn->prepare("UPDATE user_lessons SET completed_at=NOW() WHERE user_id=? AND lesson_id=?");
    $stmtUpdate->bind_param("ii", $user_id, $lesson_id);
    $stmtUpdate->execute();
    $stmtUpdate->close();
} else {
    // Insert new record
    $stmtInsert = $conn->prepare("INSERT INTO user_lessons (user_id, lesson_id, completed_at) VALUES (?, ?, NOW())");
    $stmtInsert->bind_param("ii", $user_id, $lesson_id);
    $stmtInsert->execute();
    $stmtInsert->close();
}

$stmt->close();

// ---------------------------
// Part 2: Insert into user_progress if not exists
// ---------------------------
$stmtProgress = $conn->prepare("INSERT IGNORE INTO user_progress (user_id, lesson_id) VALUES (?, ?)");
$stmtProgress->bind_param("ii", $user_id, $lesson_id);
$stmtProgress->execute();
$stmtProgress->close();

$conn->close();

echo json_encode(['success'=>true]);
?>

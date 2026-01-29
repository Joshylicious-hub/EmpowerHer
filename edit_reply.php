<?php
session_start();

$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reply_id = intval($_POST['reply_id']);
    $new_reply = trim($_POST['reply']);

    $mediaPath = NULL;
    if (!empty($_FILES['media']['name'])) {
        $filename = time() . "_" . basename($_FILES['media']['name']);
        $targetDir = "uploads/replies/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $targetFile = $targetDir . $filename;
        move_uploaded_file($_FILES['media']['tmp_name'], $targetFile);
        $mediaPath = $targetFile;
    }

    $stmtCheck = $conn->prepare("SELECT * FROM replies WHERE id = ? AND user_id = ?");
    $stmtCheck->bind_param("ii", $reply_id, $userId);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();

    if ($result->num_rows > 0) {
        if ($mediaPath) {
            $stmtUpdate = $conn->prepare("UPDATE replies SET reply = ?, media = ? WHERE id = ? AND user_id = ?");
            $stmtUpdate->bind_param("ssii", $new_reply, $mediaPath, $reply_id, $userId);
        } else {
            $stmtUpdate = $conn->prepare("UPDATE replies SET reply = ? WHERE id = ? AND user_id = ?");
            $stmtUpdate->bind_param("sii", $new_reply, $reply_id, $userId);
        }
        $stmtUpdate->execute();
        $stmtUpdate->close();

        echo json_encode(['status' => 'success', 'message' => 'Reply updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'You can only edit your own replies']);
    }

    $stmtCheck->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}

<?php
// react.php
session_start();
header('Content-Type: application/json');

// must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = intval($_SESSION['user_id']);
$post_id = isset($_POST['post_id']) && $_POST['post_id'] !== '' ? intval($_POST['post_id']) : null;
$reply_id = isset($_POST['reply_id']) && $_POST['reply_id'] !== '' ? intval($_POST['reply_id']) : null;
$reaction_type = isset($_POST['reaction_type']) ? trim($_POST['reaction_type']) : '';

$allowed = ['like','love','laugh','sad','angry'];
if (!in_array($reaction_type, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid reaction']);
    exit;
}
if (!$post_id && !$reply_id) {
    echo json_encode(['success' => false, 'error' => 'No target specified']);
    exit;
}

// DB connect (match your community.php credentials)
$conn = new mysqli('localhost', 'u739446465_empowerher_db', 'u739446465_Empowerher_db@', 'u739446465_empowerher_db');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

try {
    // find existing reaction by this user for this target
    if ($post_id) {
        $stmt = $conn->prepare("SELECT id, reaction_type FROM post_reactions WHERE user_id=? AND post_id=? AND reply_id IS NULL LIMIT 1");
        $stmt->bind_param("ii", $userId, $post_id);
    } else {
        $stmt = $conn->prepare("SELECT id, reaction_type FROM post_reactions WHERE user_id=? AND reply_id=? AND post_id IS NULL LIMIT 1");
        $stmt->bind_param("ii", $userId, $reply_id);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $existing = $res->fetch_assoc();
    $stmt->close();

    if ($existing) {
        if ($existing['reaction_type'] === $reaction_type) {
            // same reaction clicked again -> remove (toggle off)
            $del = $conn->prepare("DELETE FROM post_reactions WHERE id=?");
            $del->bind_param("i", $existing['id']);
            $del->execute();
            $del->close();
            $action = 'removed';
        } else {
            // different reaction -> update
            $upd = $conn->prepare("UPDATE post_reactions SET reaction_type=? WHERE id=?");
            $upd->bind_param("si", $reaction_type, $existing['id']);
            $upd->execute();
            $upd->close();
            $action = 'updated';
        }
    } else {
        // insert new reaction
        if ($post_id) {
            $ins = $conn->prepare("INSERT INTO post_reactions (post_id, reply_id, user_id, reaction_type) VALUES (?, NULL, ?, ?)");
            $ins->bind_param("iis", $post_id, $userId, $reaction_type);
        } else {
            $ins = $conn->prepare("INSERT INTO post_reactions (post_id, reply_id, user_id, reaction_type) VALUES (NULL, ?, ?, ?)");
            $ins->bind_param("iis", $reply_id, $userId, $reaction_type);
        }
        $ins->execute();
        $ins->close();
        $action = 'added';
    }

    // return the new count for this reaction type & target
    if ($post_id) {
        $countStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM post_reactions WHERE post_id=? AND reaction_type=?");
        $countStmt->bind_param("is", $post_id, $reaction_type);
    } else {
        $countStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM post_reactions WHERE reply_id=? AND reaction_type=?");
        $countStmt->bind_param("is", $reply_id, $reaction_type);
    }
    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $countRow = $countRes->fetch_assoc();
    $count = intval($countRow['cnt'] ?? 0);
    $countStmt->close();

    echo json_encode(['success' => true, 'action' => $action, 'count' => $count]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error']);
    exit;
}

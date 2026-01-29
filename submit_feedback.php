<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_SESSION['user_id'];
    $category = $_POST['category'] ?? "General";
    $message = trim($_POST['message']);
    $rating = $_POST['rating'] ?? null;
    $bugReport = isset($_POST['bug_report']) ? 1 : 0;

    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO feedbacks (user_id, category, message, bug_report, rating) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issii", $userId, $category, $message, $bugReport, $rating);

        if ($stmt->execute()) {
            $_SESSION['feedback_success'] = "Thank you for your feedback 💖";
        } else {
            $_SESSION['feedback_error'] = "Something went wrong. Please try again.";
        }
        $stmt->close();
    } else {
        $_SESSION['feedback_error'] = "Please enter your feedback.";
    }
}

$conn->close();

// Redirect back to feedback form
header("Location: feedback.php");
exit();
?>

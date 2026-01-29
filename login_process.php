<?php
session_start();

$servername = "localhost";
$username = "u739446465_empowerher_db";
$password = "u739446465_Empowerher_db@";
$dbname = "u739446465_empowerher_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check if email exists
    $stmt = $conn->prepare("SELECT id, fullname, password, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $fullname, $hashed_password, $is_verified);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashed_password)) {

            // ✅ Check if email is verified
            if ($is_verified == 0) {
                $_SESSION['pending_email'] = $email;
                $_SESSION['login_error'] = "Please verify your email before logging in.";
                header("Location: verify.php");
                exit();
            }

            // ✅ Verified — login success
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $fullname;
            header("Location: dashboard.php");
            exit();

        } else {
            // ❌ Wrong password
            $_SESSION['login_error'] = "Incorrect email or password!";
            header("Location: login.php");
            exit();
        }
    } else {
        // ❌ Email not found
        $_SESSION['login_error'] = "Incorrect email or password!";
        header("Location: login.php");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>

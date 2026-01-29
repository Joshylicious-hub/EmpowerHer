<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer-master/src/Exception.php';
require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';

// Database connection
$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['code'])) {
    $email = $_SESSION['pending_email'] ?? '';
    $code = trim($_POST['code'] ?? '');

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND verification_code = ?");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE email = ?");
        $update->bind_param("s", $email);
        $update->execute();

        unset($_SESSION['pending_email']);
        $_SESSION['message'] = "✅ Email verified successfully! You can now log in.";
        $_SESSION['msg_type'] = "success";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['message'] = "❌ Invalid verification code! Please check your latest code.";
        $_SESSION['msg_type'] = "error";
    }
}

// Handle resend code
if (isset($_POST['resend'])) {
    $email = $_SESSION['pending_email'] ?? '';
    if ($email) {
        $newCode = rand(100000, 999999);
        $update = $conn->prepare("UPDATE users SET verification_code = ? WHERE email = ?");
        $update->bind_param("ss", $newCode, $email);
        $update->execute();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'contact@empowerherbest.com';
            $mail->Password = 'Jo$huadota123'; // ⚠️ your actual email password
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('contact@empowerherbest.com', 'EmpowerHer');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your New EmpowerHer Verification Code';
            $mail->Body = "
                <h2>Hello!</h2>
                <p>Your new verification code is:</p>
                <h1 style='color:#ff7eb3;'>$newCode</h1>
                <p>Please enter this code to verify your account.</p>
                <p style='color:gray; font-size:14px;'>
                    ⚠️ Only your <b>latest code</b> will work. Previous codes are automatically invalidated.
                </p>
                <p>With 💗,<br><b>EmpowerHer Team</b></p>
            ";
            $mail->send();

            $_SESSION['message'] = "📩 A new verification code has been sent to your email.";
            $_SESSION['msg_type'] = "success";
        } catch (Exception $e) {
            $_SESSION['message'] = "⚠️ Could not resend code. Mailer Error: {$mail->ErrorInfo}";
            $_SESSION['msg_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "⚠️ No pending email found.";
        $_SESSION['msg_type'] = "error";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify Your Email | EmpowerHer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
    }

    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #fdf2f6, #fde8ef); /* soft pink gradient */
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      color: #333;
    }

    .verify-container {
      background: #fff;
      padding: 40px 35px;
      border-radius: 16px;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
      text-align: center;
      width: 100%;
      max-width: 400px;
      animation: fadeInUp 0.6s ease;
    }

    .verify-container h2 {
      font-size: 1.8rem;
      color: #ff7eb3; /* soft pink header */
      font-weight: 600;
      margin-bottom: 10px;
    }

    .verify-container p {
      font-size: 0.95rem;
      color: #666;
      margin-bottom: 25px;
    }

    .form-group {
      margin-bottom: 20px;
      text-align: left;
    }

    label {
      font-size: 0.9rem;
      color: #444;
      margin-bottom: 8px;
      display: block;
    }

    input[type="text"] {
      width: 100%;
      padding: 12px 15px;
      border-radius: 10px;
      border: 1.5px solid #ddd;
      font-size: 1rem;
      outline: none;
      transition: 0.3s;
    }

    input[type="text"]:focus {
      border-color: #ff7eb3;
      box-shadow: 0 0 5px rgba(255, 126, 179, 0.3);
    }

    .verify-btn, .resend-btn {
      width: 100%;
      padding: 12px 0;
      border: none;
      border-radius: 10px;
      color: white;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: 0.3s;
      margin-top: 10px;
    }

    .verify-btn {
      background: #ff7eb3; /* soft pink */
    }

    .resend-btn {
      background: #ff9ec4; /* lighter pink */
    }

    .verify-btn:hover {
      background: #ff67a5;
    }

    .resend-btn:hover {
      background: #ff82b6;
    }

    .message {
      margin-top: 15px;
      padding: 10px;
      border-radius: 10px;
      font-size: 0.9rem;
    }

    .success {
      background-color: #eafaf1;
      color: #2e7d32;
    }

    .error {
      background-color: #fdecec;
      color: #c62828;
    }

    @keyframes fadeInUp {
      from { transform: translateY(20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    @media (max-width: 480px) {
      .verify-container {
        padding: 30px 25px;
      }
    }
  </style>
</head>
<body>
  <div class="verify-container">
    <h2>Email Verification</h2>
    <p>Please enter the 6-digit verification code we sent to your email.</p>

    <?php if (isset($_SESSION['message'])): ?>
      <div class="message <?= $_SESSION['msg_type'] ?>">
        <?= htmlspecialchars($_SESSION['message']) ?>
      </div>
      <?php unset($_SESSION['message']); unset($_SESSION['msg_type']); ?>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Verification Code</label>
        <input type="text" name="code" placeholder="Enter 6-digit code" required>
      </div>
      <button type="submit" class="verify-btn">Verify Email</button>
    </form>

    <form method="POST" style="margin-top:15px;">
      <input type="hidden" name="resend" value="1">
      <button type="submit" class="resend-btn">Resend Verification Code</button>
    </form>
  </div>
</body>
</html>

<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer-master/src/Exception.php';
require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';

$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        // Generate verification code
        $code = rand(100000, 999999);
        $update = $conn->prepare("UPDATE users SET verification_code = ? WHERE email = ?");
        $update->bind_param("ss", $code, $email);
        $update->execute();

        // Send email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'contact@empowerherbest.com';
            $mail->Password = 'Jo$huadota123';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('contact@empowerherbest.com', 'EmpowerHer');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'EmpowerHer Password Reset Code';
            $mail->Body = "
                <h2>Hello!</h2>
                <p>Your password reset verification code is:</p>
                <h1 style='color:#ff7eb3;'>$code</h1>
                <p>Please enter this code on the next page to reset your password.</p>
                <p style='color:gray; font-size:14px;'>⚠️ Only your latest code will work.</p>
                <p>With 💗,<br><b>EmpowerHer Team</b></p>
            ";
            $mail->send();

            $_SESSION['reset_email'] = $email;
            $_SESSION['message'] = "📩 Verification code sent to your email.";
            header("Location: forgot_verify.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['message'] = "⚠️ Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['message'] = "❌ Email not found!";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password | EmpowerHer</title>
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
    background: linear-gradient(135deg, #fff0f6, #ffe6f0); /* soft pink gradient */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  .verify-container {
    background: #fff;
    padding: 40px 35px;
    border-radius: 20px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.08);
    text-align: center;
    width: 100%;
    max-width: 400px;
    animation: fadeInUp 0.8s ease;
  }

  .verify-container h2 {
    font-size: 1.8rem;
    color: #ff7eb3;
    font-weight: 600;
    margin-bottom: 10px;
  }

  .verify-container p {
    font-size: 0.95rem;
    color: #555;
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

  input[type="email"] {
    width: 100%;
    padding: 12px 15px;
    border-radius: 10px;
    border: 2px solid #ffe3f0;
    font-size: 1rem;
    outline: none;
    transition: 0.3s;
  }

  input[type="email"]:focus {
    border-color: #ff7eb3;
    box-shadow: 0 0 8px rgba(255,126,179,0.3);
  }

  .verify-btn {
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
    background: linear-gradient(135deg, #ff7eb3, #ff9966);
  }

  .verify-btn:hover {
    transform: scale(1.03);
    box-shadow: 0 8px 18px rgba(255, 126, 179, 0.3);
  }

  .message {
    margin-top: 15px;
    padding: 12px;
    border-radius: 10px;
    font-size: 0.9rem;
    text-align: center;
  }

  .error {
    background-color: #fde8e8;
    color: #c62828;
  }

  .success {
    background-color: #e8fce8;
    color: #2e7d32;
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
    <h2>Forgot Password</h2>
    <p>Enter your registered email to receive a verification code.</p>

    <?php if(isset($_SESSION['message'])): ?>
        <div class="message error"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>
        <button type="submit" class="verify-btn">Send Verification Code</button>
    </form>
</div>
</body>
</html>

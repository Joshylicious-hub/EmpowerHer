<?php
session_start();
if(!isset($_SESSION['verified_reset']) || !$_SESSION['verified_reset']) {
    header("Location: forgot_password.php");
    exit();
}

$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_SESSION['reset_email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if($password !== $confirm) {
        $_SESSION['message'] = "❌ Passwords do not match!";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, verification_code = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        if($stmt->execute()) {
            unset($_SESSION['reset_email'], $_SESSION['verified_reset']);
            $_SESSION['message'] = "✅ Password reset successfully! You can now log in.";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['message'] = "⚠️ Error updating password.";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password | EmpowerHer</title>
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

  input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    border-radius: 10px;
    border: 2px solid #ffe3f0;
    font-size: 1rem;
    outline: none;
    transition: 0.3s;
  }

  input[type="password"]:focus {
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
    <h2>Reset Password</h2>

    <?php if(isset($_SESSION['message'])): ?>
        <div class="message error"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="password" placeholder="Enter new password" required>
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm new password" required>
        </div>
        <button type="submit" class="verify-btn">Reset Password</button>
    </form>
</div>
</body>
</html>

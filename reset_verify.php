<?php
session_start();
$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = trim($_POST['code'] ?? '');
    $email = $_SESSION['reset_email'] ?? '';

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND verification_code = ?");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['verified_reset'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $_SESSION['message'] = "❌ Invalid verification code!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify Code | EmpowerHer</title>
  <link rel="stylesheet" href="verify.css">
</head>
<body>
  <div class="verify-container">
    <h2>Verify Code</h2>
    <p>Enter the code sent to your email.</p>

    <?php if(isset($_SESSION['message'])): ?>
      <div class="message"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <form method="POST">
      <label>Verification Code</label>
      <input type="text" name="code" placeholder="Enter 6-digit code" required>
      <button type="submit" class="verify-btn">Verify Code</button>
    </form>
  </div>
</body>
</html>

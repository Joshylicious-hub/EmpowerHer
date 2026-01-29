<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files
require __DIR__ . '/PHPMailer-master/src/Exception.php';
require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';

// Database connection
$servername = "localhost";
$username = "u739446465_empowerher_db";
$password = "u739446465_Empowerher_db@";
$dbname = "u739446465_empowerher_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');

// ✅ Gmail-only check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match("/@gmail\.com$/i", $email)) {
        $_SESSION['message'] = "❌ Please use a valid Gmail address to register.";
        $_SESSION['msg_type'] = "error";
        header("Location: register.php");
        exit();
    }
    
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';

    if ($password !== $confirm_password) {
        $_SESSION['message'] = "Passwords do not match!";
        $_SESSION['msg_type'] = "error";
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $_SESSION['message'] = "This email is already registered!";
            $_SESSION['msg_type'] = "error";
        } else {
            // Combine first and last name for fullname
            $fullname = $firstname . ' ' . $lastname;
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $verification_code = rand(100000, 999999);

            // Insert unverified user
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, verification_code, is_verified) VALUES (?, ?, ?, ?, 0)");
            $stmt->bind_param("ssss", $fullname, $email, $hashed_password, $verification_code);

            if ($stmt->execute()) {
                // Send verification email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.hostinger.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'contact@empowerherbest.com';
                    $mail->Password = 'Jo$huadota123';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;

                    $mail->setFrom('contact@empowerherbest.com', 'EmpowerHer');
                    $mail->addAddress($email, $fullname);
                    $mail->isHTML(true);
                    $mail->Subject = 'Your EmpowerHer Verification Code';
                    $mail->Body = "
                        <h2>Welcome to EmpowerHer!</h2>
                        <p>Your verification code is:</p>
                        <h3 style='color:#ff7eb3;'>$verification_code</h3>
                        <p>Please enter this code on the verification page to activate your account.</p>
                    ";

                    $mail->send();
                    $_SESSION['message'] = "A verification code has been sent to your email.";
                    $_SESSION['msg_type'] = "success";
                    $_SESSION['pending_email'] = $email;
                    header("Location: verify.php");
                    exit();
                } catch (Exception $e) {
                    $_SESSION['message'] = "Mailer Error: " . $mail->ErrorInfo;
                    $_SESSION['msg_type'] = "error";
                }
            } else {
                $_SESSION['message'] = "Error: " . $stmt->error;
                $_SESSION['msg_type'] = "error";
            }

            $stmt->close();
        }

        $check->close();
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EmpowerHer - Register</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="register.css">
  <link rel="icon" type="image/png" sizes="34x34" href="images/logo7.png">
<link rel="icon" type="image/png" sizes="64x64" href="images/logo7.png">
<link rel="icon" type="image/png" sizes="192x192" href="images/logo7.png">

  <style>
    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.6);
      justify-content: center;
      align-items: center;
    }
    .modal-content {
      background: #fff;
      padding: 30px 40px;
      border-radius: 16px;
      text-align: center;
      max-width: 400px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.3);
      font-family: 'Raleway', sans-serif;
      animation: fadeIn 0.3s ease-in-out;
    }
    .modal-content.success { border-top: 5px solid #4CAF50; }
    .modal-content.error { border-top: 5px solid #f44336; }
    .modal-content h2 { margin-bottom: 20px; font-weight: 600; color: #333; }
    .modal-content button {
      padding: 10px 25px;
      border-radius: 50px;
      border: none;
      background: linear-gradient(90deg, #ff758c, #ff7eb3);
      color: #fff;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
    }
    .modal-content button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(255,118,147,0.4);
    }
    @keyframes fadeIn {
      from {opacity:0; transform: translateY(-20px);}
      to {opacity:1; transform: translateY(0);}
    }
    
    
  /* Name row styling - always side by side */
.name-row {
    display: flex;
    gap: 12px; /* space between first and last name */
}

.name-row .form-group {
    flex: 1; /* equal width */
}

/* Optional: make sure inputs shrink nicely on small screens */
.name-row input[type="text"] {
    width: 100%;
    box-sizing: border-box;
}


/* Adjust container padding/margin for mobile */
@media (max-width: 480px) {
    .right-section .register-box {
        padding: 25px 20px; /* reduce top and side padding */
        margin-top: -90px;   /* reduce extra space on top */
    }

}


  </style>
</head>
<body>

<div class="container">
  <div class="left-section">
    <div class="overlay">
      <h1>Welcome to <span>EmpowerHer</span></h1>
      <p>Join our community of inspiring women and discover a space for growth, support, and empowerment.</p>
    </div>
  </div>

  <div class="right-section">
    <div class="register-box">
      <h2>Create Account</h2>
     <form action="" method="POST">
        <div class="name-row">
          <div class="form-group">
            <label for="firstname">First Name</label>
            <input type="text" id="firstname" name="firstname" placeholder="Enter your first name" required>
          </div>

          <div class="form-group">
            <label for="lastname">Last Name</label>
            <input type="text" id="lastname" name="lastname" placeholder="Enter your last name" required>
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Create a password" required>
        </div>

        <div class="form-group">
          <label for="confirm-password">Confirm Password</label>
          <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required>
        </div>

        <button type="submit" class="register-btn">Sign Up</button>
      </form>
      <p class="login-link">Already a member? <a href="login.php">Login here</a></p>
    </div>
  </div>
</div>

<!-- Modal -->
<?php if(isset($_SESSION['message'])): ?>
<div class="modal" id="modal">
  <div class="modal-content <?php echo $_SESSION['msg_type']; ?>">
    <h2><?php echo $_SESSION['message']; ?></h2>
    <button onclick="closeModal()">OK</button>
  </div>
</div>
<?php 
unset($_SESSION['message']); 
unset($_SESSION['msg_type']); 
?>
<?php endif; ?>

<script>
  const modal = document.getElementById('modal');
  if(modal) modal.style.display = 'flex';
  function closeModal() { modal.style.display = 'none'; }
</script>

</body>
</html>

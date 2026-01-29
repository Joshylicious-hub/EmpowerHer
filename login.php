<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EmpowerHer - Login</title>
  <link rel="icon" type="image/png" sizes="34x34" href="images/logo7.png">
<link rel="icon" type="image/png" sizes="64x64" href="images/logo7.png">
<link rel="icon" type="image/png" sizes="192x192" href="images/logo7.png">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="login.css">
  <style>
    .login-error {
  background: #f44336;
  color: #fff;
  padding: 12px 20px;
  border-radius: 12px;
  margin-bottom: 20px;
  text-align: center;
  font-weight: 600;
  
}

.forgot-password-container {
  text-align: center;
  margin-top: -10px;
  margin-bottom: 20px;
}

.forgot-password-link {
   color: #ff8c00; /* Dark orange color */
  font-weight: 600;
  text-decoration: none;
  font-size: 0.95rem;
  display: inline-block;
  transition: all 0.3s ease;
}

.forgot-password-link:hover {
  color: #ff69b4;
  transform: scale(1.05);
}

.forgot-password-link i {
  margin-right: 6px;
  color: #ff69b4;
}



/* Adjust login card padding/margin for mobile */
@media (max-width: 480px) {
    .login-right .login-card {
        padding: 25px 20px;   /* reduce padding for small screens */
        margin-top: -100px;   /* adjust top space to move card up */
    }
}

  </style>
</head>
<body>



  <div class="login-page">

    <div class="login-container">

      
      <div class="login-left">
        <div class="overlay"></div>
        <h1>Welcome!</h1>
        <p>Empower yourself and your children. Log in to your EmpowerHer account to access resources, mentorship, and support.</p>
        <img src="images/singleparent.jpg" alt="EmpowerHer Support" class="login-image">
        <div class="floating-circle circle1"></div>
        <div class="floating-circle circle2"></div>
      </div>

      <!-- back button -->
      <div class="login-right">
        <div class="login-card">
          <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
          <h2>Login</h2>


            <?php
  session_start();
  if(isset($_SESSION['login_error'])){
      echo '<div class="login-error">'.$_SESSION['login_error'].'</div>';
      unset($_SESSION['login_error']);
  }
  ?>

       <form action="login_process.php" method="POST">
  <div class="input-group">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" placeholder="Enter your email" required>
  </div>

  <div class="input-group">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" placeholder="Enter your password" required>
  </div>

  <div class="forgot-password-container">
    <a href="forgot_password.php" class="forgot-password-link">
      <i></i> Forgot Password?
    </a>
  </div>

  <button type="submit" class="btn-login">Login</button>
</form>

          <p class="signup-link">Don't have an account? <a href="register.php">Sign Up</a></p>
        </div>
      </div>

    </div>

  </div>

</body>
</html>

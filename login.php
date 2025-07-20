<?php
// Start the session
session_start();

// Include database connection file
include("connection.php");

// Initialize the error variable
$error = "";

// Check if form is submitted
if (isset(
    $_POST["login"]
)) {
    // Define email and password variables and prevent SQL injection
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = $_POST['password']; // No need to escape this as it's not directly used in the query

    // Fetch user from database based on email
    $sql = "SELECT id, email, role, password, active, names, image,campus FROM users WHERE email='$email'";
    $result = mysqli_query($connection, $sql);

    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        // Verify hashed password retrieved from the database
        if (password_verify($password, $row['password'])) {
            if ($row['active'] == '1') {
                // Password is correct, start a new session
                $_SESSION['loggedin'] = true;
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['id'] = $row['id'];
                $_SESSION['campus'] = $row['campus'];

                echo 'ready to go..........';
                echo "<script>window.location.href='./dashboard/users-profile.php'</script>";
                exit; // Exit script after redirection
            } else {
                $error = "Sorry! Your account is deactivated by the admin.";
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | UR-OFFICES</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap CSS -->
  <link href="./dashboard/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="./dashboard/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="./dashboard/assets/css/style.css" rel="stylesheet">
  <style>
    body, html {
      height: 100%;
      margin: 0;
      font-family: 'Nunito', sans-serif;
      background: #f7f7fa;
    }
    .split-container {
      display: flex;
      min-height: 100vh;
    }
    .login-left {
      flex: 1;
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 40px 30px;
    }
    .login-right {
      flex: 1;
      background: #00628b;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
    }
    .login-logo {
      display: flex;
      align-items: justify;
      margin-bottom: 40px;
      font-size: 40px;
      font-weight: bold;
    }
    .login-logo img {
      height: 40px;
      /* margin-right: 10px; */
    }
    .login-form {
      width: 100%;
      max-width: 400px;
    }
    .google-btn {
      background: #fff;
      color: #444;
      border: 1px solid #ddd;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .google-btn img {
      height: 20px;
    }
    .login-right img {
      max-width: 80%;
      height: auto;
    }
    .form-check-label {
      margin-left: 5px;
    }
    .form-footer {
      text-align: center;
      margin-top: 20px;
    }
    .forgot-link {
      float: right;
    }
    /* Hide illustration on mobile */
    @media (max-width: 900px) {
      .split-container {
        flex-direction: column;
      }
      .login-right {
        display: none !important;
      }
    }
    /* Loading overlay styles */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(255,255,255,0.7);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      display: none;
    }
  </style>
</head>
<body>
  <!-- Loading Spinner Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
      <span class="visually-hidden">Loading...</span>
    </div>
  </div>
  <div class="split-container">
    <!-- Left: Login Form -->
    <div class="login-left">
      
      <div class="login-form">
      <div class="login-logo">
        <!-- <img src="./dashboard/assets/img/icon1.png" alt="Logo"> -->
        <!-- Login -->
      </div>
        <h2 class="mb-2">Welcome back</h2>
        <p class="mb-4 text-muted">Please enter your details</p>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" action="login.php" id="loginForm">
          <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <div class="mb-3 d-flex justify-content-between align-items-center">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="remember" name="remember">
              <label class="form-check-label" for="remember">Remember for 30 days</label>
            </div>
            <a href="reset.php" class="forgot-link" style="color:#00628b;">Forgot password</a>
          </div>
          <button type="submit" class="btn  w-100 mb-2" name="login" style="background-color:#00628b;color:white">Sign in</button>
          
        </form>
       
      </div>
    </div>
    <!-- Right: Illustration -->
    <div class="login-right">
      <img src="./office-removebg-preview.png" alt="Illustration">
      <!-- Use your own SVG or PNG illustration here -->
    </div>
  </div>
  <script>
    // Show loading overlay on form submit
    document.getElementById('loginForm').addEventListener('submit', function() {
      document.getElementById('loadingOverlay').style.display = 'flex';
    });
  </script>
</body>
</html>

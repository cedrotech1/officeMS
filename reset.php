<?php
// Include database connection file
include("connection.php");

include("dashboard/functions.php");
session_start();
// Initialize error and success messages
$error = "";
$success = "";

// Step can now be passed via the URL (default is 1)
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$email = isset($_GET['email']) ? mysqli_real_escape_string($connection, $_GET['email']) : '';

// Check if form is submitted
if (isset($_POST["reset"])) {
    // Step 1: Requesting reset code (email provided)
    if ($step === 1) {
        $email = mysqli_real_escape_string($connection, $_POST['email']);
        
        // Fetch user from database based on email
        $sql = "SELECT id, names, phone, active FROM users WHERE email='$email'";
        $result = mysqli_query($connection, $sql);
        
        if ($result && mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);
            if ($row['active'] == '1') {
                $names = $row['names'];
                $phone = $row['phone'];
                
                // Generate a reset code
                $resetCode = rand(100000, 999999); // Generate a random 6-digit reset code
                
                // Update the user's reset code in the database
                $sqlUpdate = "UPDATE users SET resetcode='$resetCode' WHERE email='$email'";
                mysqli_query($connection, $sqlUpdate);
                $_SESSION['code'] = $resetCode;
                // Send the reset code to user's email and phone
                $subject = "OfficeMS Password Reset Code";
                $message = "Dear $names,<br>Your OfficeMS password reset code is: <b>$resetCode</b>";
                sendEmail($email, $subject, $message);
                if (!empty($phone)) {
                  sendSMS($phone, "Your OfficeMS password reset code is: $resetCode");
                }

                // Redirect to step 2 with the email in the URL
                header("Location: reset.php?step=2&email=" . urlencode($email));
                exit;
            } else {
                $error = "This account is deactivated.";
            }
        } else {
            $error = "Email not found.";
        }
    }

    // Step 2: Verifying the reset code
    if ($step === 2) {
        $resetCode = mysqli_real_escape_string($connection, $_POST['reset_code']);
        
        // Fetch user based on email and reset code
        $sql = "SELECT id FROM users WHERE email='$email' AND resetcode='$resetCode'";
        $result = mysqli_query($connection, $sql);
        
        if ($result && mysqli_num_rows($result) === 1) {
            $_SESSION['code'] = $resetCode;
            header("Location: reset.php?step=3&email=" . urlencode($email));
            exit;
        } else {
            $error = "Invalid reset code.";
        }
    }

    // Step 3: Resetting the password
    if ($step === 3) {
        $newPassword = mysqli_real_escape_string($connection, $_POST['new_password']);
        $confirmPassword = mysqli_real_escape_string($connection, $_POST['confirm_password']);
  
        if ($newPassword === $confirmPassword) {
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update the password in the database
            $sqlUpdate = "UPDATE users SET password='$hashedPassword', resetcode=NULL WHERE email='$email'";
            if (mysqli_query($connection, $sqlUpdate)) {
                $success = "Your password has been successfully reset.";
                session_destroy();
                header("Location: login.php?reset=success");
                exit;
            } else {
                $error = "Failed to reset password. Please try again.";
            }
        } else {
            $error = "Passwords do not match.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Reset Password</title>
  <link href="./dashboard/assets/img/icon1.png" rel="icon">
  <link href="./dashboard/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
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
    .login-right img {
      max-width: 80%;
      height: auto;
    }
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
    @media (max-width: 900px) {
      .split-container {
        flex-direction: column;
      }
      .login-right {
        display: none !important;
      }
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
    <div class="login-left">
      <!-- Place the original card/form here -->
      <main style="width:100%;max-width:430px;">
        <div class="container p-0">
          <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4" style="min-height:auto !important;">
            <div class="container p-0">
              <div class="row justify-content-center">
                <div class="col-12 d-flex flex-column align-items-center justify-content-center p-0">
                  <div class="card mb-3 w-100">
                    <div class="card-body">
                      <div class="pt-4 pb-2">
                        <div class="row">
                          <img class="logo1" src="./assets/img/ur.png" alt="">
                        </div>
                        <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                          <?php echo $error; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                          <?php echo $success; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($step === 1): ?>
                          <h2 class="mb-2">Authentication  1. </h2>
                        <div class="mb-3 text-muted small">Enter your email to receive a password reset code.thruth that email or your phone number !</div>
                        <form method="post" action="reset.php?step=1" class="reset-form-step">
                          <div class="col-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                          </div>
                          <br>
                          <div class="col-12">
                            <button class="btn  w-100" name="reset" type="submit" style="background-color:#00628b;color:white">Send Reset Code</button>
                          </div>
                        </form>
                        <?php elseif ($step === 2): ?>
                          <h2 class="mb-2">Authentication  2. </h2>
                        <div class="mb-3 text-muted small">Enter the code sent to your email address.</div>
                        <form method="post" action="reset.php?step=2&email=<?php echo urlencode($email); ?>" class="reset-form-step">
                          <div class="col-12">
                            <label for="reset_code" class="form-label">Enter Reset Code</label>
                            <input type="text" name="reset_code" class="form-control" required>
                          </div>
                          <br>
                          <div class="col-12">
                            <button class="btn  w-100" name="reset" type="submit" style="background-color:#00628b;color:white">Verify Code</button>
                          </div>
                        </form>
                        <?php elseif ($step === 3 && isset($_SESSION['code'])): ?>
                          <h2 class="mb-2">Set New Password. </h2>
                        <div class="mb-3 text-muted small">Set your new password below.</div>
                        <form method="post" action="reset.php?step=3&email=<?php echo urlencode($email); ?>" class="reset-form-step">
                          <div class="col-12">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                          </div>
                          <div class="col-12">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                          </div>
                          <br>
                          <div class="col-12">
                            <button class="btn  w-100" name="reset" type="submit" style="background-color:#00628b;color:white">Reset Password</button>
                          </div>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-danger">
                            <p>Invalid step. Please try again.</p>
                        </div>
                        <button class="btn btn-outline-primary"><a href="reset.php">Try again </a></button>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>
      </main>
    </div>
    <div class="login-right">
      <img src="./office-removebg-preview.png" alt="Illustration">
    </div>
  </div>
  <script>
    // Show loading overlay on any reset form submit
    document.querySelectorAll('.reset-form-step').forEach(function(form) {
      form.addEventListener('submit', function() {
        document.getElementById('loadingOverlay').style.display = 'flex';
      });
    });
  </script>
  <script src="assets/js/main.js"></script>
</body>
</html>

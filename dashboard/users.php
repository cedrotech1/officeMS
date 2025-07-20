<?php
session_start();
include('../connection.php');
include('functions.php'); // Add this to use sendEmail and sendSMS
?>

<?php
// Add a variable to store form errors
$form_error = '';
// AJAX handler for Add User
if (isset($_POST['ajax_add_user'])) {
  header('Content-Type: application/json');
  $names = mysqli_real_escape_string($connection, $_POST['names']);
  $email = mysqli_real_escape_string($connection, $_POST['email']);
  $phone = mysqli_real_escape_string($connection, $_POST['phone']);
  $campus = mysqli_real_escape_string($connection, $_POST['campus']);
  $role = 'officer';
  // Check if email or phone already exists
  $check_query = "SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1";
  $check_stmt = $connection->prepare($check_query);
  $check_stmt->bind_param("ss", $email, $phone);
  $check_stmt->execute();
  $check_stmt->store_result();
  if ($check_stmt->num_rows > 0) {
    $check_stmt->close();
    echo json_encode(['success' => false, 'error' => 'Email or phone already exists!']);
    exit;
  }
  $check_stmt->close();
  $stmt = $connection->prepare("INSERT INTO users (names, email, phone, campus, role) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("sssss", $names, $email, $phone, $campus, $role);
  if ($stmt->execute()) {
    // Notify user via email and SMS
    $subject = "Welcome to OfficeMS";
    $message = "Dear Officer,<br>Your account has been created. Email: $email, Phone: $phone, Campus: $campus.";
    sendEmail($email, $subject, $message);
    sendSMS($phone, "Your OfficeMS account was created. Email: $email, Campus: $campus.");
    $stmt->close();
    echo json_encode(['success' => true]);
    exit;
  } else {
    $error = $stmt->error;
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'Error: ' . $error]);
    exit;
  }
}
// AJAX handler for loading users table
if (isset($_GET['ajax_load_users'])) {
  include 'users_table_ajax.php';
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Users - UR-HUYE</title>
  <meta content="" name="description">
  <meta content="" name="keywords">
  <link href="assets/img/icon1.png" rel="icon">
  <link href="assets/img/icon1.png" rel="apple-touch-icon">
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  
</head>
<body>
<?php
include ("./includes/header.php");  
include ("./includes/menu.php");
?>
<main id="main" class="main">
  <div class="pagetitle">
    <h1><i class="bi bi-bar-chart"></i> Users</h1>
  </div>
  <section class="section dashboard">
  <div class="container mt-4">
  <?php if (isset($_SESSION['msg'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
  <?php endif; ?>
  <div class="mb-3">
    <button class="btn btn-success" type="button" id="toggleAddUserForm">
      <i class="bi bi-plus"></i> Add User
    </button>
  </div>
    <div class="row">
     <div class="col-md-4">
  <div id="addUserFormContainer" class=" card p-3 collapse <?php if (!empty($form_error) || isset($_POST['add_user'])) echo 'show'; ?> mb-4">
   
 
  
       <div id="formErrorAlert" class="alert alert-danger d-none"></div>
    <div id="formSuccessAlert" class="alert alert-success d-none"></div>
    <form method="POST" id="addUserForm" autocomplete="off">
      <div class="mb-3">
        <label>Names</label>
        <input type="text" name="names" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Campus</label>
        <select name="campus" class="form-control" required>
          <option value="">-- Select Campus --</option>
          <option value="huye">Huye</option>
          <option value="nyagatare">Nyagatare</option>
          <option value="rukara">Rukara</option>
          <option value="nyarugenge">Nyarugenge</option>
          <option value="busogo">Busogo</option>
          <option value="remera">Remera</option>
        </select>
      </div>
      <div class="mb-3 text-end">
        <button type="submit" class="btn btn-primary" id="addUserBtn">
          <span id="addUserBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
          <span id="addUserBtnText">Add User</span>
        </button>
        <button type="reset" class="btn btn-secondary ms-2">Reset</button>
      </div>
    </form>

   </div>
   
   </div>
   
  </div>
<script>
// Toggle dropdown form
$('#toggleAddUserForm').on('click', function() {
  $('#addUserFormContainer').toggleClass('show');
});
// Reset button for Add User form
$('#addUserForm').on('reset', function() {
  $('#formErrorAlert').addClass('d-none').text('');
  $('#formSuccessAlert').addClass('d-none').text('');
});
// AJAX load users table
function loadUsersTable(page = 1) {
  var search = $('#userSearchInput').val();
  var campus = $('#userCampusInput').val();
  $.post('users_table_ajax.php', { page: page, search: search, campus: campus }, function(data) {
    $('#usersTableContainer').html(data);
  });
}
// Initial load
$(document).ready(function() {
  loadUsersTable();
  $('#userSearchInput, #userCampusInput').on('input change', function() {
    loadUsersTable(1);
  });
});
// Handle pagination click (delegated)
$(document).on('click', '.pagination a', function(e) {
  e.preventDefault();
  let page = $(this).data('page');
  if (page) loadUsersTable(page);
});
// AJAX Add User
$('#addUserForm').on('submit', function(e) {
  e.preventDefault();
  $('#formErrorAlert').addClass('d-none').text('');
  $('#formSuccessAlert').addClass('d-none').text('');
  $('#addUserBtn').prop('disabled', true);
  $('#addUserBtnSpinner').removeClass('d-none');
  $('#addUserBtnText').text('Adding...');
  $.ajax({
    url: '',
    type: 'POST',
    data: $(this).serialize() + '&ajax_add_user=1',
    dataType: 'json',
    success: function(response) {
      $('#addUserBtn').prop('disabled', false);
      $('#addUserBtnSpinner').addClass('d-none');
      $('#addUserBtnText').text('Add User');
      if (response.success) {
        $('#formSuccessAlert').removeClass('d-none').text('User added successfully!');
        $('#addUserForm')[0].reset();
        loadUsersTable(1); // Reload users table to show new user
        setTimeout(function() {
          $('#formSuccessAlert').addClass('d-none');
          $('#addUserFormContainer').removeClass('show');
        }, 1500);
      } else {
        $('#formErrorAlert').removeClass('d-none').text(response.error);
      }
    },
    error: function() {
      $('#addUserBtn').prop('disabled', false);
      $('#addUserBtnSpinner').addClass('d-none');
      $('#addUserBtnText').text('Add User');
      $('#formErrorAlert').removeClass('d-none').text('An error occurred. Please try again.');
    }
  });
});
</script>

   <!-- Filters -->
   <!-- The users table should be loaded into a container like: -->
<div class="row g-3 mb-3" id="filterRow">
  <div class="col-md-3">
    <input type="text" id="userSearchInput" class="form-control" placeholder="Search email or phone">
  </div>
  <div class="col-md-3">
    <select id="userCampusInput" class="form-control">
      <option value="">All Campuses</option>
      <option value="huye">Huye</option>
      <option value="nyagatare">Nyagatare</option>
      <option value="rukara">Rukara</option>
      <option value="nyarugenge">Nyarugenge</option>
      <option value="busogo">Busogo</option>
      <option value="remera">Remera</option>
    </select>
  </div>
</div>
<div id="usersTableContainer"></div>

   <!-- Users Table -->
   <!-- The users table should be loaded into a container like: -->
<div id="usersTableContainer"></div>
  </section>
</main>
<?php
include ("./includes/footer.php");
?>
<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
<script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/chart.js/chart.umd.js"></script>
<script src="assets/vendor/echarts/echarts.min.js"></script>
<script src="assets/vendor/quill/quill.min.js"></script>
<script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
<script src="assets/vendor/tinymce/tinymce.min.js"></script>
<script src="assets/vendor/php-email-form/validate.js"></script>
<script src="assets/js/main.js"></script>
<script>
// Show modal if there was a form error
<?php if (!empty($form_error)): ?>
  var addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
  window.addEventListener('DOMContentLoaded', function() {
    addUserModal.show();
  });
<?php endif; ?>
// Loading spinner on Add User button
const addUserForm = document.getElementById('addUserForm');
if (addUserForm) {
  addUserForm.addEventListener('submit', function() {
    document.getElementById('addUserBtn').disabled = true;
    document.getElementById('addUserBtnSpinner').classList.remove('d-none');
    document.getElementById('addUserBtnText').textContent = 'Adding...';
  });
}
</script>
</body>
</html> 
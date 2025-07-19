<?php

include('../connection.php');
// AJAX: User List Filter/Search
if (isset($_GET['ajax_user_list'])) {
  $search = isset($_GET['search']) ? trim($_GET['search']) : '';
  $status = isset($_GET['status']) ? $_GET['status'] : '';
  $where = '1=1';
  if ($search !== '') {
    $search_esc = mysqli_real_escape_string($connection, $search);
    $where .= " AND (names LIKE '%$search_esc%' OR email LIKE '%$search_esc%' OR phone LIKE '%$search_esc%')";
  }
  $users = mysqli_query($connection, "SELECT * FROM users WHERE  $where ORDER BY names");
  $user_rows = [];
  if ($users) {
    while ($u = mysqli_fetch_assoc($users)) {
      $alloc = mysqli_query($connection, "SELECT allocation.id AS alloc_id, allocation.*, offices.* FROM allocation JOIN offices ON allocation.office_id = offices.id WHERE allocation.user_id = " . intval($u['id']) . " LIMIT 1");
      $office = mysqli_fetch_assoc($alloc);
      $alloc_status = $office ? ($office['status'] ?? 'pending') : 'non-allocated';
      // Status filter
      if ($status && $status !== $alloc_status && !($status === 'non-allocated' && !$office)) continue;
      ob_start();
      ?>
      <tr>
        <td><img src="<?= htmlspecialchars($u['image']) ?>" alt="img" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;border:1px solid #ccc;"></td>
        <td><?= htmlspecialchars($u['names']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= htmlspecialchars($u['phone']) ?></td>
        <td><?= htmlspecialchars($u['role']) ?></td>
        <td>
          <?php if ($office): ?>
            <span class="badge bg-info"> <?= htmlspecialchars($office['building_name']) ?> (<?= htmlspecialchars($office['room_code']) ?>)
              </span><br>
            <?php
              $status_disp = $office['status'] ?? 'pending';
              if ($status_disp == 'approved') echo '<span class="badge bg-success">Approved</span>';
             
              else echo '<span class="badge bg-warning text-d ark">Pending</span>';
            ?>
          <?php else: ?>
            <span class="text-muted">None</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($office): ?>
            <?php if (($office['status'] ?? 'pending') === 'pending'): ?>
              <button class="btn btn-success btn-sm approve-allocation-btn" data-alloc-id="<?= $office['alloc_id'] ?>" data-user-id="<?= $u['id'] ?>">Approve</button>
            <?php endif; ?>
            <?php if (($office['status'] ?? 'pending') === 'approved'): ?>
              <button class="btn btn-warning btn-sm reallocate-office-btn" data-user-id="<?= $u['id'] ?>" data-user-name="<?= htmlspecialchars($u['names']) ?>">Reallocate</button>
            <?php endif; ?>
            <button class="btn btn-danger btn-sm remove-office-btn" data-alloc-id="<?= $office['alloc_id'] ?>" data-user-id="<?= $u['id'] ?>">Remove</button>
            <button class="btn btn-info btn-sm view-user-office-btn" data-user-id="<?= $u['id'] ?>">View Office</button>
          <?php else: ?>
            <button class="btn btn-primary btn-sm assign-office-btn" data-user-id="<?= $u['id'] ?>" data-user-name="<?= htmlspecialchars($u['names']) ?>">Allocate Office</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php
      $user_rows[] = [
        'row_html' => ob_get_clean()
      ];
    }
  }
  header('Content-Type: application/json');
  echo json_encode(['users' => $user_rows]);
  exit;
}
// Handle user upload
$upload_msg = '';
if (isset($_POST['import']) && isset($_FILES['excelFile'])) {
  $file = $_FILES['excelFile']['tmp_name'];
  $filename = $_FILES['excelFile']['name'];
  $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
  $data = [];
  $errors = [];
  $requiredHeaders = [
    'names', 'email', 'phone', 'role', 'image'
  ];
  if ($ext === 'csv') {
    if (($handle = fopen($file, 'r')) !== false) {
      $header = fgetcsv($handle);
      $headerNorm = array_map(function($h) { return strtolower(trim($h)); }, $header);
      $missing = array_diff($requiredHeaders, $headerNorm);
      if (count($missing) > 0) {
        $upload_msg = '<div class="alert alert-danger mt-3">Missing required headers: '.implode(', ', $missing).'. Upload aborted.</div>';
        fclose($handle);
      } else {
        $headerMap = array_flip($headerNorm);
        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
          $rowNum++;
          $rowAssoc = [];
          foreach ($requiredHeaders as $h) {
            $idx = $headerMap[$h];
            $rowAssoc[$h] = isset($row[$idx]) ? trim($row[$idx]) : '';
          }
          // Validation
          if (!filter_var($rowAssoc['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email at row $rowNum.";
          }
          if ($rowAssoc['names'] === '' || $rowAssoc['role'] === '') {
            $errors[] = "Missing required data at row $rowNum.";
          }
          $data[] = $rowAssoc;
        }
        fclose($handle);
        if (count($errors) > 0) {
          $upload_msg = '<div class="alert alert-danger mt-3"><b>Upload aborted due to the following errors:</b><ul><li>' . implode('</li><li>', $errors) . '</li></ul></div>';
        } else {
          foreach ($data as $rowData) {
            $names = mysqli_real_escape_string($connection, $rowData['names']);
            $email = mysqli_real_escape_string($connection, $rowData['email']);
            $phone = mysqli_real_escape_string($connection, $rowData['phone']);
            $role = mysqli_real_escape_string($connection, $rowData['role']);
            $image = isset($rowData['image']) && $rowData['image'] !== '' ? mysqli_real_escape_string($connection, $rowData['image']) : 'upload/av.png';
            $password = password_hash('1234', PASSWORD_BCRYPT);
            mysqli_query($connection, "INSERT INTO users (names, email, phone, role, image, password, active) VALUES ('$names', '$email', '$phone', '$role', '$image', '$password', 1)");
          }
          $upload_msg = '<div class="alert alert-success mt-3">Users uploaded successfully!</div>';
        }
      }
    }
  } else {
    $upload_msg = '<div class="alert alert-danger mt-3">Only CSV files are supported in this demo. Please convert your Excel file to CSV.</div>';
  }
}
if (isset($_GET['ajax_get_offices'])) {
  $offices = [];
  $q = mysqli_query($connection, "SELECT * FROM offices ORDER BY campus_name, building_name, room_code");
  if (!$q) {
    header('Content-Type: text/plain');
    echo 'MySQL error: ' . mysqli_error($connection);
    exit;
  }
  while ($o = mysqli_fetch_assoc($q)) {
    $offices[] = $o;
  }
  header('Content-Type: application/json');
  echo json_encode(['count' => count($offices), 'offices' => $offices]);
  exit;
}
// Handle AJAX for office list (pagination, search, filter)
if (isset($_GET['ajax_office_list'])) {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = 6;
    $offset = ($page - 1) * $per_page;
    $where = '1=1';
    if ($search !== '') {
        $where .= " AND (campus_name LIKE '%$search%' OR building_name LIKE '%$search%' OR building_code LIKE '%$search%' OR room_code LIKE '%$search%')";
    }
    $office_rows = [];
    $q = mysqli_query($connection, "SELECT *, (SELECT COUNT(*) FROM allocation WHERE office_id=offices.id) as occupants FROM offices WHERE $where ORDER BY id DESC LIMIT $offset, $per_page");
    while ($row = mysqli_fetch_assoc($q)) {
        $row['status'] = ($row['occupants'] >= $row['max_occupants']) ? 'Full' : (($row['occupants'] > 0) ? 'Partially Occupied' : 'Available');
        $office_rows[] = $row;
    }
    // Filter by status after fetching
    if ($status !== '') {
        $office_rows = array_filter($office_rows, function($row) use ($status) {
            return $row['status'] === $status;
        });
        $office_rows = array_values($office_rows); // reindex
    }
    $total_offices = count($office_rows);
    $total_pages = ceil($total_offices / $per_page);
    // Paginate filtered results
    $office_rows = array_slice($office_rows, ($page-1)*$per_page, $per_page);
    echo json_encode(['offices' => $office_rows, 'total_pages' => $total_pages, 'current_page' => $page]);
    exit;
}
if (isset($_GET['ajax_get_user_details']) && isset($_GET['user_id'])) {
  $uid = intval($_GET['user_id']);
  $user = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM users WHERE id=$uid"));
  $alloc = mysqli_query($connection, "SELECT offices.*, allocation.status FROM allocation JOIN offices ON allocation.office_id = offices.id WHERE allocation.user_id = $uid LIMIT 1");
  $office = mysqli_fetch_assoc($alloc);
  $result = [
    'names' => $user['names'] ?? '',
    'email' => $user['email'] ?? '',
    'phone' => $user['phone'] ?? '',
    'image' => $user['image'] ?? '',
    'current_office' => $office ? [
      'building_name' => $office['building_name'],
      'building_code' => $office['building_code'],
      'room_code' => $office['room_code'],
      'campus_name' => $office['campus_name'],
      'status' => $office['status']
    ] : null
  ];
  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}
if (isset($_GET['ajax_room_users']) && isset($_GET['room'])) {
  $room = mysqli_real_escape_string($connection, trim($_GET['room']));
  $users = mysqli_query($connection, "SELECT users.*, allocation.id AS alloc_id, allocation.status as alloc_status, offices.* FROM allocation JOIN users ON allocation.user_id = users.id JOIN offices ON allocation.office_id = offices.id WHERE offices.room_code LIKE '%$room%' ORDER BY users.names");
  $user_rows = [];
  if ($users) {
    while ($u = mysqli_fetch_assoc($users)) {
      $alloc_status = $u['alloc_status'] ?? 'pending';
      ob_start();
      ?>
      <tr>
        <td><img src="<?= htmlspecialchars($u['image']) ?>" alt="img" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;border:1px solid #ccc;"></td>
        <td><?= htmlspecialchars($u['names']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= htmlspecialchars($u['phone']) ?></td>
        <td><?= htmlspecialchars($u['role']) ?></td>
        <td>
          <span class="badge bg-info"> <?= htmlspecialchars($u['building_name']) ?> (<?= htmlspecialchars($u['room_code']) ?>) </span><br>
          <?php
            $status_disp = $u['alloc_status'] ?? 'pending';
            if ($status_disp == 'approved') echo '<span class="badge bg-success">Approved</span>';
           
            else echo '<span class="badge bg-warning text-dark">Pending</span>';
          ?>
        </td>
        <td>
          <?php if ($u): ?>
            <?php if (($u['alloc_status'] ?? 'pending') === 'pending'): ?>
              <button class="btn btn-success btn-sm approve-allocation-btn" data-alloc-id="<?= $u['alloc_id'] ?>" data-user-id="<?= $u['id'] ?>">Approve</button>
            <?php endif; ?>
            <?php if (($u['alloc_status'] ?? 'pending') === 'approved'): ?>
              <button class="btn btn-warning btn-sm reallocate-office-btn" data-user-id="<?= $u['id'] ?>" data-user-name="<?= htmlspecialchars($u['names']) ?>">Reallocate</button>
            <?php endif; ?>
            <button class="btn btn-danger btn-sm remove-office-btn" data-alloc-id="<?= $u['alloc_id'] ?>" data-user-id="<?= $u['id'] ?>">Remove</button>
          <?php else: ?>
            <button class="btn btn-primary btn-sm assign-office-btn" data-user-id="<?= $u['id'] ?>" data-user-name="<?= htmlspecialchars($u['names']) ?>">Allocate Office</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php
      $user_rows[] = [ 'row_html' => ob_get_clean() ];
    }
  }
  header('Content-Type: application/json');
  echo json_encode(['users' => $user_rows]);
  exit;
}
// Search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$where = '1=1';
if ($search !== '') {
    $search_esc = mysqli_real_escape_string($connection, $search);
    $where .= " AND (names LIKE '%$search_esc%' OR email LIKE '%$search_esc%' OR phone LIKE '%$search_esc%' OR role LIKE '%$search_esc%')";
}
$total_users = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM users WHERE $where"));
$total_users = $total_users['cnt'];
$total_pages = ceil($total_users / $per_page);
$users = mysqli_query($connection, "SELECT * FROM users WHERE $where ORDER BY names LIMIT $offset, $per_page");
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>UR-HUYE-CARDS</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="./icon1.png" rel="icon">
  <link href="./icon1.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">

  <!-- XLSX and PapaParse libraries -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .hidden { display: none; }
        label { display: block; margin: 10px 0 5px; }
    </style>

</head>

<body>

<?php  
include("./includes/header.php");
include("./includes/menu.php");
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1><i class="bi bi-people"></i> Users List</h1>
  </div>
  <section class="section dashboard">
    <div class="row card p-3">
      <!-- Add Upload Users Button and Modal -->
      <div class="mb-3">
        <button class="btn btn-success" id="openUploadModalBtn"><i class="bi bi-upload"></i> Upload Users (CSV/Excel)</button>
      </div>
      <!-- Upload Users Modal -->
      <div class="modal fade" id="uploadUsersModal" tabindex="-1" aria-labelledby="uploadUsersModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="uploadUsersModalLabel">Upload Users Excel/CSV File</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <a href="template_users.csv" class="btn btn-outline-primary mb-3"><i class="bi bi-download"></i> Download Template</a>
            <form action="user_lists.php" method="post" enctype="multipart/form-data">
              <div class="mb-3">
                <label for="excelFile" class="form-label">Select Excel/CSV File (.xlsx, .xls, .csv)</label>
                <input class="form-control" type="file" name="excelFile" id="excelFile" accept=".xlsx,.xls,.csv" required>
              </div>
              <button type="submit" name="import" class="btn btn-success"><i class="bi bi-upload"></i> Import</button>
            </form>
            <?= $upload_msg ?>
          </div>
        </div>
      </div>
      </div>
      <!-- Organized Filters Card -->
      <div class="card mb-3 p-3 shadow-sm">
        <form class="row g-2 align-items-end" id="userFiltersForm" onsubmit="return false;">
          <div class="col-md-3">
            <input type="text" id="userSearchInput" class="form-control" placeholder="Search by name, email, or phone...">
          </div>
          <div class="col-md-3">
            <input type="text" id="roomNumberSearch" class="form-control" placeholder="Search by Room Number...">
          </div>
          <div class="col-md-3">
            <select id="userStatusFilter" class="form-control">
              <option value="">All Status</option>
              <option value="approved">Approved</option>
              <option value="pending">Pending</option>
             
              <option value="non-allocated">Non-Allocated</option>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="button" class="btn btn-secondary w-100" onclick="$('#userSearchInput').val('');$('#roomNumberSearch').val('');$('#userStatusFilter').val('');loadUsers();">Reset</button>
          </div>
        </form>
      </div>
        <div class="table-responsive">
        <table class="table table-striped align-middle" id="userTable">
            <thead class="table-light">
              <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Allocated Office</th>
              <th>Actions</th>
              </tr>
            </thead>
          <tbody></tbody>
        </table>
      </div>
      <!-- Assign Office Modal -->
      <div class="modal fade" id="assignOfficeModal" tabindex="-1" aria-labelledby="assignOfficeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="assignOfficeModalLabel">Assign Office to <span id="modalUserName"></span></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <!-- Combined User + Office Card -->
              <div class="card mb-3 shadow-sm" id="modalUserCombinedCard" style="max-width: 600px;">
                <div class="row g-0 align-items-center">
                  <div class="col-auto p-3">
                    <img id="modalUserImage" src="" alt="User Image" class="" style="width:200px;height:200px;object-fit:cover;border:2px solid #dee2e6;border-radius:10px">
                  </div>
                  <div class="col">
                    <div class="card-body p-2">
                      <h5 class="card-title mb-1" id="modalUserDetailsName"></h5>
                      <div class="mb-1"><span id="modalUserDetailsEmail" class="text-muted small"></span></div>
                      <div class="mb-2"><span id="modalUserDetailsPhone" class="text-muted small"></span></div>
                      <div id="modalCurrentOfficeCombined" class="mt-2"></div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Office Table and Form -->
              <form id="assignOfficeForm">
                <input type="hidden" id="modalUserId" name="user_id">
                <input type="hidden" id="modalSelectedOfficeId" name="office_id">
                <input type="hidden" id="modalUserIsReallocating" value="0">
                <div class="mb-3 row">
                  <div class="col-md-5">
                    <input type="text" id="modalOfficeSearch" class="form-control" placeholder="Search by building, room number...">
                  </div>
                  <div class="col-md-4">
                    <select id="modalOfficeStatus" class="form-control">
                      <option value="">All Status</option>
                      <option value="Available">Available</option>
                      <option value="Partially Occupied">Partially Occupied</option>
                      <option value="Full">Full</option>
                    </select>
                  </div>
                </div>
                <div class="table-responsive mb-2">
                  <table class="table table-bordered table-striped align-middle" id="modalOfficeTable">
                    <thead class="table-light">
                      <tr>
                        <th>Campus</th>
                        <th>Building</th>
                        <th>Room number</th>
                        <th>Max Occupants</th>
                        <th>Current Occupants</th>
                        <th>Status</th>
                        <th>Select</th>
                </tr>
                    </thead>
                    <tbody></tbody>
          </table>
                </div>
                <nav>
                  <ul class="pagination justify-content-center" id="modalOfficePagination"></ul>
                </nav>
                <div id="assignOfficeMsg"></div>
                <button type="submit" class="btn btn-success" id="modalAssignBtn" disabled>Assign</button>
              </form>
            </div>
          </div>
        </div>
      </div>
      <!-- User/Office Details Modal -->
      <div class="modal fade" id="userOfficeDetailsModal" tabindex="-1" aria-labelledby="userOfficeDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="userOfficeDetailsModalLabel">User & Office Details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="userOfficeDetailsModalBody">
              <!-- AJAX loaded -->
            </div>
          </div>
        </div>
        </div>
        <nav>
          <ul class="pagination justify-content-center">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
              <li class="page-item<?= $p == $page ? ' active' : '' ?>">
                <a class="page-link" href="user_lists.php?page=<?= $p ?>&search=<?= urlencode($search) ?>"> <?= $p ?> </a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
    </div>
  </section>
</main>

<!-- Remove the Offices Overview card and its contents -->
<!-- Occupants Modal -->
<div class="modal fade" id="occupantsModal" tabindex="-1" aria-labelledby="occupantsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="occupantsModalLabel">Office Occupants</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="occupantsModalBody">
        <!-- AJAX loaded -->
      </div>
    </div>
  </div>
</div>

<?php  
include("./includes/footer.php");
?>



<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<!-- Vendor JS Files -->
<script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/chart.js/chart.umd.js"></script>
<script src="assets/vendor/echarts/echarts.min.js"></script>
<script src="assets/vendor/quill/quill.min.js"></script>
<script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
<script src="assets/vendor/tinymce/tinymce.min.js"></script>
<script src="assets/vendor/php-email-form/validate.js"></script>

<!-- Template Main JS File -->
<script src="assets/js/main.js"></script>

<script>
$(document).ready(function() {
  var assignModal = new bootstrap.Modal(document.getElementById('assignOfficeModal'));
  var $modalUserName = $('#modalUserName');
  var $modalUserId = $('#modalUserId');
  var $assignOfficeMsg = $('#assignOfficeMsg');

  // Assign/Request Office
  $(document).on('click', '.assign-office-btn, .reallocate-office-btn', function() {
    var userId = $(this).data('user-id');
    var userName = $(this).data('user-name');
    $modalUserName.text(userName);
    $modalUserId.val(userId);
    $assignOfficeMsg.html('');
    $('#modalSelectedOfficeId').val('');
    $('#modalAssignBtn').prop('disabled', true);
    // Fetch user details and current office via AJAX
    $.get('user_lists.php', { ajax_get_user_details: 1, user_id: userId }, function(resp) {
      var data = {};
      try { data = typeof resp === 'object' ? resp : JSON.parse(resp); } catch(e) { data = {}; }
      // Fill user details
      $('#modalUserImage').attr('src', data.image || 'assets/img/profile1.png');
      $('#modalUserDetailsName').text(data.names || '');
      $('#modalUserDetailsEmail').text(data.email || '');
      $('#modalUserDetailsPhone').text(data.phone || '');
      // Combined current office display
      if (data.current_office) {
        let badge = '';
        if (data.current_office.status === 'approved') badge = "<span class='badge bg-success ms-2'>approved</span>";
        else if (data.current_office.status === 'pending') badge = "<span class='badge bg-warning text-dark ms-2'>pending</span>";
       
        $('#modalCurrentOfficeCombined').html(
          `<div class='mt-2'><b>Current Office</b></div>` +
          `<div><span class='fw-bold'>${data.current_office.building_name}</span> (${data.current_office.room_code})</div>` +
          `<div class='text-muted small'>${data.current_office.campus_name}${badge}</div>`
        );
        $('#modalUserIsReallocating').val('1');
      } else {  
        $('#modalCurrentOfficeCombined').html('');
        $('#modalUserIsReallocating').val('0');
      }
    });
    loadModalOffices(1);
    assignModal.show();
  });

  // Remove allocation
  $(document).on('click', '.remove-office-btn', function() {
    if (!confirm('Remove this user from the office?')) return;
    var allocId = $(this).data('alloc-id');
    var userId = $(this).data('user-id');
    $.post('allocate_office.php', {
      ajax_action: 'remove',
      alloc_id: allocId,
      office_id: '' // not needed for remove
    }, function(resp) {
      console.log('Remove response:', resp);
      try { var data = typeof resp === 'object' ? resp : JSON.parse(resp); } catch(e) { var data = {}; }
      if (data.success) {
        location.reload();
      } else {
        alert('Remove failed: ' + (data.error || data.message || 'Unknown error'));
        console.error('Remove error:', data);
      }
    }).fail(function(xhr, status, error) {
      console.error('AJAX error removing allocation:', status, error, xhr.responseText);
    });
  });

  // Approve allocation
  $(document).on('click', '.approve-allocation-btn', function() {
    var allocId = $(this).data('alloc-id');
    var userId = $(this).data('user-id');
    $.post('allocate_office.php', {
      ajax_action: 'approve',
      alloc_id: allocId,
      office_id: ''
    }, function(resp) {
      console.log('Approve response:', resp);
      try { var data = typeof resp === 'object' ? resp : JSON.parse(resp); } catch(e) { var data = {}; }
      if (data.success) {
        location.reload();
      } else {
        alert('Approve failed: ' + (data.error || data.message || 'Unknown error'));
        console.error('Approve error:', data);
      }
    }).fail(function(xhr, status, error) {
      console.error('AJAX error approving allocation:', status, error, xhr.responseText);
    });
  });

  // Assign office form submit
  $('#assignOfficeForm').on('submit', function(e) {
    e.preventDefault();
    var userId = $('#modalUserId').val();
    var officeId = $('#modalSelectedOfficeId').val();
    if (!officeId) {
      $('#assignOfficeMsg').html('<div class="alert alert-danger">Please select an office.</div>');
      return;
    }
    $('#assignOfficeMsg').html('<div class="text-info">Requesting...</div>');
    $.post('allocate_office.php', {
      ajax_allocate: 1,
      user_ids: [userId],
      office_id: officeId
    }, function(resp) {
      console.log('Assign/Reallocate response:', resp);
      var data = {};
      try { data = typeof resp === 'object' ? resp : JSON.parse(resp); } catch(e) { data = {}; }
      if (data.success) {
        $('#assignOfficeMsg').html('<div class="alert alert-success">Request submitted! Waiting for approval.</div>');
        setTimeout(function() { location.reload(); }, 1000);
      } else {
        $('#assignOfficeMsg').html('<div class="alert alert-danger">'+(data.error || data.message || 'Error requesting office.')+'</div>');
        console.error('Assign/Reallocate error:', data);
      }
    }).fail(function(xhr, status, error) {
      console.error('AJAX error assigning/reallocating:', status, error, xhr.responseText);
    });
  });

  // Modal office table logic
  function loadModalOffices(page=1) {
    const search = $('#modalOfficeSearch').val();
    const status = $('#modalOfficeStatus').val();
    $.get('user_lists.php', { ajax_office_list: 1, search, status, page }, function(resp) {
      let data = {};
      try { data = typeof resp === 'object' ? resp : JSON.parse(resp); } catch(e) { data = {}; }
      const offices = data.offices || [];
      let rows = '';
      offices.forEach(function(o) {
        let badge = o.status === 'Full' ? 'danger' : (o.status === 'Available' ? 'success' : 'warning');
        let disabled = o.status === 'Full' ? 'disabled' : '';
        rows += `<tr>
          <td>${o.campus_name}</td>
          <td>${o.building_name} (${o.building_code})</td>
          <td>${o.room_code}</td>
          <td>${o.max_occupants}</td>
          <td>${o.occupants}</td>
          <td><span class="badge bg-${badge}">${o.status}</span></td>
          <td><button type="button" class="btn btn-outline-primary btn-sm select-office-btn" data-office-id="${o.id}" ${disabled}>Select</button></td>
        </tr>`;
      });
      $('#modalOfficeTable tbody').html(rows);
      // Pagination
      let pag = '';
      for (let p = 1; p <= (data.total_pages || 1); p++) {
        pag += `<li class="page-item${p==data.current_page?' active':''}"><a class="page-link" href="#" onclick="loadModalOffices(${p});return false;">${p}</a></li>`;
      }
      $('#modalOfficePagination').html(pag);
    });
  }
  $('#modalOfficeSearch, #modalOfficeStatus').on('input change', function() { loadModalOffices(1); });
  $(document).on('click', '.select-office-btn', function() {
    const officeId = $(this).data('office-id');
    $('#modalSelectedOfficeId').val(officeId);
    $('.select-office-btn').removeClass('btn-primary').addClass('btn-outline-primary');
    $(this).removeClass('btn-outline-primary').addClass('btn-primary');
    $('#modalAssignBtn').prop('disabled', false);
  });

  $('#openUploadModalBtn').on('click', function() {
    $('#uploadUsersModal').modal('show');
  });

  function loadUsers() {
    var search = $('#userSearchInput').val();
    var status = $('#userStatusFilter').val();
    var room = $('#roomNumberSearch').val(); // Get room number from search input
    $.get('user_lists.php', { ajax_user_list: 1, search, status, room }, function(resp) {
      let data = {};
      try { data = typeof resp === 'object' ? resp : JSON.parse(resp); } catch(e) { data = {}; }
      const users = data.users || [];
      let rows = '';
      if (users.length === 0) {
        rows = '<tr><td colspan="6" class="text-center">No users found.</td></tr>';
      } else {
        users.forEach(function(u) {
          rows += u.row_html;
        });
      }
      $('#userTable tbody').html(rows);
    }).fail(function(xhr, status, error) {
      console.error('AJAX error loading users:', status, error, xhr.responseText);
    });
  }
  $('#userSearchInput, #userStatusFilter').on('input change', function() { loadUsers(); });
  // Initial load
  loadUsers();
});
// Room number search handler
$('#roomNumberSearch').on('input', function() {
  const room = $(this).val().trim();
  if (room.length === 0) {
    // If cleared, reload full user list
    $('#userSearchInput').val('');
    $('#userStatusFilter').val('');
    loadUsers();
    return;
  }
  $.get('user_lists.php', { ajax_room_users: 1, room: room }, function(resp) {
    let data = {};
    try { data = typeof resp === 'object' ? resp : JSON.parse(resp); } catch(e) { data = {}; }
    const users = data.users || [];
    let rows = '';
    if (users.length === 0) {
      rows = '<tr><td colspan="6" class="text-center">No users found for this room.</td></tr>';
    } else {
      users.forEach(function(u) {
        rows += u.row_html;
      });
    }
    $('#userTable tbody').html(rows);
  });
});
// Ensure Reset button works reliably
$('#userFiltersForm').on('submit', function(e) { e.preventDefault(); });
$('#userFiltersForm .btn.btn-secondary').off('click').on('click', function(e) {
  e.preventDefault();
  location.reload();
});
// View User & Office Details Modal
$(document).on('click', '.view-user-office-btn', function() {
  var userId = $(this).data('user-id');
  $('#userOfficeDetailsModalBody').html('<div class="text-center text-muted">Loading...</div>');
  $.get('user_lists.php', { ajax_get_user_details: 1, user_id: userId }, function(resp) {
    var data = {};
    try { data = typeof resp === 'object' ? resp : JSON.parse(resp); } catch(e) { data = {}; }
    let html = '';
    html += `<div class='d-flex align-items-center mb-3'>`;
    html += `<img src='${data.image || 'assets/img/profile1.png'}' alt='img' class='rounded-circle me-3' style='width:60px;height:60px;object-fit:cover;border:1px solid #ccc;'>`;
    html += `<div><h4 class='mb-0'>${data.names || ''}</h4><div class='text-muted small'>${data.email || ''} | ${data.phone || ''}</div></div></div>`;
    if (data.current_office) {
      html += `<div class='card border-primary mb-2' style='max-width:500px;background-color:whitesmoke'><div class='card-body p-3'>`;
      html += `<div class='d-flex align-items-center mb-2'><div class='me-3 display-5 text-primary'><i class='bi bi-building'></i></div><div>`;
      html += `<div class='fw-bold fs-5 mb-1'>${data.current_office.building_name} <span class='badge bg-secondary ms-2'>${data.current_office.building_code || ''}</span></div>`;
      html += `<div class='text-muted small'><i class='bi bi-geo-alt'></i> ${data.current_office.campus_name}</div></div></div>`;
      html += `<div class='mb-2'><span class='fw-bold'><i class='bi bi-door-closed'></i> Office Room Number:</span> ${data.current_office.room_code}</div>`;
      html += `<div class='mb-2'><span class='fw-bold'><i class='bi bi-info-circle'></i> Status:</span> `;
      if (data.current_office.status === 'approved') html += `<span class='badge bg-success ms-2'>Approved</span>`;
      else if (data.current_office.status === 'pending') html += `<span class='badge bg-warning text-dark ms-2'>Pending</span>`;
      else if (data.current_office.status === 'rejected') html += `<span class='badge bg-danger ms-2'>Rejected</span>`;
      html += `</div></div></div>`;
    } else {
      html += `<div class='alert alert-warning'>No office allocated.</div>`;
    }
    $('#userOfficeDetailsModalBody').html(html);
    $('#userOfficeDetailsModal').modal('show');
  });
});
</script>

</body>
</html>

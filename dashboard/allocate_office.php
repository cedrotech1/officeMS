<?php
ob_start();
include('../connection.php');
include('./functions.php');
$office_id = isset($_GET['office_id']) ? intval($_GET['office_id']) : 0;
$success = '';
$error = '';
$info = '';
// Handle AJAX allocation of users
if (isset($_POST['ajax_allocate']) && isset($_POST['user_ids'])) {
    $user_ids = array_map('intval', $_POST['user_ids']);
    $office_id = intval($_POST['office_id']);
    $max_occupants = 0;
    $office = mysqli_fetch_assoc(mysqli_query($connection, "SELECT max_occupants FROM offices WHERE id=$office_id"));
    if ($office) $max_occupants = intval($office['max_occupants']);
    $current_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM allocation WHERE office_id=$office_id"));
    $current_count = $current_count ? intval($current_count['cnt']) : 0;
    $num_to_allocate = count($user_ids);
    // Check if reallocation is needed
    $allocated = 0;
    foreach ($user_ids as $user_id) {
        $check = mysqli_query($connection, "SELECT * FROM allocation WHERE user_id=$user_id");
        $user = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM users WHERE id=$user_id"));
        $office_details = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM offices WHERE id=$office_id"));
        if (mysqli_num_rows($check) > 0) {
            // User already allocated: update allocation to new office and set status to pending
            $alloc = mysqli_fetch_assoc($check);
            // Only update if the office is different
            if ($alloc['office_id'] != $office_id) {
                if ($current_count >= $max_occupants) {
                    if (ob_get_length()) ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Cannot allocate: exceeds maximum occupants.', 'message' => 'Office full during reallocation']);
                    exit;
                }
                $res = mysqli_query($connection, "UPDATE allocation SET office_id=$office_id, status='approved', allocation_date=CURDATE() WHERE id=" . intval($alloc['id']));
                if ($res) {
                    $allocated++;
                    // Notify user about reallocation
                    $type = 'info';
                    $title = 'Office Reallocation';
                    $msg_html = '<p>Dear ' . htmlspecialchars($user['names']) . ',</p>';
                    $msg_html .= '<p>You have been reallocated to office <b>' . htmlspecialchars($office_details['building_name']) . ' (' . htmlspecialchars($office_details['building_code']) . '), Office room number ' . htmlspecialchars($office_details['room_code']) . ', campus ' . htmlspecialchars($office_details['campus_name']) . '</b>.</p>';
                    $msg_html .= '<p>If you have any questions, please contact the office management team.</p>';
                    $msg_html .= '<br><p>Best regards,<br>Office Management System</p>';
                    $msg_plain = 'Dear ' . $user['names'] . ',You have been reallocated to office ' . $office_details['building_name'] . ' (' . $office_details['building_code'] . '), Office room number ' . $office_details['room_code'] . ', campus ' . $office_details['campus_name'] . '.';
                    saveNotification($connection, $user_id, $type, $title, $msg_plain);
                    sendEmail($user['email'], $title, $msg_html);
                    sendSMS($user['phone'], $msg_plain);
                } else {
                    if (ob_get_length()) ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Failed to update allocation.', 'message' => mysqli_error($connection)]);
                    exit;
                }
            } else {
                // Already in this office, do nothing
            }
        } else {
            // Not allocated: insert new allocation
            if ($current_count >= $max_occupants) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Cannot allocate: exceeds maximum occupants.', 'message' => 'Office full during new allocation']);
                exit;
            }
            $res = mysqli_query($connection, "INSERT INTO allocation (user_id, office_id, status) VALUES ($user_id, $office_id, 'approved')");
            if ($res) {
                $allocated++;
                // Notify user about allocation
                $type = 'success';
                $title = 'Office Allocation';
                $msg_html = '<p>Dear ' . htmlspecialchars($user['names']) . ',</p>';
                $msg_html .= '<p>You have been allocated to office <b>' . htmlspecialchars($office_details['building_name']) . ' (' . htmlspecialchars($office_details['building_code']) . '), Office room number ' . htmlspecialchars($office_details['room_code']) . ', campus ' . htmlspecialchars($office_details['campus_name']) . '</b>.</p>';
                $msg_html .= '<p>If you have any questions, please contact the office management team.</p>';
                $msg_html .= '<br><p>Best regards,<br>Office Management System</p>';
                $msg_plain = 'Dear ' . $user['names'] . ',You have been allocated to office ' . $office_details['building_name'] . ' (' . $office_details['building_code'] . '), Office room number ' . $office_details['room_code'] . ', campus ' . $office_details['campus_name'] . '.';
                saveNotification($connection, $user_id, $type, $title, $msg_plain);
                sendEmail($user['email'], $title, $msg_html);
                sendSMS($user['phone'], $msg_plain);
            } else {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Failed to insert allocation.', 'message' => mysqli_error($connection)]);
                exit;
            }
        }
    }
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'allocated' => $allocated, 'message' => 'Allocation/reallocation successful']);
    exit;
}
// AJAX for unallocated users table
if (isset($_GET['ajax_unallocated'])) {
    $office_id = intval($_GET['office_id']);
    $search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
    $role = isset($_GET['role']) ? mysqli_real_escape_string($connection, $_GET['role']) : '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    // Only users not allocated to any office
    $where = "id NOT IN (SELECT user_id FROM allocation)";
    if ($search !== '') {
        $where .= " AND (names LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
    }
    if ($role !== '') {
        $where .= " AND role = '$role'";
    }
    $total_users = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM users WHERE $where"));
    $total_users = $total_users['cnt'];
    $total_pages = ceil($total_users / $per_page);
    $users = mysqli_query($connection, "SELECT id, names, email, phone, role FROM users WHERE $where ORDER BY names LIMIT $offset, $per_page");
    ob_start();
    $i = $offset + 1;
    while ($u = mysqli_fetch_assoc($users)) {
        echo '<tr>';
        echo '<td><input type="checkbox" name="user_ids[]" value="' . $u['id'] . '"></td>';
        echo '<td>' . $i++ . '</td>';
        echo '<td>' . htmlspecialchars($u['names']) . '</td>';
        echo '<td>' . htmlspecialchars($u['email']) . '</td>';
        echo '<td>' . htmlspecialchars($u['phone']) . '</td>';
        echo '<td>' . htmlspecialchars($u['role']) . '</td>';
        echo '</tr>';
    }
    $tbody = ob_get_clean();
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'tbody' => $tbody,
        'total_pages' => $total_pages
    ]);
    exit;
}
// Handle AJAX approve/reject/remove for occupants
if (isset($_POST['ajax_action']) && isset($_POST['alloc_id']) && isset($_POST['office_id'])) {
    $alloc_id = intval($_POST['alloc_id']);
    $office_id = intval($_POST['office_id']);
    $action = $_POST['ajax_action'];
    $result = false;
    if ($action === 'remove') {
        // Fetch allocation and office details BEFORE deleting
        $alloc = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM allocation WHERE id=$alloc_id"));
        $user_id = $alloc ? $alloc['user_id'] : null;
        $user = $user_id ? mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM users WHERE id=$user_id")) : null;
        $office_details = $alloc ? mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM offices WHERE id=" . intval($alloc['office_id']))) : null;
        $result = mysqli_query($connection, "DELETE FROM allocation WHERE id=$alloc_id");
        if ($result && $user) {
            $msg = 'Allocation removed.';
            $type = 'warning';
            $title = 'Office Allocation Removed';
            $msg_html = '<p>Dear ' . htmlspecialchars($user['names']) . ',</p>';
            $msg_html .= '<p>Your allocation to office <b>' . htmlspecialchars($office_details['building_name']) . ' (' . htmlspecialchars($office_details['building_code']) . '), Office room number ' . htmlspecialchars($office_details['room_code']) . ', campus ' . htmlspecialchars($office_details['campus_name']) . '</b> has been removed.</p>';
            $msg_html .= '<p>If you have any questions, please contact the office management team.</p>';
            $msg_html .= '<br><p>Best regards,<br>Office Management System</p>';
            $msg_plain = 'Dear ' . $user['names'] . ',Your allocation to office ' . $office_details['building_name'] . ' (' . $office_details['building_code'] . '), Office room number ' . $office_details['room_code'] . ', campus ' . $office_details['campus_name'] . ' has been removed.';
            saveNotification($connection, $user_id, $type, $title, $msg_plain);
            sendEmail($user['email'], $title, $msg_html);
            sendSMS($user['phone'], $msg_plain);
        } else if (!$result) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to remove allocation.', 'message' => mysqli_error($connection)]);
            exit;
        }
    } elseif ($action === 'approve' || $action === 'reject') {
        // Fetch allocation and office details BEFORE updating
        $alloc = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM allocation WHERE id=$alloc_id"));
        $user_id = $alloc ? $alloc['user_id'] : null;
        $user = $user_id ? mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM users WHERE id=$user_id")) : null;
        $office_details = $alloc ? mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM offices WHERE id=" . intval($alloc['office_id']))) : null;
        $new_status = $action === 'approve' ? 'approved' : 'rejected';
        $result = mysqli_query($connection, "UPDATE allocation SET status='$new_status' WHERE id=$alloc_id");
        if ($result && $user) {
            $msg = 'Status updated.';
            $type = $action === 'approve' ? 'success' : 'error';
            $title = $action === 'approve' ? 'Office Allocation Approved' : 'Office Allocation Rejected';
            $msg_html = '<p>Dear ' . htmlspecialchars($user['names']) . ',</p>';
            $msg_html .= '<p>Your allocation to office <b>' . htmlspecialchars($office_details['building_name']) . ' (' . htmlspecialchars($office_details['building_code']) . '), Office room number ' . htmlspecialchars($office_details['room_code']) . ', campus ' . htmlspecialchars($office_details['campus_name']) . '</b> has been ' . ($action === 'approve' ? 'approved' : 'rejected') . '.</p>';
            $msg_html .= '<p>If you have any questions, please contact the office management team.</p>';
            $msg_html .= '<br><p>Best regards,<br>Office Management System</p>';
            $msg_plain = 'Dear ' . $user['names'] . ',Your allocation to office ' . $office_details['building_name'] . ' (' . $office_details['building_code'] . '), Office room number ' . $office_details['room_code'] . ', campus ' . $office_details['campus_name'] . ' has been ' . ($action === 'approve' ? 'approved' : 'rejected') . '.';
            saveNotification($connection, $user_id, $type, $title, $msg_plain);
            sendEmail($user['email'], $title, $msg_html);
            sendSMS($user['phone'], $msg_plain);
        } else if (!$result) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to update status.', 'message' => mysqli_error($connection)]);
            exit;
        }
    }
    // Return updated occupants table HTML and current count
    ob_start();
    $current_allocs = mysqli_query($connection, "SELECT allocation.*, users.names, users.email, allocation.status as alloc_status FROM allocation JOIN users ON allocation.user_id = users.id WHERE allocation.office_id = $office_id");
    $current_occupants = [];
    while ($a = mysqli_fetch_assoc($current_allocs)) {
        $current_occupants[] = $a;
    }
    $current_count = count($current_occupants);
    $i=1;
    foreach ($current_occupants as $occ) {
        echo '<tr>';
        echo '<td>' . $i++ . '</td>';
        echo '<td>' . htmlspecialchars($occ['names']) . '</td>';
        echo '<td>' . htmlspecialchars($occ['email']) . '</td>';
        echo '<td>';
        $status = $occ['alloc_status'] ?? 'pending';
        if ($status == 'approved') echo '<span class="badge bg-success">Approved</span>';
        elseif ($status == 'rejected') echo '<span class="badge bg-danger">Rejected</span>';
        else echo '<span class="badge bg-warning text-dark">Pending</span>';
        echo '</td>';
        echo '<td>';
        if ($status == 'approved') {
            echo '<button class="btn btn-secondary btn-sm occ-action" data-action="remove" data-id="' . $occ['id'] . '" onclick="return confirm(\'Remove this user from the office?\')"><i class="bi bi-x"></i> Remove</button>';
        } elseif ($status == 'pending') {
            echo '<button class="btn btn-success btn-sm occ-action" data-action="approve" data-id="' . $occ['id'] . '">Approve</button> ';
            echo '<button class="btn btn-secondary btn-sm occ-action" data-action="remove" data-id="' . $occ['id'] . '" onclick="return confirm(\'Remove this user from the office?\')"><i class="bi bi-x"></i> Remove</button>';
        }
        echo '</td>';
        echo '</tr>';
    }
    $tbody = ob_get_clean();
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'tbody' => $tbody, 'current_count' => $current_count, 'message' => $msg]);
    exit;
}
// Fetch office details
$office = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM offices WHERE id=$office_id"));
$max_occupants = $office ? intval($office['max_occupants']) : 0;
// Fetch current allocations for this office
$current_allocs = mysqli_query($connection, "SELECT allocation.*, users.names, users.email, allocation.status as alloc_status FROM allocation JOIN users ON allocation.user_id = users.id WHERE allocation.office_id = $office_id");
$current_occupants = [];
while ($a = mysqli_fetch_assoc($current_allocs)) {
    $current_occupants[] = $a;
}
$current_count = count($current_occupants);
// Fetch all roles for filter
$roles = [];
$role_q = mysqli_query($connection, "SELECT DISTINCT role FROM users");
while ($r = mysqli_fetch_assoc($role_q)) {
    $roles[] = $r['role'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Allocate User to Office - UR-HUYE</title>
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
</head>
<body>
<?php
include ("./includes/header.php");  
include ("./includes/menu.php");
?>
<main id="main" class="main">
  <div class="pagetitle">
    <h1><i class="bi bi-person-plus"></i> Allocate User(s) to Office</h1>
  </div>
  <section class="section dashboard">
    <div class="row">
      <div class="col-lg-6">
        <div class="card shadow mb-4 p-3 border-primary" >
          <div class="card-body" style="">
            <div class="d-flex align-items-center mb-2">
              <div class="me-3 display-5 text-primary"><i class="bi bi-building"></i></div>
              <div>
                <div class="fw-bold fs-5 mb-1"> <?= htmlspecialchars($office['building_name']) ?> <span class="badge bg-secondary ms-2"> <?= htmlspecialchars($office['building_code']) ?> </span></div>
                <div class="text-muted small"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($office['campus_name']) ?></div>
              </div>
            </div>
            <div class="mb-2">
              <span class="fw-bold"><i class="bi bi-door-closed"></i> Office Room Number:</span> <?= htmlspecialchars($office['room_code']) ?>
            </div>
            <div class="mb-2">
              <span class="fw-bold"><i class="bi bi-people"></i> Max Occupants:</span> <?= htmlspecialchars($office['max_occupants']) ?>
            </div>
            <div class="mb-2">
              <span class="fw-bold"><i class="bi bi-info-circle"></i> Current Occupants:</span> <span id="current_count" class="fw-bold"><?= $current_count ?></span> / <?= $max_occupants ?>
              <?php if ($current_count > $max_occupants): ?>
                <span class="badge bg-danger ms-2">Over-Occupied</span>
              <?php elseif ($current_count == $max_occupants && $max_occupants > 0): ?>
                <span class="badge bg-success ms-2">Full</span>
              <?php elseif ($current_count > 0): ?>
                <span class="badge bg-warning text-dark ms-2">Partially Occupied</span>
              <?php else: ?>
                <span class="badge bg-secondary ms-2">Available</span>
              <?php endif; ?>
            </div>
            <!-- Current Occupants Table -->
            
          </div>
          <div class="mt-4">
              <h6 class="mb-2">Current Occupants & Allocation Status</h6>
              <div id="occupants_table">
                <div class="table-responsive">
                  <table class="table table-bordered table-sm">
                    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                      <?php $i=1; foreach ($current_occupants as $occ): ?>
                      <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($occ['names']) ?></td>
                        <td><?= htmlspecialchars($occ['email']) ?></td>
                        <td>
                          <?php
                            $status = $occ['alloc_status'] ?? 'pending';
                            if ($status == 'approved') echo '<span class="badge bg-success">Approved</span>';
                            elseif ($status == 'rejected') echo '<span class="badge bg-danger">Rejected</span>';
                            else echo '<span class="badge bg-warning text-dark">Pending</span>';
                          ?>
                        </td>
                        <td>
                          <?php if ($status == 'approved'): ?>
                            <button class="btn btn-secondary btn-sm occ-action" data-action="remove" data-id="<?= $occ['id'] ?>" onclick="return confirm('Remove this user from the office?')"><i class="bi bi-x"></i> Remove</button>
                          <?php elseif ($status == 'pending'): ?>
                            <button class="btn btn-success btn-sm occ-action" data-action="approve" data-id="<?= $occ['id'] ?>">Approve</button>
                            <button class="btn btn-secondary btn-sm occ-action" data-action="remove" data-id="<?= $occ['id'] ?>" onclick="return confirm('Remove this user from the office?')"><i class="bi bi-x"></i> Remove</button>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Users Not Yet Allocated to This Office</h5>
            <form id="unallocatedForm">
              <div class="row mb-2">
                <div class="col-md-6">
                  <input type="text" id="unalloc_search" class="form-control" placeholder="Search name, email, phone">
                </div>
                <div class="col-md-6">
                  <select id="unalloc_role" class="form-control">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $role): ?>
                      <option value="<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <br>
              <div class="table-responsive">
                <table class="table table-bordered table-sm">
                  <thead><tr><th><input type="checkbox" id="checkAllUnalloc"></th><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th></tr></thead>
                  <tbody id="unalloc_tbody">
                    <!-- AJAX loaded -->
                  </tbody>
                </table>
              </div>
              <nav>
                <ul class="pagination" id="unalloc_pagination"></ul>
              </nav>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
            <div id="unalloc_msg"></div>
          </div>
        </div>
      </div>
    </div>
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
<style>
.spinner-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(255,255,255,0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
}
.spinner {
  border: 8px solid #f3f3f3;
  border-top: 8px solid #3498db;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  animation: spin 1s linear infinite;
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
<script>
function showAllocateOfficeLoading() {
  document.getElementById('allocateOfficeLoading').style.display = 'flex';
}
function hideAllocateOfficeLoading() {
  document.getElementById('allocateOfficeLoading').style.display = 'none';
}
// Patch all AJAX actions to use spinner
function loadUnallocated(page=1) {
  showAllocateOfficeLoading();
  const search = document.getElementById('unalloc_search').value;
  const role = document.getElementById('unalloc_role').value;
  fetch(`allocate_office.php?ajax_unallocated=1&office_id=<?= $office_id ?>&search=${encodeURIComponent(search)}&role=${encodeURIComponent(role)}&page=${page}`)
    .then(res => res.json())
    .then(data => {
      document.getElementById('unalloc_tbody').innerHTML = data.tbody;
      let pag = '';
      for (let p = 1; p <= data.total_pages; p++) {
        pag += `<li class="page-item${p==page?' active':''}"><a class="page-link" href="#" onclick="loadUnallocated(${p});return false;">${p}</a></li>`;
      }
      document.getElementById('unalloc_pagination').innerHTML = pag;
      hideAllocateOfficeLoading();
    })
    .catch(() => { hideAllocateOfficeLoading(); });
}
document.getElementById('unalloc_search').addEventListener('input', function() { loadUnallocated(1); });
document.getElementById('unalloc_role').addEventListener('change', function() { loadUnallocated(1); });
document.getElementById('checkAllUnalloc')?.addEventListener('change', function() {
  const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
  for (const cb of checkboxes) cb.checked = this.checked;
});
document.getElementById('unallocatedForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  formData.append('ajax_allocate', '1');
  formData.append('office_id', '<?= $office_id ?>');
  showAllocateOfficeLoading();
  fetch('allocate_office.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    hideAllocateOfficeLoading();
    if (data.success) {
      document.getElementById('unalloc_msg').innerHTML = '<div class="alert alert-success">Users allocated successfully!</div>';
      setTimeout(() => window.location.reload(), 1000);
    } else {
      document.getElementById('unalloc_msg').innerHTML = '<div class="alert alert-danger">' + (data.error || 'Error allocating users.') + '</div>';
    }
  })
  .catch(() => { hideAllocateOfficeLoading(); });
});
function bindOccActions() {
  document.querySelectorAll('.occ-action').forEach(btn => {
    btn.onclick = function() {
      const alloc_id = this.getAttribute('data-id');
      const action = this.getAttribute('data-action');
      if (action === 'remove') {
        removeAllocId = alloc_id;
        removeModal.show();
        return;
      }
      const formData = new FormData();
      formData.append('ajax_action', action);
      formData.append('alloc_id', alloc_id);
      formData.append('office_id', '<?= $office_id ?>');
      showAllocateOfficeLoading();
      fetch('allocate_office.php', {
        method: 'POST',
        body: formData
      })
      .then(async res => {
        hideAllocateOfficeLoading();
        if (!res.ok) throw new Error('Network response was not ok');
        let data;
        try { data = await res.json(); } catch (e) { throw new Error('Invalid JSON response'); }
        if (data.success) {
          document.querySelector('#occupants_table tbody').innerHTML = data.tbody;
          document.getElementById('current_count').textContent = data.current_count;
          bindOccActions();
          loadUnallocated(); // update unallocated table as well
        } else {
          alert('Action failed: ' + (data.error || data.message || 'Unknown error'));
        }
      })
      .catch(error => {
        hideAllocateOfficeLoading();
        alert('AJAX error: ' + error.message);
        console.error('AJAX error:', error);
      });
    };
  });
}
window.addEventListener('DOMContentLoaded', function() {
  removeModal = new bootstrap.Modal(document.getElementById('removeConfirmModal'));
  bindOccActions();
  loadUnallocated(1);
  document.getElementById('confirmRemoveBtn').onclick = function() {
    if (removeAllocId) {
      const formData = new FormData();
      formData.append('ajax_action', 'remove');
      formData.append('alloc_id', removeAllocId);
      formData.append('office_id', '<?= $office_id ?>');
      showAllocateOfficeLoading();
      fetch('allocate_office.php', {
        method: 'POST',
        body: formData
      })
      .then(async res => {
        hideAllocateOfficeLoading();
        if (!res.ok) throw new Error('Network response was not ok');
        let data;
        try { data = await res.json(); } catch (e) { throw new Error('Invalid JSON response'); }
        if (data.success) {
          document.querySelector('#occupants_table tbody').innerHTML = data.tbody;
          document.getElementById('current_count').textContent = data.current_count;
          bindOccActions();
          loadUnallocated();
        } else {
          alert('Action failed: ' + (data.error || data.message || 'Unknown error'));
        }
      })
      .catch(error => {
        hideAllocateOfficeLoading();
        alert('AJAX error: ' + error.message);
        console.error('AJAX error:', error);
      });
      removeAllocId = null;
      removeModal.hide();
    }
  };
});
</script>
<div id="allocateOfficeLoading" class="spinner-overlay" style="display:none;">
  <div class="spinner"></div>
</div>
<!-- Add Remove Confirmation Modal -->
<div class="modal fade" id="removeConfirmModal" tabindex="-1" aria-labelledby="removeConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="removeConfirmModalLabel"><i class="bi bi-exclamation-triangle"></i> Confirm Removal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to remove this user from the office? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmRemoveBtn">Yes, Remove</button>
      </div>
    </div>
  </div>
</div>
</body>
</html> 
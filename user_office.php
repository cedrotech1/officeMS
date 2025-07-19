<?php
session_start();
include('../connection.php');
$userid = $_SESSION['id'] ?? null;
if (!$userid) {
  header('Location: login.php');
  exit;
}
$user = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM users WHERE id=" . intval($userid)));
$alloc = mysqli_query($connection, "SELECT offices.*, allocation.status, allocation.office_id FROM allocation JOIN offices ON allocation.office_id = offices.id WHERE allocation.user_id = $userid LIMIT 1");
$office = mysqli_fetch_assoc($alloc);
$office_mates = [];
if ($office && $office['status'] === 'approved') {
  $mates_q = mysqli_query($connection, "SELECT users.* FROM allocation JOIN users ON allocation.user_id = users.id WHERE allocation.office_id = " . intval($office['office_id']) . " AND allocation.status = 'approved' AND users.id != $userid");
  while ($mate = mysqli_fetch_assoc($mates_q)) {
    $office_mates[] = $mate;
  }
}
// Handle AJAX request for user office allocation
if (isset($_POST['ajax_user_request_office']) && isset($_POST['office_id'])) {
  $office_id = intval($_POST['office_id']);
  // Check if user already has a pending or approved allocation
  $existing = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM allocation WHERE user_id = $userid AND (status = 'pending' OR status = 'approved')"));
  if ($existing) {
    if ($existing['status'] === 'pending') {
      // Update the existing pending allocation to the new office
      $res = mysqli_query($connection, "UPDATE allocation SET office_id = $office_id WHERE id = " . intval($existing['id']));
      if ($res) {
        echo json_encode(['success' => true, 'message' => 'Room change request submitted! Waiting for approval.']);
      } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update room request.']);
      }
      exit;
    } else {
      echo json_encode(['success' => false, 'error' => 'You already have a pending or approved office allocation.']);
      exit;
    }
  }
  $res = mysqli_query($connection, "INSERT INTO allocation (user_id, office_id, status) VALUES ($userid, $office_id, 'pending')");
  if ($res) {
    echo json_encode(['success' => true, 'message' => 'Request submitted! Waiting for approval.']);
  } else {
    echo json_encode(['success' => false, 'error' => 'Failed to submit request.']);
  }
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>My Office - UR-HUYE</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<?php include("./includes/header.php"); include("./includes/menu.php"); ?>
<main id="main" class="main">
  
  <section class="section dashboard">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card shadow mb-4 p-2">
          <div class="card-body">
          <div class="pagetitle">
    <h1><i class="bi bi-person"></i> My Office</h1>
    <hr>
  </div>
            <div class="d-flex align-items-center mb-3">
              <img src="<?= htmlspecialchars($user['image']) ?>" alt="img" class="rounded-circle me-3" style="width:60px;height:60px;object-fit:cover;border:1px solid #ccc;">
              <div>
                <h4 class="mb-0"> <?= htmlspecialchars($user['names']) ?> </h4>
                <div class="text-muted small"> <?= htmlspecialchars($user['email']) ?> | <?= htmlspecialchars($user['phone']) ?> </div>
              </div>
            </div>
            <?php if ($office): ?>
                
              <div class="alert alert-info mb-2"><b>Your Assigned Office:</b></div>
              <div class="card mt-3 mb-3 border-primary" style="max-width: 500px;background-color:whitesmoke">
                <div class="card-body p-3">
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
                    <span class="fw-bold"><i class="bi bi-info-circle"></i> Status:</span> <?php
                      $status = $office['status'] ?? 'pending';
                      if ($status == 'approved') echo '<span class="badge bg-success ms-2">Approved</span>';
                      elseif ($status == 'rejected') echo '<span class="badge bg-danger ms-2">Rejected</span>';
                      else echo '<span class="badge bg-warning text-dark ms-2">Pending</span>';
                    ?>
                  </div>
                </div>
              </div>
              <?php if ($office['status'] === 'pending'): ?>
                <div class="mb-3">
                  <button class="btn btn-warning" id="changeRoomBtn"><i class="bi bi-arrow-repeat"></i> Change Room</button>
                </div>
              <?php endif; ?>
              <?php if ($office['status'] === 'approved'): ?>
                <div class="card mb-3">
                  <div class="card-header bg-light"><b>Office Mates</b></div>
                  <ul class="list-group list-group-flush">
                    <?php if (count($office_mates) === 0): ?>
                      <li class="list-group-item text-muted">No other office mates.</li>
                    <?php else: foreach ($office_mates as $mate): ?>
                      <li class="list-group-item d-flex align-items-center">
                        <img src="<?= htmlspecialchars($mate['image']) ?>" alt="img" class="rounded-circle me-2" style="width:40px;height:40px;object-fit:cover;border:1px solid #ccc;">
                        <div>
                          <b><?= htmlspecialchars($mate['names']) ?></b><br>
                          <span class="text-muted small"> <?= htmlspecialchars($mate['email']) ?> | <?= htmlspecialchars($mate['phone']) ?> </span>
                        </div>
                      </li>
                    <?php endforeach; endif; ?>
                  </ul>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <div class="alert alert-warning mb-3">You have not been assigned an office yet.</div>
              <button class="btn btn-primary" id="requestOfficeBtn"><i class="bi bi-plus-circle"></i> Request Office</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>
<!-- Assign Office Modal (reuse from user_lists.php) -->
<div class="modal fade" id="assignOfficeModal" tabindex="-1" aria-labelledby="assignOfficeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assignOfficeModalLabel">Request Office</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- User Card -->
        <div class="card mb-3 shadow-sm" id="modalUserCombinedCard" style="max-width: 600px;">
          <div class="row g-0 align-items-center">
            <div class="col-auto p-3">
              <img id="modalUserImage" src="<?= htmlspecialchars($user['image']) ?>" alt="User Image" class="rounded-circle" style="width:70px;height:70px;object-fit:cover;border:2px solid #dee2e6;">
            </div>
            <div class="col">
              <div class="card-body p-2">
                <h5 class="card-title mb-1" id="modalUserDetailsName"><?= htmlspecialchars($user['names']) ?></h5>
                <div class="mb-1"><span id="modalUserDetailsEmail" class="text-muted small"><?= htmlspecialchars($user['email']) ?></span></div>
                <div class="mb-2"><span id="modalUserDetailsPhone" class="text-muted small"><?= htmlspecialchars($user['phone']) ?></span></div>
              </div>
            </div>
          </div>
        </div>
        <!-- Office Table and Form -->
        <form id="assignOfficeForm">
          <input type="hidden" id="modalUserId" name="user_id" value="<?= $userid ?>">
          <input type="hidden" id="modalSelectedOfficeId" name="office_id">
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
          <button type="submit" class="btn btn-success" id="modalAssignBtn" disabled>Request</button>
        </form>
      </div>
    </div>
  </div>
</div>
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
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
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
$('#requestOfficeBtn').on('click', function() {
  $('#modalSelectedOfficeId').val('');
  $('#modalAssignBtn').prop('disabled', true);
  loadModalOffices(1);
  $('#assignOfficeModal').modal('show');
});
$('#assignOfficeForm').on('submit', function(e) {
  e.preventDefault();
  var officeId = $('#modalSelectedOfficeId').val();
  if (!officeId) {
    $('#assignOfficeMsg').html('<div class="alert alert-danger">Please select an office.</div>');
    return;
  }
  $('#assignOfficeMsg').html('<div class="text-info">Requesting...</div>');
  $.post('user_office.php', {
    ajax_user_request_office: 1,
    office_id: officeId
  }, function(resp) {
    var data = {};
    try { data = typeof resp === 'object' ? resp : JSON.parse(resp); } catch(e) { data = {}; }
    if (data.success) {
      $('#assignOfficeMsg').html('<div class="alert alert-success">Request submitted! Waiting for approval.</div>');
      setTimeout(function() { location.reload(); }, 1000);
    } else {
      $('#assignOfficeMsg').html('<div class="alert alert-danger">'+(data.error || 'Error requesting office.')+'</div>');
    }
  });
});
$('#changeRoomBtn').on('click', function() {
  $('#modalSelectedOfficeId').val('');
  $('#modalAssignBtn').prop('disabled', true);
  loadModalOffices(1);
  $('#assignOfficeModal').modal('show');
});
</script>
</body>
</html> 
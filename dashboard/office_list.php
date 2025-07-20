<?php
// Always define these first to avoid undefined variable warnings
$user_search = isset($_GET['user_search']) ? trim($_GET['user_search']) : '';
$user_office = null;
$user_info = null;
include('../connection.php');
session_start();
$mycampus = $_SESSION['campus'];
// Handle office edit POST (must be before any output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_office_id'])) {
    $edit_id = intval($_POST['edit_office_id']);
    $campus = mysqli_real_escape_string($connection, $_POST['campus_name']);
    $bcode = mysqli_real_escape_string($connection, $_POST['building_code']);
    $bname = mysqli_real_escape_string($connection, $_POST['building_name']);
    $rcode = mysqli_real_escape_string($connection, $_POST['room_code']);
    $maxocc = intval($_POST['max_occupants']);
    mysqli_query($connection, "UPDATE offices SET campus_name='$campus', building_code='$bcode', building_name='$bname', room_code='$rcode', max_occupants=$maxocc WHERE id=$edit_id");
    header('Location: office_list.php?edit_success=1');
    exit;
}
// User search for allocated office
if ($user_search !== '') {
    $user_q = mysqli_query($connection, "SELECT * FROM users WHERE names LIKE '%$user_search%' OR email LIKE '%$user_search%' OR phone LIKE '%$user_search%' LIMIT 1");
    if ($user = mysqli_fetch_assoc($user_q)) {
        $user_info = $user;
        $alloc = mysqli_query($connection, "SELECT offices.* FROM allocation JOIN offices ON allocation.office_id = offices.id WHERE allocation.user_id = " . intval($user['id']) . " LIMIT 1");
        if ($office = mysqli_fetch_assoc($alloc)) {
            $user_office = $office;
        }
    }
}
// Pagination and search/filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
//$campus = isset($_GET['campus']) ? mysqli_real_escape_string($connection, $_GET['campus']) : '';
$campus = $mycampus;
$occupancy = isset($_GET['occupancy']) ? $_GET['occupancy'] : '';
$occupants_num = isset($_GET['occupants_num']) ? trim($_GET['occupants_num']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 6;
$offset = ($page - 1) * $per_page;
$where = '1=1';
$where .= " AND campus_name = '".mysqli_real_escape_string($connection, $campus)."'";
if ($search !== '') {
    $where .= " AND (campus_name LIKE '%$search%' OR building_name LIKE '%$search%' OR building_code LIKE '%$search%' OR room_code LIKE '%$search%')";
}
// For occupancy status filter
$office_ids = [];
if ($occupancy !== '' || $occupants_num !== '') {
    $all_offices = mysqli_query($connection, "SELECT id, max_occupants FROM offices WHERE $where");
    while ($o = mysqli_fetch_assoc($all_offices)) {
        $oid = $o['id'];
        $max_occ = intval($o['max_occupants']);
        $occ_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM allocation WHERE office_id = $oid"));
        $occ_count = $occ_count ? intval($occ_count['cnt']) : 0;
        $match = true;
        if ($occupancy === 'full' && $occ_count == $max_occ && $max_occ > 0) $match = true;
        elseif ($occupancy === 'partial' && $occ_count > 0 && $occ_count < $max_occ) $match = true;
        elseif ($occupancy === 'none' && $occ_count == 0) $match = true;
        elseif ($occupancy === 'over' && $occ_count > $max_occ) $match = true;
        elseif ($occupancy === '') $match = true;
        else $match = false;
        if ($match && $occupants_num !== '' && $occ_count != intval($occupants_num)) $match = false;
        if ($match) $office_ids[] = $oid;
    }
    if (count($office_ids) > 0) {
        $where .= " AND id IN (" . implode(",", $office_ids) . ")";
    } else {
        $where .= " AND 0";
    }
}
$total_offices = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM offices WHERE $where"));
$total_offices = $total_offices['cnt'];
$total_pages = ceil($total_offices / $per_page);
$offices = mysqli_query($connection, "SELECT * FROM offices WHERE $where ORDER BY id DESC LIMIT $offset, $per_page");
// For campus filter
$campuses = [];
$campus_q = mysqli_query($connection, "SELECT DISTINCT campus_name FROM offices");
while ($c = mysqli_fetch_assoc($campus_q)) {
    $campuses[] = $c['campus_name'];
}

if (isset($_GET['ajax_get_occupants']) && isset($_GET['office_id'])) {
    $office_id = intval($_GET['office_id']);
    $occupants = [];
    $q = mysqli_query($connection, "SELECT users.names, users.email, allocation.status FROM allocation JOIN users ON allocation.user_id = users.id WHERE allocation.office_id = $office_id");
    while ($row = mysqli_fetch_assoc($q)) {
        // If status column does not exist, set to 'N/A'
        if (!isset($row['status'])) $row['status'] = 'N/A';
        $occupants[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'occupants' => $occupants]);
    exit;
}

if (isset($_GET['ajax_office_list'])) {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = 6;
    $offset = ($page - 1) * $per_page;
    $where = '1=1';
    //$campus = isset($_GET['campus']) ? mysqli_real_escape_string($connection, $_GET['campus']) : '';
    $campus = $mycampus;
    $occupants_num = isset($_GET['occupants_num']) ? trim($_GET['occupants_num']) : '';
    $user_search = isset($_GET['user_search']) ? trim($_GET['user_search']) : '';
    $where .= " AND campus_name = '".mysqli_real_escape_string($connection, $campus)."'";
    if ($search !== '') {
        $where .= " AND (campus_name LIKE '%$search%' OR building_name LIKE '%$search%' OR building_code LIKE '%$search%' OR room_code LIKE '%$search%')";
    }
    $office_rows = [];
    $q = mysqli_query($connection, "SELECT * FROM offices WHERE $where ORDER BY id DESC");
    while ($row = mysqli_fetch_assoc($q)) {
        // Count occupants
        $occ_q = mysqli_query($connection, "SELECT users.names, users.email FROM allocation JOIN users ON allocation.user_id = users.id WHERE allocation.office_id = " . intval($row['id']));
        $occupants = [];
        while ($occ = mysqli_fetch_assoc($occ_q)) {
            $occupants[] = $occ;
        }
        $row['occupants'] = count($occupants);
        $row['occupant_names'] = array_map(function($o){ return strtolower($o['names'] . ' ' . $o['email']); }, $occupants);
        // Status logic: Over-Occupied, Full, Partially Occupied, Available
        if ($row['occupants'] > $row['max_occupants']) {
            $row['status'] = 'Over-Occupied';
        } elseif ($row['occupants'] == $row['max_occupants'] && $row['max_occupants'] > 0) {
            $row['status'] = 'Full';
        } elseif ($row['occupants'] > 0) {
            $row['status'] = 'Partially Occupied';
        } else {
            $row['status'] = 'Available';
        }
        $row['occupants_data'] = $occupants;
        $office_rows[] = $row;
    }
    // Map status filter value to status string
    $status_map = [
        'full' => 'Full',
        'partial' => 'Partially Occupied',
        'none' => 'Available',
        'over' => 'Over-Occupied'
    ];
    if ($status !== '' && isset($status_map[$status])) {
        $status_str = $status_map[$status];
        $office_rows = array_filter($office_rows, function($row) use ($status_str) {
            return $row['status'] === $status_str;
        });
        $office_rows = array_values($office_rows);
    }
    // Filter by occupants_num
    if ($occupants_num !== '') {
        $office_rows = array_filter($office_rows, function($row) use ($occupants_num) {
            return $row['occupants'] == intval($occupants_num);
        });
        $office_rows = array_values($office_rows);
    }
    // Filter by occupant name/email/phone (userCardSearch)
    if ($user_search !== '') {
        $user_search_lc = strtolower($user_search);
        $office_rows = array_filter($office_rows, function($row) use ($user_search_lc) {
            foreach ($row['occupant_names'] as $occ) {
                if (strpos($occ, $user_search_lc) !== false) return true;
            }
            return false;
        });
        $office_rows = array_values($office_rows);
    }
    $total_offices = count($office_rows);
    $total_pages = ceil($total_offices / $per_page);
    $office_rows = array_slice($office_rows, ($page-1)*$per_page, $per_page);
    header('Content-Type: application/json');
    echo json_encode(['offices' => $office_rows, 'total_pages' => $total_pages, 'current_page' => $page]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>UR-HUYE</title>
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
  <!-- jQuery CDN (must be before any script using $) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<?php
include ("./includes/header.php");
include ("./includes/menu.php");
?>
<main id="main" class="main">
  <div class="pagetitle">
    <h1><i class="bi bi-building"></i> Office List</h1>
  </div>
  <?php if (isset($_GET['edit_success'])): ?>
    <div class="alert alert-success text-center">Office updated successfully!</div>
  <?php endif; ?>
  <section class="section card p-3 dashboard">
    <form class="row mb-4" method="get" id="officeFilterForm">
      <div class="col-md-3">
        <input type="text" name="search" class="form-control" placeholder="Search campus, building, room..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <!-- Campus filter removed for campus restriction -->
      <div class="col-md-2">
        <select name="occupancy" class="form-control">
          <option value="">All Status</option>
          <option value="full" <?= $occupancy==='full'?'selected':'' ?>>Fully Occupied</option>
          <option value="partial" <?= $occupancy==='partial'?'selected':'' ?>>Partially Occupied</option>
          <option value="none" <?= $occupancy==='none'?'selected':'' ?>>Unoccupied</option>
          <option value="over" <?= $occupancy==='over'?'selected':'' ?>>Over-Occupied</option>
        </select>
      </div>
      <div class="col-md-2">
        <input type="number" name="occupants_num" class="form-control" placeholder="Occupants = ?" value="<?= htmlspecialchars($occupants_num) ?>">
      </div>
      <div class="col-md-2 d-flex gap-2">
        <a href="office_list.php" class="btn btn-secondary w-100">Reset</a>
      </div>
      <div class="col-md-6 mt-2">
        <input type="text" id="userCardSearch" class="form-control" placeholder="Filter offices by occupant name, email, or phone...">
      </div>
    </form>
    <!-- View Toggle -->
    <div class="d-flex justify-content-end mb-3">
      <div class="btn-group" role="group">
        <button class="btn btn-outline-primary active" id="viewTableBtn"><i class="bi bi-table"></i> Table View</button>
        <button class="btn btn-outline-primary" id="viewCardBtn"><i class="bi bi-grid-3x3-gap"></i> Card View</button>
      </div>
    </div>
    <!-- Table View -->
    <div id="officeTableView">
      <div class="table-responsive">
        <table class="table  table-hover align-middle" id="officeTable">
          <thead class="table-light">
            <tr>
              <th>Campus</th>
              <th>Building</th>
              <th>Room</th>
              <th>Max Occupants</th>
              <th>Current Occupants</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <nav>
        <ul class="pagination justify-content-center" id="officePaginationTable"></ul>
      </nav>
    </div>
    <!-- Card View -->
    <div id="officeCardView" class="row g-3" style="display:none;"></div>
    <nav id="cardPaginationNav">
      <ul class="pagination justify-content-center" id="officePaginationCard"></ul>
    </nav>
  </section>
</main>
<!-- Occupants Modal -->
<div class="modal fade" id="occupantsModal" tabindex="-1" aria-labelledby="occupantsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="occupantsModalLabel">Office Occupants</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="occupantsModalBody">
          <div class="text-center text-muted">Loading...</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
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
// --- AJAX Office Loader for Table & Card View ---
let tableCurrentPage = 1;
let cardCurrentPage = 1;
let lastView = 'table'; // or 'card'

function renderOfficeCards(offices) {
  let cards = '';
  offices.forEach(function(o) {
    let badge = o.status === 'Full' ? 'danger' : (o.status === 'Available' ? 'success' : 'warning');
    cards += `<div class=\"col-md-4 office-card\">
      <div class=\"card h-100 border-primary pt-3 shadow-sm\" style=\"background-color:whitesmoke \">
        <div class=\"card-body\">
          <div class=\"d-flex align-items-center mb-2\">
            <div class=\"me-3 display-5 text-primary\"><i class=\"bi bi-building\"></i></div>
            <div>
              <div class=\"fw-bold fs-5 mb-1\">${o.building_name} <span class=\"badge bg-secondary ms-2\">${o.building_code}</span></div>
              <div class=\"text-muted small\"><i class=\"bi bi-geo-alt\"></i> ${o.campus_name}</div>
            </div>
          </div>
          <div class=\"mb-2\"><span class=\"fw-bold\"><i class=\"bi bi-door-closed\"></i> Office Room Number:</span> ${o.room_code}</div>
          <div class=\"mb-2\"><span class=\"fw-bold\"><i class=\"bi bi-people\"></i> Occupants:</span> <span class=\"badge bg-info\">${o.occupants} / ${o.max_occupants}</span></div>
          <div class=\"mb-2\"><span class=\"fw-bold\"><i class=\"bi bi-info-circle\"></i> Status:</span> <span class=\"badge bg-${badge} ms-2\">${o.status}</span></div>
          <div class=\"mt-2\">
            <a href=\"allocate_office.php?office_id=${o.id}\" class=\"btn btn-primary btn-sm\">Allocate</a> 
            ${o.occupants > 0 ? `<button class=\"btn btn-info btn-sm view-occupants-btn\" data-office-id=\"${o.id}\">View Occupants</button>` : ''}
          </div>
        </div>
      </div>
    </div>`;
  });
  $('#officeCardView').html(cards);
}
function loadOffices(page=1, view=null) {
  // view: 'table' or 'card' or null (auto-detect)
  if (!view) view = lastView;
  if (view === 'table') tableCurrentPage = page;
  if (view === 'card') cardCurrentPage = page;
  const search = $('input[name=search]').val();
  const status = $('select[name=occupancy]').val();
  const occupantsNum = $('input[name=occupants_num]').val();
  const campus = $('select[name=campus]').val(); // This will be empty for users
  const userSearch = $('#userCardSearch').val();
  $.get('office_list.php', {
    ajax_office_list: 1,
    search: search,
    status: status,
    occupants_num: occupantsNum,
    campus: campus, // This will be empty for users
    user_search: userSearch,
    page: (view === 'table' ? tableCurrentPage : cardCurrentPage)
  }, function(resp) {
    let data = {};
    try { data = typeof resp === 'object' ? resp : JSON.parse(resp); } catch(e) { data = {}; }
    const offices = data.offices || [];
    // Table view
    let rows = '';
    offices.forEach(function(o) {
      let badge = o.status === 'Full' ? 'danger' : (o.status === 'Available' ? 'success' : 'warning');
      rows += `<tr>
        <td>${o.campus_name}</td>
        <td>${o.building_name} (${o.building_code})</td>
        <td>${o.room_code}</td>
        <td>${o.max_occupants}</td>
        <td>${o.occupants}</td>
        <td><span class=\"badge bg-${badge}\">${o.status}</span></td>
        <td>`;
      rows += `<a href=\"allocate_office.php?office_id=${o.id}\" class=\"btn btn-primary btn-sm\">Allocate</a> `;
      if (o.occupants > 0) {
        rows += `<button class=\"btn btn-info btn-sm view-occupants-btn\" data-office-id=\"${o.id}\">View Occupants</button>`;
      }
      rows += `</td></tr>`;
    });
    $('#officeTable tbody').html(rows);
    // Card view
    renderOfficeCards(offices);
    // Pagination (update both paginations)
    let pagTable = '';
    let pagCard = '';
    for (let p = 1; p <= (data.total_pages || 1); p++) {
      pagTable += `<li class=\"page-item${p==tableCurrentPage?' active':''}\"><a class=\"page-link\" href=\"#\" data-page=\"${p}\" data-view=\"table\">${p}</a></li>`;
      pagCard += `<li class=\"page-item${p==cardCurrentPage?' active':''}\"><a class=\"page-link\" href=\"#\" data-page=\"${p}\" data-view=\"card\">${p}</a></li>`;
    }
    $('#officePaginationTable').html(pagTable);
    $('#officePaginationCard').html(pagCard);
    // Show/hide paginations based on view
    if ($('#officeTableView').is(':visible')) {
      $('#officePaginationTable').parent().show();
      $('#cardPaginationNav').hide();
    } else {
      $('#officePaginationTable').parent().hide();
      $('#cardPaginationNav').show();
    }
    // If card view is active and there are no offices, still show the pagination nav (empty or with a message)
    if ($('#officeCardView').is(':visible') && (!offices || offices.length === 0)) {
      $('#officeCardView').html('<div class="col-12 text-center text-muted">No offices found.</div>');
      $('#cardPaginationNav').show();
    }
    console.log('Loaded offices:', offices);
  });
}
$('#viewTableBtn').on('click', function() {
  $(this).addClass('active');
  $('#viewCardBtn').removeClass('active');
  $('#officeTableView').show();
  $('#officeCardView').hide();
  $('#officePaginationTable').parent().show();
  $('#cardPaginationNav').hide();
  lastView = 'table';
  loadOffices(tableCurrentPage, 'table');
});
$('#viewCardBtn').on('click', function() {
  $(this).addClass('active');
  $('#viewTableBtn').removeClass('active');
  $('#officeTableView').hide();
  $('#officeCardView').show();
  $('#officePaginationTable').parent().hide();
  $('#cardPaginationNav').show();
  lastView = 'card';
  loadOffices(cardCurrentPage, 'card');
});
// Initial load
$(document).ready(function() {
  loadOffices();
  // Optionally, reload on filter form submit
  // Remove filter button and make all filters/searches trigger AJAX on change/input
  $('select[name=occupancy], input[name=occupants_num], input[name=search]').on('change input', function() {
    loadOffices(1);
  });
  $('#userCardSearch').on('input', function() {
    loadOffices(1);
  });
  // Remove filter button event handler and form submit
  $('#officeFilterForm').off('submit');
});
// View Occupants Modal logic
$(document).on('click', '.view-occupants-btn', function() {
  const officeId = $(this).data('office-id');
  $('#occupantsModal').modal('show');
  $('#occupantsModalBody').html('<div class="text-center text-muted">Loading...</div>');
  $.get('office_list.php', { ajax_get_occupants: 1, office_id: officeId }, function(resp) {
    let data = {};
    try { data = typeof resp === 'object' ? resp : JSON.parse(resp); } catch(e) { data = {}; }
    if (data.success && data.occupants && data.occupants.length > 0) {
      let table = `<table class=\"table\"><thead><tr><th>Name</th><th>Email</th><th>Status</th></tr></thead><tbody>`;
      data.occupants.forEach(function(o) {
        let status = o.status ? o.status.toLowerCase() : 'n/a';
        let badgeClass = 'secondary';
        let statusText = status.charAt(0).toUpperCase() + status.slice(1);
        if (status === 'approved') badgeClass = 'success';
        else if (status === 'pending') badgeClass = 'warning';
        else if (status === 'rejected') badgeClass = 'danger';
        else badgeClass = 'secondary';
        table += `<tr><td>${o.names}</td><td>${o.email}</td><td><span class=\"badge bg-${badgeClass}\">${statusText}</span></td></tr>`;
      });
      table += '</tbody></table>';
      $('#occupantsModalBody').html(table);
    } else {
      $('#occupantsModalBody').html('<div class=\"alert alert-info\">No occupants found for this office.</div>');
    }
  }).fail(function(xhr, status, error) {
    $('#occupantsModalBody').html('<div class=\"alert alert-danger\">Failed to load occupants.</div>');
  });
});
// Pagination click handler (event delegation)
$(document).on('click', '#officePaginationTable .page-link, #officePaginationCard .page-link', function(e) {
  e.preventDefault();
  const page = parseInt($(this).data('page'));
  const view = $(this).data('view');
  if (!isNaN(page) && (view === 'table' || view === 'card')) loadOffices(page, view);
});
// Filter change handlers
// Remove filter button and make all filters/searches trigger AJAX on change/input
$('select[name=occupancy], input[name=occupants_num], input[name=search]').on('change input', function() {
  loadOffices(1);
});
$('input[name=search]').on('input', function() {
  loadOffices(1);
});
</script>
<script>
document.getElementById('userCardSearch').addEventListener('input', function() {
  const val = this.value.trim().toLowerCase();
  document.querySelectorAll('.office-card').forEach(card => {
    const occ = card.getAttribute('data-occupants');
    if (!val || occ.includes(val)) {
      card.style.display = '';
    } else {
      card.style.display = 'none';
    }
  });
});
</script>
</body>
</html> 
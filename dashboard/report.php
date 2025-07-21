<?php
include('../connection.php');
session_start();
$mycampus = $_SESSION['campus'];

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
    <h1><i class="bi bi-bar-chart"></i> Reports</h1>
  </div>
  <section class="section dashboard">
    <div class="card p-3 shadow-sm">
      <ul class="nav nav-tabs mb-3" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="user-report-tab" data-bs-toggle="tab" data-bs-target="#user-report" type="button" role="tab" aria-controls="user-report" aria-selected="true">User Report</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="office-report-tab" data-bs-toggle="tab" data-bs-target="#office-report" type="button" role="tab" aria-controls="office-report" aria-selected="false">Office Report</button>
        </li>
      </ul>
      <div class="tab-content" id="reportTabsContent">
        <!-- User Report Tab -->
        <div class="tab-pane fade show active" id="user-report" role="tabpanel" aria-labelledby="user-report-tab">
          <div class="mb-2 text-end">
            <button class="btn btn-success" id="exportUserReport"><i class="bi bi-file-earmark-excel"></i> Export to Excel</button>
          </div>
          <div class="table-responsive">
            <table class="table table-striped align-middle" id="userReportTable">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Role</th>
                  <th>Office Room</th>
                  <th>Building</th>
                  <th>Building Code</th>
                  <th>Campus</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $i = 1;
                $q = mysqli_query($connection, "SELECT users.*, offices.room_code, offices.building_name, offices.building_code, offices.campus_name, allocation.status as alloc_status FROM users LEFT JOIN allocation ON allocation.user_id = users.id LEFT JOIN offices ON allocation.office_id = offices.id WHERE (offices.campus_name = '".mysqli_real_escape_string($connection, $mycampus)."' OR offices.campus_name IS NULL) AND users.role != 'admin' AND users.role != 'officer' ORDER BY users.names");
                while ($u = mysqli_fetch_assoc($q)) {
                  echo '<tr>';
                  echo '<td>' . $i++ . '</td>';
                  echo '<td>' . htmlspecialchars($u['names']) . '</td>';
                  echo '<td>' . htmlspecialchars($u['email']) . '</td>';
                  echo '<td>' . htmlspecialchars($u['phone']) . '</td>';
                  echo '<td>' . htmlspecialchars($u['role']) . '</td>';
                  echo '<td>' . htmlspecialchars($u['room_code'] ?? '-') . '</td>';
                  echo '<td>' . htmlspecialchars($u['building_name'] ?? '-') . '</td>';
                  echo '<td>' . htmlspecialchars($u['building_code'] ?? '-') . '</td>';
                  echo '<td>' . htmlspecialchars($u['campus_name'] ?? '-') . '</td>';
                  echo '<td>';
                  $status = $u['alloc_status'] ?? 'None';
                  if ($status == 'approved') echo '<span class="badge bg-success">Approved</span>';
                  elseif ($status == 'pending') echo '<span class="badge bg-warning text-dark">Pending</span>';
                  elseif ($status == 'rejected') echo '<span class="badge bg-danger">Rejected</span>';
                  else echo '<span class="badge bg-secondary">None</span>';
                  echo '</td>';
                  echo '</tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
        <!-- Office Report Tab -->
        <div class="tab-pane fade" id="office-report" role="tabpanel" aria-labelledby="office-report-tab">
          <div class="mb-2 text-end">
            <button class="btn btn-success" id="exportOfficeReport"><i class="bi bi-file-earmark-excel"></i> Export to Excel</button>
          </div>
          <div class="table-responsive">
            <table class="table table-striped align-middle" id="officeReportTable">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Room</th>
                  <th>Building</th>
                  <th>Building Code</th>
                  <th>Campus</th>
                  <th>Max Occupants</th>
                  <th>Current Occupants</th>
                  <th>Name</th>
                  <th>Email</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $i = 1;
                $q = mysqli_query($connection, "SELECT * FROM offices WHERE campus_name = '".mysqli_real_escape_string($connection, $mycampus)."' ORDER BY campus_name, building_name, room_code");
                while ($o = mysqli_fetch_assoc($q)) {
                  $office_id = $o['id'];
                  $members_q = mysqli_query($connection, "SELECT users.names, users.email FROM allocation JOIN users ON allocation.user_id = users.id WHERE allocation.office_id = $office_id");
                  $members = [];
                  while ($m = mysqli_fetch_assoc($members_q)) {
                    $members[] = $m;
                  }
                  $current_occupants = count($members);
                  if ($members) {
                    foreach ($members as $idx => $mem) {
                      echo '<tr>';
                      if ($idx === 0) {
                        echo '<td rowspan="'.count($members).'">' . $i . '</td>';
                        echo '<td rowspan="'.count($members).'">' . htmlspecialchars($o['room_code']) . '</td>';
                        echo '<td rowspan="'.count($members).'">' . htmlspecialchars($o['building_name']) . '</td>';
                        echo '<td rowspan="'.count($members).'">' . htmlspecialchars($o['building_code']) . '</td>';
                        echo '<td rowspan="'.count($members).'">' . htmlspecialchars($o['campus_name']) . '</td>';
                        echo '<td rowspan="'.count($members).'">' . htmlspecialchars($o['max_occupants']) . '</td>';
                        echo '<td rowspan="'.count($members).'">' . $current_occupants . '</td>';
                      }
                      echo '<td>' . htmlspecialchars($mem['names']) . '</td>';
                      echo '<td>' . htmlspecialchars($mem['email']) . '</td>';
                      echo '</tr>';
                    }
                    $i++;
                  } else {
                    echo '<tr>';
                    echo '<td>' . $i++ . '</td>';
                    echo '<td>' . htmlspecialchars($o['room_code']) . '</td>';
                    echo '<td>' . htmlspecialchars($o['building_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($o['building_code']) . '</td>';
                    echo '<td>' . htmlspecialchars($o['campus_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($o['max_occupants']) . '</td>';
                    echo '<td>0</td>';
                    echo '<td colspan="2"><span class="text-muted">None</span></td>';
                    echo '</tr>';
                  }
                }
                ?>
              </tbody>
            </table>
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
<script>
// Export to Excel logic using SheetJS
function exportTableToExcel(tableId, filename) {
  var wb = XLSX.utils.table_to_book(document.getElementById(tableId), {sheet: "Sheet1"});
  XLSX.writeFile(wb, filename);
}
$('#exportUserReport').on('click', function() {
  exportTableToExcel('userReportTable', 'user_report.xlsx');
});
$('#exportOfficeReport').on('click', function() {
  exportTableToExcel('officeReportTable', 'office_report.xlsx');
});
</script>
</body>
</html> 
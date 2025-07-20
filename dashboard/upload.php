<?php
include('../connection.php');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jsonData'])) {
  $data = json_decode($_POST['jsonData'], true);
  $errors = [];
  $requiredHeaders = [
    'campus name',
    'building code',
    'building name',
    'room code',
    'maximum number of occupants'
  ];
  $rows = [];
  $roomCodesInFile = [];
  $rowNum = 1;
  $allowed_campuses = ['huye','nyagatare','rukara','nyarugenge','busogo','remera'];
  $session_campus = isset($_SESSION['campus']) ? $_SESSION['campus'] : null;
  foreach ($data as $row) {
    $rowNum++;
    // Normalize keys
    $rowAssoc = [];
    foreach ($requiredHeaders as $h) {
      $found = false;
      foreach ($row as $k => $v) {
        if (strtolower(trim($k)) === $h) {
          $rowAssoc[$h] = trim($v);
          $found = true;
          break;
        }
      }
      if (!$found) {
        $rowAssoc[$h] = '';
      }
    }
    // Campus validation
    $campus = strtolower($rowAssoc['campus name']);
    if (!in_array($campus, $allowed_campuses)) {
      $errors[] = "Invalid campus name '".$rowAssoc['campus name']."' at row $rowNum. Must be one of: ".implode(', ', $allowed_campuses);
    }
    if ($session_campus && $campus !== $session_campus) {
      $errors[] = "Campus at row $rowNum must match your campus: $session_campus.";
    }
    // Validation
    $roomCode = $rowAssoc['room code'];
    if (isset($roomCodesInFile[strtolower($roomCode)])) {
      $errors[] = "Duplicate room code '$roomCode' found in file at row $rowNum.";
    } else {
      $roomCodesInFile[strtolower($roomCode)] = true;
    }
    $roomCodeEsc = mysqli_real_escape_string($connection, $roomCode);
    $campusEsc = mysqli_real_escape_string($connection, $rowAssoc['campus name']);
    $q = mysqli_query($connection, "SELECT COUNT(*) as cnt FROM offices WHERE room_code='$roomCodeEsc' AND campus_name='$campusEsc'");
    $r = mysqli_fetch_assoc($q);
    if ($r['cnt'] > 0) {
      $errors[] = "Room code '$roomCode' at row $rowNum already exists in the database for campus '$campusEsc'.";
    }
    $maxOcc = $rowAssoc['maximum number of occupants'];
    if (!is_numeric($maxOcc) || intval($maxOcc) <= 0) {
      $errors[] = "Invalid 'Maximum number of occupants' at row $rowNum. Must be a positive integer.";
    }
    $rows[] = $rowAssoc;
  }
  // Check all required headers exist in the first row
  $firstRow = isset($data[0]) ? $data[0] : [];
  $headerNorm = array_map(function($h) { return strtolower(trim($h)); }, array_keys($firstRow));
  $missing = array_diff($requiredHeaders, $headerNorm);
  if (count($missing) > 0) {
    $errors[] = 'Missing required headers: '.implode(', ', $missing).'. Upload aborted.';
  }
  if (count($errors) > 0) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
  } else {
    $inserted = [];
    foreach ($rows as $rowData) {
      $campus = mysqli_real_escape_string($connection, $rowData['campus name']);
      $bcode = mysqli_real_escape_string($connection, $rowData['building code']);
      $bname = mysqli_real_escape_string($connection, $rowData['building name']);
      $rcode = mysqli_real_escape_string($connection, $rowData['room code']);
      $maxocc = (int)$rowData['maximum number of occupants'];
      $sql = "INSERT INTO offices (campus_name, building_code, building_name, room_code, max_occupants) VALUES ('$campus', '$bcode', '$bname', '$rcode', $maxocc)";
      mysqli_query($connection, $sql);
      $inserted[] = $rowData;
    }
    echo json_encode(['success' => true, 'data' => $inserted]);
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Import Offices</title>
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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body>
<?php
include ("./includes/header.php");
include ("./includes/menu.php");
?>
<main id="main" class="main">
  <div class="pagetitle">
    <h1><i class="bi bi-file-earmark-excel"></i> Import Excel Offices</h1>
  </div>
  <section class="section dashboard">
    <div class="row justify-content-center">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Upload Excel/CSV File</h5>
            <a href="template_offices.csv" class="btn btn-outline-primary mb-3"><i class="bi bi-download"></i> Download Template</a>
            <form id="excelForm" enctype="multipart/form-data">
              <div class="mb-3">
                <label for="excelFile" class="form-label">Select Excel/CSV File (.xlsx, .xls, .csv)</label>
                <input class="form-control" type="file" name="excelFile" id="excelFile" accept=".xlsx,.xls,.csv" required>
              </div>
              <button type="submit" class="btn btn-success"><i class="bi bi-upload"></i> Import</button>
            </form>
            <div id="result"></div>
            <script>
            document.getElementById('excelForm').addEventListener('submit', function(e) {
              e.preventDefault();
              var fileInput = document.getElementById('excelFile');
              var file = fileInput.files[0];
              if (!file) return;
              var reader = new FileReader();
              reader.onload = function(e) {
                var data = e.target.result;
                var workbook = XLSX.read(data, {type: 'binary'});
                var firstSheet = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheet];
                var json = XLSX.utils.sheet_to_json(worksheet, {defval: ''});
                // Send JSON to PHP via AJAX
                var formData = new FormData();
                formData.append('jsonData', JSON.stringify(json));
                fetch('upload.php', {
                  method: 'POST',
                  body: formData
                })
                .then(response => response.json())
                .then(res => {
                  var resultDiv = document.getElementById('result');
                  if (res.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success mt-3">Upload successful! Data:</div>' + '<pre>' + JSON.stringify(res.data, null, 2) + '</pre>';
                  } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger mt-3"><b>Upload aborted due to the following errors:</b><ul><li>' + res.errors.join('</li><li>') + '</li></ul></div>';
                  }
                })
                .catch(err => {
                  document.getElementById('result').innerHTML = '<div class="alert alert-danger mt-3">Error uploading file.</div>';
                });
              };
              reader.readAsBinaryString(file);
            });
            </script>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>
<?php include ("./includes/footer.php"); ?>
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
</body>
</html>



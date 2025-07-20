<?php
// Enable error reporting for debugging (remove or comment out in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection
include('../connection.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>UR-HUYE Dashboard</title>
    <meta content="Dashboard for UR-HUYE Cards" name="description">
    <meta content="cards, statistics, dashboard" name="keywords">

    <!-- Favicons -->
    <link href="assets/img/icon1.png" rel="icon">
    <link href="assets/img/icon1.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|
    Nunito:300,300i,400,400i,600,600i,700,700i|
    Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

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

    <!-- Optional: Include Chart.js for visual charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .col-lg-12 {
            margin-top: 20px;
        }
        .card {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            background: #fff;
        }
        .card-body {
            padding: 20px;
        }
        #lineChart {
            max-width: 100%;
            margin: 0 auto;
        }
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
    </style>
</head>

<body>

    <?php
    include("./includes/header.php");
    include("./includes/menu.php");
    ?>

    <?php
    // Dashboard statistics
    $total_users = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM users"))['cnt'];
    $total_offices = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM offices"))['cnt'];
    $total_allocations = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM allocation"))['cnt'];
    $total_unallocated = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM users WHERE id NOT IN (SELECT user_id FROM allocation)"))['cnt'];
    $total_available_offices = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM offices WHERE id NOT IN (SELECT office_id FROM allocation)"))['cnt'];
    $total_pending_alloc = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM allocation WHERE status = 'pending'"))['cnt'];
    $total_approved_alloc = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM allocation WHERE status = 'approved'"))['cnt'];
    // Office occupancy breakdown
    $occ_stats = ['Full'=>0,'Partially Occupied'=>0,'Available'=>0,'Over-Occupied'=>0];
    $q = mysqli_query($connection, "SELECT id, max_occupants FROM offices");
    while ($o = mysqli_fetch_assoc($q)) {
      $oid = $o['id'];
      $max_occ = intval($o['max_occupants']);
      $occ_count = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM allocation WHERE office_id = $oid"));
      $occ_count = $occ_count ? intval($occ_count['cnt']) : 0;
      if ($occ_count > $max_occ) $occ_stats['Over-Occupied']++;
      elseif ($occ_count == $max_occ && $max_occ > 0) $occ_stats['Full']++;
      elseif ($occ_count > 0) $occ_stats['Partially Occupied']++;
      else $occ_stats['Available']++;
    }
    $total_spaces = mysqli_fetch_assoc(mysqli_query($connection, "SELECT SUM(max_occupants) as total FROM offices"))['total'];
    $total_occupied = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM allocation"))['cnt'];
    $total_available_spaces = $total_spaces - $total_occupied;
    ?>
    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Dashboard</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center text-dark" style="min-height:120px;">
                    <div class="card-body py-3 px-2 d-flex flex-column align-items-center justify-content-center">
                        <div class="mb-1" style="font-size:2rem;"><i class="bi bi-people"></i></div>
                        <div class="small fw-bold">Total Users</div>
                        <div class="fs-5 fw-bold"><?= $total_users ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center text-dark" style="min-height:120px;">
                    <div class="card-body py-3 px-2 d-flex flex-column align-items-center justify-content-center">
                        <div class="mb-1" style="font-size:2rem;"><i class="bi bi-building"></i></div>
                        <div class="small fw-bold">Total Offices</div>
                        <div class="fs-5 fw-bold"><?= $total_offices ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center text-dark" style="min-height:120px;">
                    <div class="card-body py-3 px-2 d-flex flex-column align-items-center justify-content-center">
                        <div class="mb-1" style="font-size:2rem;"><i class="bi bi-person-check"></i></div>
                        <div class="small fw-bold">Total Allocations</div>
                        <div class="fs-5 fw-bold"><?= $total_allocations ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center text-dark" style="min-height:120px;">
                    <div class="card-body py-3 px-2 d-flex flex-column align-items-center justify-content-center">
                        <div class="mb-1" style="font-size:2rem;"><i class="bi bi-person-x"></i></div>
                        <div class="small fw-bold">Unallocated Users</div>
                        <div class="fs-5 fw-bold"><?= $total_unallocated ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center text-dark" style="min-height:120px;">
                    <div class="card-body py-3 px-2 d-flex flex-column align-items-center justify-content-center">
                        <div class="mb-1" style="font-size:2rem;"><i class="bi bi-hourglass-split"></i></div>
                        <div class="small fw-bold">Pending Allocations</div>
                        <div class="fs-5 fw-bold"><?= $total_pending_alloc ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center text-dark" style="min-height:120px;">
                    <div class="card-body py-3 px-2 d-flex flex-column align-items-center justify-content-center">
                        <div class="mb-1" style="font-size:2rem;"><i class="bi bi-check-circle"></i></div>
                        <div class="small fw-bold">Approved Allocations</div>
                        <div class="fs-5 fw-bold"><?= $total_approved_alloc ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center text-dark" style="min-height:120px;">
                    <div class="card-body py-3 px-2 d-flex flex-column align-items-center justify-content-center">
                        <div class="mb-1" style="font-size:2rem;"><i class="bi bi-box"></i></div>
                        <div class="small fw-bold">Available Spaces</div>
                        <div class="fs-5 fw-bold"><?= $total_available_spaces ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center text-dark" style="min-height:120px;">
                    <div class="card-body py-3 px-2 d-flex flex-column align-items-center justify-content-center">
                        <div class="mb-1" style="font-size:2rem;"><i class="bi bi-bar-chart"></i></div>
                        <div class="small fw-bold">Total Offices (No Allocations)</div>
                        <div class="fs-5 fw-bold"><?= $total_available_offices ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-pie-chart"></i> Office Occupancy</h5>
                        <canvas id="occupancyPieChart" height="220"></canvas>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-bar-chart"></i> Spaces Overview</h5>
                        <canvas id="spacesBarChart" height="180"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-pie-chart"></i> Allocation Status</h5>
                        <canvas id="allocationDoughnutChart" height="180"></canvas>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-bar-chart"></i> Quick Insights</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Available Offices: <span class="fw-bold text-success"><?= $occ_stats['Available'] ?></span></li>
                            <li class="list-group-item">Full Offices: <span class="fw-bold text-primary"><?= $occ_stats['Full'] ?></span></li>
                            <li class="list-group-item">Partially Occupied Offices: <span class="fw-bold text-warning"><?= $occ_stats['Partially Occupied'] ?></span></li>
                            <li class="list-group-item">Over-Occupied Offices: <span class="fw-bold text-danger"><?= $occ_stats['Over-Occupied'] ?></span></li>
                            <li class="list-group-item">Total Available Offices (no allocations): <span class="fw-bold text-info"><?= $total_available_offices ?></span></li>
                            <li class="list-group-item">Pending Allocations: <span class="fw-bold text-secondary"><?= $total_pending_alloc ?></span></li>
                            <li class="list-group-item">Approved Allocations: <span class="fw-bold text-success"><?= $total_approved_alloc ?></span></li>
                            <li class="list-group-item">Available Spaces: <span class="fw-bold text-primary"><?= $total_available_spaces ?></span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>
    // Office Occupancy Pie Chart
    const occData = {
      labels: ['Available', 'Full', 'Partially Occupied', 'Over-Occupied'],
      datasets: [{
        data: [<?= $occ_stats['Available'] ?>, <?= $occ_stats['Full'] ?>, <?= $occ_stats['Partially Occupied'] ?>, <?= $occ_stats['Over-Occupied'] ?>],
        backgroundColor: ['#20c997', '#0d6efd', '#ffc107', '#dc3545'],
        borderWidth: 1
      }]
    };
    const occConfig = {
      type: 'pie',
      data: occData,
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' },
          title: { display: false }
        }
      }
    };
    // Spaces Bar Chart
    const spacesBarData = {
      labels: ['Total Spaces', 'Occupied', 'Available'],
      datasets: [{
        label: 'Spaces',
        data: [<?= $total_spaces ?>, <?= $total_occupied ?>, <?= $total_available_spaces ?>],
        backgroundColor: ['#0d6efd', '#dc3545', '#20c997'],
        borderWidth: 1
      }]
    };
    const spacesBarConfig = {
      type: 'bar',
      data: spacesBarData,
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          title: { display: false }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    };
    // Allocation Status Doughnut Chart
    const allocDoughnutData = {
      labels: ['Pending', 'Approved'],
      datasets: [{
        data: [<?= $total_pending_alloc ?>, <?= $total_approved_alloc ?>],
        backgroundColor: ['#6c757d', '#198754'],
        borderWidth: 1
      }]
    };
    const allocDoughnutConfig = {
      type: 'doughnut',
      data: allocDoughnutData,
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' },
          title: { display: false }
        }
      }
    };
    window.addEventListener('DOMContentLoaded', function() {
      new Chart(document.getElementById('occupancyPieChart'), occConfig);
      new Chart(document.getElementById('spacesBarChart'), spacesBarConfig);
      new Chart(document.getElementById('allocationDoughnutChart'), allocDoughnutConfig);
    });
    </script>

    <!-- ======= Footer ======= -->
    <?php
    include("./includes/footer.php");
    ?>
    <!-- End Footer -->

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

</body>

</html>

<?php
include('./includes/header.php');
include('./includes/menu.php');
include('../connection.php');
?>
<main id="main" class="main">
  <div class="pagetitle">
    <h1><i class="bi bi-file-earmark-excel"></i> Import Users</h1>
  </div>
  <section class="section dashboard">
    <div class="row justify-content-center">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Upload Users Excel/CSV File</h5>
            <a href="template_users.csv" class="btn btn-outline-primary mb-3"><i class="bi bi-download"></i> Download Template</a>
            <form action="upload_users.php" method="post" enctype="multipart/form-data">
              <div class="mb-3">
                <label for="excelFile" class="form-label">Select Excel/CSV File (.xlsx, .xls, .csv)</label>
                <input class="form-control" type="file" name="excelFile" id="excelFile" accept=".xlsx,.xls,.csv" required>
              </div>
              <button type="submit" name="import" class="btn btn-success"><i class="bi bi-upload"></i> Import</button>
            </form>
            <?php
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
                    echo '<div class="alert alert-danger mt-3">Missing required headers: '.implode(', ', $missing).'. Upload aborted.</div>';
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
                      echo '<div class="alert alert-danger mt-3"><b>Upload aborted due to the following errors:</b><ul><li>' . implode('</li><li>', $errors) . '</li></ul></div>';
                    } else {
                      foreach ($data as $rowData) {
                        $names = mysqli_real_escape_string($connection, $rowData['names']);
                        $email = mysqli_real_escape_string($connection, $rowData['email']);
                        $phone = mysqli_real_escape_string($connection, $rowData['phone']);
                        $role = mysqli_real_escape_string($connection, $rowData['role']);
                        $image = mysqli_real_escape_string($connection, $rowData['image']);
                        $password = password_hash('1234', PASSWORD_BCRYPT);
                        mysqli_query($connection, "INSERT INTO users (names, email, phone, role, image, password, active) VALUES ('$names', '$email', '$phone', '$role', 'upload/icon1.png', '$password', 1)");
                      }
                      echo '<div class="alert alert-success mt-3">Users uploaded successfully!</div>';
                    }
                  }
                }
              } else {
                echo '<div class="alert alert-danger mt-3">Only CSV files are supported in this demo. Please convert your Excel file to CSV.</div>';
              }
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>
<?php include('./includes/footer.php'); ?> 
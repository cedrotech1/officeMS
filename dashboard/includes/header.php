<?php
include('../connection.php');
  
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }
  

if(!isset($_SESSION['loggedin'])){
    echo"<script>window.location.href='../loginOM.php'</script>";

}
$userid=$_SESSION['id'];
// $userid=1;
$ok1 = mysqli_query($connection, "select * from users where id=$userid");
                  while ($row = mysqli_fetch_array($ok1)) {
                    $id = $row["id"];
                    $names = $row["names"];
                    $image = $row["image"];
                    $phone = $row["phone"];
                    $email = $row["email"];
                    $about = $row["about"];
                    $role = $row["role"];
                    
                }
// Fetch latest 4 notifications and unread count for the user
$notif_q = mysqli_query($connection, "SELECT * FROM notifications WHERE receiver_id = $userid ORDER BY created_at DESC LIMIT 4");
$notifications = [];
while ($row = mysqli_fetch_assoc($notif_q)) {
  $notifications[] = $row;
}
$notif_count = 0;
$notif_unread = 0;
$count_q = mysqli_query($connection, "SELECT COUNT(*) as cnt FROM notifications WHERE receiver_id = $userid");
if ($row = mysqli_fetch_assoc($count_q)) $notif_count = $row['cnt'];
$unread_q = mysqli_query($connection, "SELECT COUNT(*) as cnt FROM notifications WHERE receiver_id = $userid AND status='unread'");
if ($row = mysqli_fetch_assoc($unread_q)) $notif_unread = $row['cnt'];
?>
<!-- ======= Header ======= -->

<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center justify-content-between">
    <a href="users-profile.php" class="logo d-flex align-items-center">
      <!-- <img src="assets/img/icon1.png" alt="" style='height:1.5cm'> -->
      <span class="d-none d-lg-block">OFFICE MS</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div><!-- End Logo -->

  <div class="search-bar">
    <form class="search-form d-flex align-items-center" method="POST" action="#">
      <input type="text" name="query" placeholder="Search" title="Enter search keyword">
      <button type="submit" title="Search"><i class="bi bi-search"></i></button>
    </form>
  </div><!-- End Search Bar -->

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">

      <li class="nav-item d-block d-lg-none">
        <a class="nav-link nav-icon search-bar-toggle " href="#">
          <i class="bi bi-search"></i>
        </a>
      </li><!-- End Search Icon-->

      <li class="nav-item dropdown">
        <a class="nav-link nav-icon position-relative" href="#" data-bs-toggle="dropdown" id="notifDropdown">
          <i class="bi bi-bell"></i>
          <?php if ($notif_unread > 0): ?>
            <span class="badge bg-danger badge-number"><?php echo $notif_unread; ?></span>
          <?php endif; ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications" aria-labelledby="notifDropdown" style="min-width:340px;max-width:400px;">
          <li class="dropdown-header">
            Notifications
            <span class="badge rounded-pill bg-primary p-2 ms-2"><?php echo $notif_unread; ?> unread</span>
            <a href="notifications.php" class="float-end small">View all</a>
          </li>
          <li><hr class="dropdown-divider"></li>
          <?php if (count($notifications) === 0): ?>
            <li class="text-center text-muted py-3">No notifications</li>
          <?php else: ?>
            <?php foreach ($notifications as $n): ?>
              <li class="px-2 py-2 <?php echo $n['status']==='unread'?'bg-light':''; ?>">
                <div class="d-flex align-items-start gap-2">
                  <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;
                    <?php
                      if ($n['type'] === 'success') echo 'background:#27ae60;color:#fff;';
                      elseif ($n['type'] === 'error') echo 'background:#e74c3c;color:#fff;';
                      elseif ($n['type'] === 'warning') echo 'background:#f1c40f;color:#222;';
                      else echo 'background:#3498db;color:#fff;';
                    ?>">
                    <?php
                      if ($n['type'] === 'success') echo '<i class="bi bi-check-circle"></i>';
                      elseif ($n['type'] === 'error') echo '<i class="bi bi-x-circle"></i>';
                      elseif ($n['type'] === 'warning') echo '<i class="bi bi-exclamation-triangle"></i>';
                      else echo '<i class="bi bi-info-circle"></i>';
                    ?>
                  </div>
                  <div class="flex-grow-1" style="min-width:0;">
                    <div class="fw-bold text-truncate" style="max-width:220px;">
                      <?php echo htmlspecialchars($n['title']); ?>
                      <?php if ($n['status']==='unread'): ?><span class="badge bg-primary ms-1">new</span><?php endif; ?>
                    </div>
                    <div class="small text-muted text-truncate" style="max-width:220px;">
                      <?php echo htmlspecialchars(mb_strimwidth($n['message'],0,60,'...')); ?>
                    </div>
                    <div class="small text-secondary mt-1">
                      <?php
                        $created = strtotime($n['created_at']);
                        $now = time();
                        $diff = $now - $created;
                        if ($diff < 60) echo 'Just now';
                        elseif ($diff < 3600) echo intval($diff/60) . ' min ago';
                        elseif ($diff < 86400) echo intval($diff/3600) . ' hr ago';
                        elseif ($diff < 604800) echo intval($diff/86400) . ' d ago';
                        else echo date('M d, Y', $created);
                      ?>
                    </div>
                  </div>
                </div>
              </li>
              <li><hr class="dropdown-divider my-1"></li>
            <?php endforeach; ?>
          <?php endif; ?>
          <li class="dropdown-footer text-center">
            <a href="notifications.php">Show all notifications</a>
          </li>
        </ul>
      </li><!-- End Notification Nav -->

   
      <li class="nav-item dropdown pe-3">

        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <img src="<?php echo $image ?>" alt="Profile" class="rounded-circle" style="height:1cm;width:1cm">
          <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $names; ?></span>
        </a><!-- End Profile Iamge Icon -->

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            <h6><?php echo $names;?></h6>
            <span><?php echo $role; ?></span>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="users-profile.php">
              <i class="bi bi-person"></i>
              <span>My Profile</span>
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="users-profile.php">
              <i class="bi bi-gear"></i>
              <span>Account Settings</span>
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="../index.php">
              <i class="bi bi-question-circle"></i>
              <span>go user page</span>
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="./logout.php">
              <i class="bi bi-box-arrow-right"></i>
              <span>Sign Out</span>
            </a>
          </li>

        </ul><!-- End Profile Dropdown Items -->
      </li><!-- End Profile Nav -->

    </ul>
  </nav><!-- End Icons Navigation -->

</header><!-- End Header -->
<?php
session_start();
include('../connection.php');
$userid = $_SESSION['id'] ?? null;
if (!$userid) {
  echo '<div class="alert alert-danger">You must be logged in to view notifications.</div>';
  exit;
}
// Handle AJAX mark as read
if (isset($_POST['ajax_mark_read']) && isset($_POST['id'])) {
  $nid = intval($_POST['id']);
  mysqli_query($connection, "UPDATE notifications SET status='read', updated_at=NOW() WHERE id=$nid AND receiver_id=$userid");
  echo json_encode(['success'=>true]);
  exit;
}
// Handle AJAX mark all as read
if (isset($_POST['ajax_mark_all_read'])) {
  mysqli_query($connection, "UPDATE notifications SET status='read', updated_at=NOW() WHERE receiver_id=$userid AND status='unread'");
  echo json_encode(['success'=>true]);
  exit;
}
// Handle AJAX delete notification
if (isset($_POST['ajax_delete']) && isset($_POST['id'])) {
  $nid = intval($_POST['id']);
  mysqli_query($connection, "DELETE FROM notifications WHERE id=$nid AND receiver_id=$userid");
  echo json_encode(['success'=>true]);
  exit;
}
// Handle AJAX delete all notifications
if (isset($_POST['ajax_delete_all'])) {
  mysqli_query($connection, "DELETE FROM notifications WHERE receiver_id=$userid");
  echo json_encode(['success'=>true]);
  exit;
}
$notifications = [];
$q = mysqli_query($connection, "SELECT * FROM notifications WHERE receiver_id = $userid ORDER BY created_at DESC");
while ($row = mysqli_fetch_assoc($q)) {
  $notifications[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Notifications - UR-HUYE</title>
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
  <style>
.ig-notification-list {
  padding: 0;
  margin: 0;
  list-style: none;
}
.ig-notification-item {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 16px 12px;
  border-bottom: 1px solid #f0f0f0;
  background: #fff;
  transition: background 0.2s;
}
.ig-notification-item.unread {
  background: #f6f8fa;
  font-weight: 600;
}
.ig-notification-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #e9ecef;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.7rem;
  color: #fff;
  flex-shrink: 0;
}
.ig-notification-content {
  flex: 1;
  min-width: 0;
}
.ig-notification-title {
  font-weight: 600;
  margin-bottom: 2px;
  font-size: 1rem;
  color: #222;
}
.ig-notification-message {
  color: #555;
  font-size: 0.97rem;
  margin-bottom: 2px;
  white-space: pre-line;
}
.ig-notification-time {
  color: #888;
  font-size: 0.85rem;
}
.ig-notification-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #1877f2;
  display: inline-block;
  margin-right: 6px;
  vertical-align: middle;
}
</style>
</head>
<body>
<?php
include ("./includes/header.php");  
include ("./includes/menu.php");
?>
<main id="main" class="main">
  <div class="pagetitle">
    <h1><i class="bi bi-bar-chart"></i> Notifications</h1>
  </div>
  <section class="section dashboard">
    <div class="card p-3 shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Notifications</h5>
        <div>
          <?php if (count(array_filter($notifications, fn($n) => $n['status']==='unread')) > 0): ?>
            <button class="btn btn-sm btn-outline-primary me-2" id="markAllReadBtn"><i class="bi bi-check2-all"></i> Mark all as read</button>
          <?php endif; ?>
          <?php if (count($notifications) > 0): ?>
            <button class="btn btn-sm btn-outline-danger" id="deleteAllBtn"><i class="bi bi-trash"></i> Delete all</button>
          <?php endif; ?>
        </div>
      </div>
      <?php if (count($notifications) === 0): ?>
        <div class="alert alert-info text-center mb-0">No notifications yet.</div>
      <?php else: ?>
        <ul class="ig-notification-list">
          <?php foreach ($notifications as $n): ?>
            <li class="ig-notification-item<?php echo $n['status'] === 'unread' ? ' unread' : ''; ?>" data-nid="<?php echo $n['id']; ?>">
              <div class="ig-notification-avatar" style="background:
                <?php
                  if ($n['type'] === 'success') echo '#27ae60';
                  elseif ($n['type'] === 'error') echo '#e74c3c';
                  elseif ($n['type'] === 'warning') echo '#f1c40f;color:#222;';
                  else echo '#3498db';
                ?>">
                <?php
                  if ($n['type'] === 'success') echo '<i class="bi bi-check-circle"></i>';
                  elseif ($n['type'] === 'error') echo '<i class="bi bi-x-circle"></i>';
                  elseif ($n['type'] === 'warning') echo '<i class="bi bi-exclamation-triangle"></i>';
                  else echo '<i class="bi bi-info-circle"></i>';
                ?>
              </div>
              <div class="ig-notification-content">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="ig-notification-title">
                    <?php if ($n['status'] === 'unread'): ?><span class="ig-notification-dot"></span><?php endif; ?>
                    <?php echo htmlspecialchars($n['title']); ?>
                  </div>
                  <button class="btn btn-link btn-sm text-danger p-0 ms-2 delete-notif-btn" title="Delete" data-nid="<?php echo $n['id']; ?>"><i class="bi bi-trash"></i></button>
                </div>
                <div class="ig-notification-message"><?php echo nl2br(htmlspecialchars($n['message'])); ?></div>
                <div class="d-flex align-items-center justify-content-between">
                  <div class="ig-notification-time">
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
                  <?php if ($n['status'] === 'unread'): ?>
                    <button class="btn btn-link btn-sm text-primary mark-read-btn" data-nid="<?php echo $n['id']; ?>">Mark as read</button>
                  <?php endif; ?>
                </div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
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
$(function(){
  $('.mark-read-btn').on('click', function(){
    var $li = $(this).closest('.ig-notification-item');
    var nid = $(this).data('nid');
    $.post('notifications.php', {ajax_mark_read:1, id:nid}, function(resp){
      $li.removeClass('unread');
      $li.find('.ig-notification-dot').remove();
      $li.find('.mark-read-btn').remove();
    });
  });
  $('.delete-notif-btn').on('click', function(){
    var $li = $(this).closest('.ig-notification-item');
    var nid = $(this).data('nid');
    $.post('notifications.php', {ajax_delete:1, id:nid}, function(resp){
      $li.slideUp(200, function(){ $(this).remove(); });
    });
  });
  $('#markAllReadBtn').on('click', function(){
    $.post('notifications.php', {ajax_mark_all_read:1}, function(resp){
      $('.ig-notification-item.unread').removeClass('unread').find('.ig-notification-dot').remove();
      $('.mark-read-btn').remove();
    });
  });
  $('#deleteAllBtn').on('click', function(){
    $.post('notifications.php', {ajax_delete_all:1}, function(resp){
      $('.ig-notification-list').slideUp(200, function(){ $(this).remove(); });
      $('.alert-info').remove();
      $('.card .d-flex').after('<div class="alert alert-info text-center mb-0">No notifications yet.</div>');
    });
  });
});
</script>
</body>
</html> 
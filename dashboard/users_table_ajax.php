<?php
// users_table_ajax.php
include_once '../connection.php';
$limit = 10;
$page = isset($_POST['page']) ? (int)$_POST['page'] : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
$where = ["role = 'officer'"];
$params = [];
$types = '';
if (!empty($_POST['search'])) {
  $where[] = "(email LIKE ? OR phone LIKE ?)";
  $params[] = '%' . $_POST['search'] . '%';
  $params[] = '%' . $_POST['search'] . '%';
  $types .= 'ss';
} elseif (!empty($_GET['search'])) {
  $where[] = "(email LIKE ? OR phone LIKE ?)";
  $params[] = '%' . $_GET['search'] . '%';
  $params[] = '%' . $_GET['search'] . '%';
  $types .= 'ss';
}
if (!empty($_POST['campus'])) {
  $where[] = "campus = ?";
  $params[] = $_POST['campus'];
  $types .= 's';
} elseif (!empty($_GET['campus'])) {
  $where[] = "campus = ?";
  $params[] = $_GET['campus'];
  $types .= 's';
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
// Get total count
$count_sql = "SELECT COUNT(*) FROM users $where_sql";
$count_stmt = $connection->prepare($count_sql);
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total_users);
$count_stmt->fetch();
$count_stmt->close();
// Get users for current page
$sql = "SELECT id, email, phone, campus FROM users $where_sql ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $connection->prepare($sql);
if ($types) {
  $params[] = $limit;
  $params[] = $offset;
  $stmt->bind_param($types . 'ii', ...$params);
} else {
  $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$sn = $offset + 1;
?>
<div class="card">
  <div class="card-header">Offices Manager List</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table  table-hover">
        <thead>
          <tr>
            <th>#</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Campus</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?php echo $sn++; ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['phone']); ?></td>
            <td><?php echo htmlspecialchars(ucfirst($row['campus'])); ?></td>
          </tr>
          <?php endwhile; $stmt->close(); ?>
        </tbody>
      </table>
    </div>
    <!-- Pagination -->
    <?php
    $total_pages = ceil($total_users / $limit);
    if ($total_pages > 1):
    ?>
    <nav>
      <ul class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
            <a class="page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</div> 
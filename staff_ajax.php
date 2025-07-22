<?php
include_once 'connection.php';
$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';
$name_filter = isset($_GET['name']) ? trim($_GET['name']) : '';
$where = "active=1";
$params = [];
if ($role_filter !== '') {
    $where .= " AND role = ?";
    $params[] = $role_filter;
}
if ($name_filter !== '') {
    $where .= " AND names LIKE ?";
    $params[] = $name_filter . '%';
}
$count = 0;
$count_sql = "SELECT COUNT(*) as cnt FROM users WHERE $where";
$count_stmt = $connection->prepare($count_sql);
if ($params) {
    $types = str_repeat('s', count($params));
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
if ($row = $count_result->fetch_assoc()) $count = $row['cnt'];
$sql = "SELECT * FROM users WHERE $where ORDER BY names ASC LIMIT $per_page OFFSET $offset";
$stmt = $connection->prepare($sql);
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$bg = true;
$html = '';
if ($result && $result->num_rows > 0) {
    while ($user = $result->fetch_assoc()) {
        $bgcolor = $bg ? '#f7f8fa' : '#fff';
        $bg = !$bg;
        $avatar = '';
        if ($user['image']!='upload/icon1.png') {
            $avatar = '<img src="dashboard/' . htmlspecialchars($user['image']) . '" class="staff-avatar" alt="' . htmlspecialchars($user['names']) . '">';
        } else {
            $names = explode(' ', $user['names']);
            $initials = '';
            foreach ($names as $n) { $initials .= strtoupper(mb_substr($n,0,1)); }
            $avatar = '<div class="staff-initials">' . $initials . '</div>';
        }
        $html .= '<div class="row staff-row align-items-center py-3 mx-0" style="background:'.$bgcolor.'; border-bottom:1px solid #e5e7eb;">';
        $html .= '<div class="col-auto d-flex align-items-center justify-content-center">'.$avatar.'</div>';
        $html .= '<div class="col-md-4 col-12">';
        $html .= '<div class="fw-bold" style="color:#223; font-size:1.15rem;">'.htmlspecialchars($user['names']).'</div>';
        $html .= '<div class="text-muted small">'.htmlspecialchars($user['about']).'</div>';
        $html .= '</div>';
        $html .= '<div class="col-md-4 col-12 mt-2 mt-md-0">';
        $html .= '<div><a href="mailto:'.htmlspecialchars($user['email']).'" style="color:#223;">'.htmlspecialchars($user['email']).'</a></div>';
        if (!empty($user['phone'])) $html .= '<div class="text-muted small">'.htmlspecialchars($user['phone']).'</div>';
        $html .= '</div>';
        $html .= '<div class="col-md-3 col-12 mt-2 mt-md-0 text-md-start text-start">'.htmlspecialchars($user['role']).'</div>';
        $html .= '</div>';
    }
} else {
    $html .= '<div class="text-center py-5">No staff found.</div>';
}
// Output JSON for count and HTML
header('Content-Type: application/json');
echo json_encode([
    'count' => $count,
    'html' => $html
]); 
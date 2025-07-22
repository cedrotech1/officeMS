<?php
include_once 'connection.php';
// Pagination setup
$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;
// Filters
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';
$alpha_filter = isset($_GET['alpha']) ? trim($_GET['alpha']) : '';
// Build WHERE clause
$where = "active=1";
$params = [];
if ($role_filter !== '') {
    $where .= " AND role = ?";
    $params[] = $role_filter;
}
if ($alpha_filter !== '') {
    $where .= " AND names LIKE ?";
    $params[] = $alpha_filter . '%';
}
// Get total count
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
$total_pages = max(1, ceil($count / $per_page));
// Get unique roles for filter dropdown
$roles = [];
$roles_result = $connection->query("SELECT DISTINCT role FROM users WHERE active=1 AND role != '' ORDER BY role ASC");
while ($r = $roles_result->fetch_assoc()) {
    $roles[] = $r['role'];
}
// Fetch users
$sql = "SELECT * FROM users WHERE $where ORDER BY names ASC LIMIT $per_page OFFSET $offset";
$stmt = $connection->prepare($sql);
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$bg = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty & Staff Directory</title>
    <link rel="stylesheet" href="dashboard/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboard/assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard/assets/css/style.css">
    <style>
        body { background: #f8f9fa; }
        .directory-navbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.5rem 0;
        }
        .directory-navbar .logo {
            height: 40px;
            margin-right: 10px;
        }
        .directory-navbar .brand {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .directory-navbar .search-box {
            max-width: 220px;
        }
        .directory-navbar .apply-btn {
            background: #d7262b;
            color: #fff;
            font-weight: 600;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 4px;
        }
        .directory-header {
            background: #151e3d;
            color: #fff;
            padding: 2.5rem 0 1.5rem 0;
        }
        .directory-header h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .directory-header .nav-pills .nav-link {
            background: none;
            color: #fff;
            font-weight: 500;
            font-size: 1.1rem;
            margin-right: 1.5rem;
            border-radius: 0;
            padding: 0.5rem 0.75rem;
        }
        .directory-header .nav-pills .nav-link.active {
            background: #223;
            color: #fff;
        }
        .filter-bar {
            margin: 2rem 0 1rem 0;
        }
        .filter-bar select {
            min-width: 180px;
        }
        .directory-list {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            margin-bottom: 1rem;
            padding: 0;
        }
        .staff-row {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
            padding: 1.25rem 2rem 1.25rem 1.5rem;
        }
        .staff-row:last-child {
            border-bottom: none;
        }
        .staff-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1.25rem;
            border: 2px solid #e5e7eb;
        }
        .staff-initials {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #223;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 700;
            margin-right: 1.25rem;
        }
        .staff-info {
            flex: 1 1 0%;
        }
        .staff-info .name {
            font-weight: 600;
            font-size: 1.1rem;
        }
        .staff-info .title {
            color: #555;
            font-size: 0.98rem;
        }
        .staff-contact {
            min-width: 210px;
            font-size: 0.97rem;
            color: #223;
        }
        .staff-contact a {
            color: #223;
            text-decoration: none;
        }
        .staff-contact a:hover {
            text-decoration: underline;
        }
        .staff-dept {
            min-width: 220px;
            text-align: right;
            color: #223;
            font-size: 0.97rem;
        }
        .alpha-nav {
            position: absolute;
            /* Set top to the height below navbar, header, and filter-bar. Adjust as needed. */
            top: 229px;
            right: 0;
            width: 38px;
            /* height: calc(100vh - 220px); */
            background: rgba(255,255,255,0.95);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 0;
            border-left: 1px solid #e5e7eb;
            box-shadow: -2px 0 8px rgba(0,0,0,0.04);
            transition: all 0.2s;
        }
        .alpha-nav .az-label {
            display: none;
        }
        .alpha-nav a {
            display: block;
            color: #223;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.05rem;
            margin: 6px 0;
            width: 100%;
            text-align: center;
            transition: background 0.2s, color 0.2s;
        }
        .alpha-nav a:hover {
            color: #d7262b;
            background: #f0f0f0;
        }
        @media (max-width: 991px) {
            .staff-row, .directory-list { padding-left: 0.5rem; padding-right: 0.5rem; }
            .alpha-nav {
                position: fixed;
                top: auto;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100vw;
                height: 44px;
                background: #fff;
                border-left: none;
                border-top: 1px solid #e5e7eb;
                box-shadow: 0 -2px 8px rgba(0,0,0,0.04);
                flex-direction: row;
                align-items: center;
                justify-content: center;
                padding: 0 2px;
            }
            .alpha-nav a {
                display: inline-block;
                margin: 0 2px;
                width: 28px;
                font-size: 1rem;
                padding: 0;
            }
            .staff-dept { text-align: left; margin-top: 0.5rem; }
            .directory-header h1 { font-size: 1.5rem; }
            .directory-header .nav-pills .nav-link { font-size: 1rem; margin-right: 0.5rem; }
            .filter-bar { margin: 1rem 0 0.5rem 0; }
            .staff-row { flex-direction: column; align-items: flex-start; padding: 1rem 0.5rem; }
            .staff-avatar, .staff-initials { margin-bottom: 0.5rem; }
            .staff-contact, .staff-dept { margin-left: 0 !important; min-width: 0; text-align: left; margin-top: 0.5rem; }
            .staff-info .name { font-size: 1rem; }
            .staff-info .title { font-size: 0.95rem; }
        }
        @media (max-width: 575px) {
            .directory-navbar .brand { font-size: 1.1rem; }
            .directory-navbar .search-box { max-width: 120px; }
            .directory-header { padding: 1.2rem 0 0.7rem 0; }
            .directory-list { border-radius: 0; box-shadow: none; }
            .alpha-nav { height: 36px; }
            .alpha-nav a { width: 20px; font-size: 0.95rem; }
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="directory-navbar d-flex align-items-center justify-content-between px-4">
        <div class="d-flex align-items-center">
            <img src="dashboard/assets/img/icon1.png" alt="Logo" class="logo" style="height: 1.5cm;width:1.5cm">
            <span class="brand">UR <span style="color:#d7262b;">STAFF</span> / DIRECTORY</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <form class="me-3 d-none d-md-block">
                <input type="text" class="form-control search-box" placeholder="Search">
            </form>
            <button class="apply-btn">APPLY</button>
        </div>
    </nav>
    <!-- Header Section -->
    <div class="directory-header">
        <div class="container">
            <ul class="nav nav-pills mb-3">
                <li class="nav-item">
                    <a class="nav-link active" href="#">Faculty & Staff</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Offices</a>
                </li>
            </ul>
            <h1>Faculty & Staff</h1>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row filter-bar align-items-center g-2 mb-0" id="filter-bar">
            <div class="col-md-3 col-12 mb-2 mb-md-0">
                <input type="text" id="filter-name" name="name" class="form-control" placeholder="Search by Name..." value="<?php echo htmlspecialchars($alpha_filter); ?>">
            </div>
            <div class="col-md-3 col-12 mb-2 mb-md-0">
                <select id="filter-role" name="role" class="form-select">
                    <option value="">-- Filter by Academic Position --</option>
                    <?php foreach ($roles as $role) {
                        $selected = ($role_filter === $role) ? 'selected' : '';
                        echo '<option value="'.htmlspecialchars($role).'" '.$selected.'>'.htmlspecialchars($role).'</option>';
                    } ?>
                </select>
            </div>
            <div class="col-md-2 col-12 mb-2 mb-md-0">
                <button type="button" id="reset-filters" class="btn btn-secondary w-100">Reset</button>
            </div>
            <div class="col-md-4 col-12 text-end">
                <span style="color:#888;font-size:0.98rem;font-weight:500;">Showing <strong id="result-count"><?php echo $count; ?></strong> results</span>
            </div>
        </div>
        <div id="ajax-loading" style="display:none;text-align:center;padding:1rem;">
            <span class="spinner-border text-primary" role="status"></span> Loading...
        </div>
        <div class="directory-list" id="staff-list">
<?php
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
        echo '<div class="row staff-row align-items-center py-3 mx-0" style="background:'.$bgcolor.'; border-bottom:1px solid #e5e7eb;">';
        echo '<div class="col-auto d-flex align-items-center justify-content-center">'.$avatar.'</div>';
        echo '<div class="col-md-4 col-12">';
        echo '<div class="fw-bold" style="color:#223; font-size:1.15rem;"><a href="profile_view.php?id=' . $user['id'] . '" style="color:#223; text-decoration:underline;">'.htmlspecialchars($user['names']).'</a></div>';
        echo '<div class="text-muted small">'.htmlspecialchars($user['about']).'</div>';
        echo '</div>';
        echo '<div class="col-md-4 col-12 mt-2 mt-md-0">';
        echo '<div><a href="mailto:'.htmlspecialchars($user['email']).'" style="color:#223;">'.htmlspecialchars($user['email']).'</a></div>';
        if (!empty($user['phone'])) echo '<div class="text-muted small">'.htmlspecialchars($user['phone']).'</div>';
        echo '</div>';
        echo '<div class="col-md-3 col-12 mt-2 mt-md-0 text-md-start text-start">'.htmlspecialchars($user['role']).'</div>';
        echo '</div>';
    }
} else {
    echo '<div class="text-center py-5">No staff found.</div>';
}
?>
        </div>
        <!-- Pagination controls -->
        <nav aria-label="Staff directory pagination">
            <ul class="pagination justify-content-center my-4">
                <li class="page-item<?php if ($page <= 1) echo ' disabled'; ?>">
                    <a class="page-link" href="?<?php
                        $q = $_GET; $q['page'] = $page-1; echo http_build_query($q);
                    ?>" tabindex="-1">Previous</a>
                </li>
<?php
$start = max(1, $page - 2);
$end = min($total_pages, $page + 2);
if ($start > 1) {
    $q = $_GET; $q['page'] = 1;
    echo '<li class="page-item"><a class="page-link" href="?'.http_build_query($q).'">1</a></li>';
    if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
}
for ($i = $start; $i <= $end; $i++) {
    $q = $_GET; $q['page'] = $i;
    echo '<li class="page-item'.($i==$page?' active':'').'"><a class="page-link" href="?'.http_build_query($q).'">'.$i.'</a></li>';
}
if ($end < $total_pages) {
    if ($end < $total_pages-1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
    $q = $_GET; $q['page'] = $total_pages;
    echo '<li class="page-item"><a class="page-link" href="?'.http_build_query($q).'">'.$total_pages.'</a></li>';
}
?>
                <li class="page-item<?php if ($page >= $total_pages) echo ' disabled'; ?>">
                    <a class="page-link" href="?<?php
                        $q = $_GET; $q['page'] = $page+1; echo http_build_query($q);
                    ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <!-- A-Z Sidebar OUTSIDE the grid for clarity -->
    <div class="alpha-nav pt-3">
<?php
$letters = range('A', 'Z');
foreach ($letters as $letter) {
    $q = $_GET; $q['alpha'] = $letter; $q['page'] = 1;
    $active = (strtoupper($alpha_filter) === $letter) ? ' style="font-weight:bold;color:#d7262b;"' : '';
    echo '<a href="#" class="alpha-link" data-alpha="'.$letter.'"'.$active.'>'.$letter.'</a>';
}
?>
    </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
    function fetchStaff(page = 1) {
        var role = $('#filter-role').val();
        var name = $('#filter-name').val();
        $('#ajax-loading').show();
        $.get('staff_ajax.php', { role: role, name: name, page: page }, function(data) {
            $('#staff-list').html(data.html);
            $('#result-count').text(data.count);
            $('#ajax-loading').hide();
        }, 'json');
    }
    $('#filter-role').on('change', function() {
        fetchStaff(1);
    });
    $('#filter-name').on('input', function() {
        fetchStaff(1);
    });
    $(document).on('click', '.pagination .page-link', function(e) {
        var href = $(this).attr('href');
        if (href && href !== '#') {
            e.preventDefault();
            var url = new URL(href, window.location.origin);
            var page = url.searchParams.get('page') || 1;
            fetchStaff(page);
        }
    });
    $(document).on('click', '.alpha-link', function(e) {
        e.preventDefault();
        var alpha = $(this).data('alpha');
        $('#filter-name').val(alpha);
        fetchStaff(1);
        $('.alpha-link').css({'font-weight':'','color':''});
        $(this).css({'font-weight':'bold','color':'#d7262b'});
    });
    $('#reset-filters').on('click', function() {
        $('#filter-role').val('');
        $('#filter-name').val('');
        $('.alpha-link').css({'font-weight':'','color':''});
        fetchStaff(1);
    });
});
</script>
</body>
</html>

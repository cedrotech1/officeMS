<?php
// Sample data array (replace with DB query as needed)
$staff = [
    [
        'name' => 'Christopher F. Achua',
        'title' => 'Professor of Marketing/Management',
        'email' => 'cfa2d@uvawise.edu',
        'phone' => '276-328-0276',
        'department' => 'Department of Business & Economics',
        'photo' => 'dashboard/assets/img/av.png',
    ],
    [
        'name' => 'Clara-Cristina Adame de Heu',
        'title' => 'Assistant Professor of Modern Languages',
        'email' => 'ca3m@uvawise.edu',
        'phone' => '276-376-4622',
        'department' => 'Department of Language & Literature',
        'photo' => 'dashboard/assets/img/dsv.png',
    ],
    // ... add more sample staff here ...
];

// Get unique departments
$departments = array_unique(array_column($staff, 'department'));
sort($departments);

// Handle search and filter
$search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
$filter_dept = isset($_GET['department']) ? $_GET['department'] : '';

$filtered_staff = array_filter($staff, function($person) use ($search, $filter_dept) {
    $matches_search = $search === '' || strpos(strtolower($person['name']), $search) !== false || strpos(strtolower($person['title']), $search) !== false || strpos(strtolower($person['department']), $search) !== false;
    $matches_dept = $filter_dept === '' || $person['department'] === $filter_dept;
    return $matches_search && $matches_dept;
});

// Pagination
$per_page = 12;
$total = count($filtered_staff);
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $per_page;
$staff_page = array_slice($filtered_staff, $start, $per_page);
$total_pages = ceil($total / $per_page);

function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $w) {
        if ($w !== '') $initials .= strtoupper($w[0]);
    }
    return $initials;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty & Staff Directory | OfficeMS</title>
    <!-- <link rel="stylesheet" href="./directory.css"> -->
    <link rel="stylesheet" href="dashboard/assets/vendor/bootstrap-icons/bootstrap-icons.css">

</head>
<body>
<header class="directory-header">
    <div class="directory-header-inner">
        <h1 class="directory-title">Faculty & Staff</h1>
        <p class="directory-lead">Find contact information for faculty and staff at OfficeMS.</p>
    </div>
</header>
<main class="directory-main">
    <form class="directory-search-form" method="get">
        <div class="directory-search-row">
            <input type="text" name="search" class="directory-search-input" placeholder="Search by name, title, or department" value="<?php echo htmlspecialchars($search); ?>">
            <select name="department" class="directory-select">
                <option value="">-- Select Unit --</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php if ($filter_dept === $dept) echo 'selected'; ?>><?php echo htmlspecialchars($dept); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="directory-search-btn"><i class="bi bi-search"></i> Search</button>
        </div>
    </form>
    <section class="directory-list">
        <?php if (empty($staff_page)): ?>
            <div class="directory-no-results">No results found.</div>
        <?php else: ?>
            <div class="directory-grid">
            <?php foreach ($staff_page as $person): ?>
                <div class="directory-card">
                    <div class="directory-card-photo">
                    <?php if (!empty($person['photo']) && file_exists($person['photo'])): ?>
                        <img src="<?php echo $person['photo']; ?>" class="directory-photo" alt="<?php echo htmlspecialchars($person['name']); ?>">
                    <?php else: ?>
                        <div class="directory-photo-placeholder">
                            <span><?php echo getInitials($person['name']); ?></span>
                        </div>
                    <?php endif; ?>
                    </div>
                    <div class="directory-card-body">
                        <div class="directory-name"><?php echo htmlspecialchars($person['name']); ?></div>
                        <div class="directory-title-job"><?php echo htmlspecialchars($person['title']); ?></div>
                        <div class="directory-department"><?php echo htmlspecialchars($person['department']); ?></div>
                        <div class="directory-contact"><i class="bi bi-envelope"></i> <a href="mailto:<?php echo htmlspecialchars($person['email']); ?>"><?php echo htmlspecialchars($person['email']); ?></a></div>
                        <div class="directory-contact"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($person['phone']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php if ($total_pages > 1): ?>
    <nav class="directory-pagination">
        <ul>
            <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => max(1, $page-1)])); ?>" class="<?php if ($page <= 1) echo 'disabled'; ?>">Previous</a></li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="<?php if ($i == $page) echo 'active'; ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
            <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => min($total_pages, $page+1)])); ?>" class="<?php if ($page >= $total_pages) echo 'disabled'; ?>">Next</a></li>
        </ul>
    </nav>
    <?php endif; ?>
</main>
<footer class="directory-footer">
    <div>&copy; <?php echo date('Y'); ?> OfficeMS. All rights reserved.</div>
</footer>
</body>
</html> 
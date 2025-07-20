<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'];

?>


<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">

        
        <?php
        if ($role == 'admin') {
            ?>
          
            <li class="nav-item">
                <a class="nav-link collapsed" href="users.php">
                    <i class="bi bi-people"></i>
                    <span>users</span>
                </a>
            </li>

            <?php
        }

        if ($role == 'officer') {
            ?>
            <li class="nav-item">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-grid"></i>
                    <span>Dashboard</span>
                </a>
            </li><!-- End Dashboard Nav -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="user_lists.php">
                    <i class="bi bi-list-ul"></i>
                    <span>staff lists</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" href="upload.php">
                    <i class="bi bi-upload"></i>
                    <span>Upload offices</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" href="office_list.php">
                    <i class="bi bi-building"></i>
                    <span>offices list</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="report.php">
                    <i class="bi bi-bar-chart"></i>
                    <span>Report</span>
                </a>
            </li>

            <?php
        }

        if ($role != 'admin' && $role != 'officer') {
            ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="user_office.php">
                    <i class="bi bi-house-door"></i>
                    <span>My offices</span>
                </a>
            </li>

            <?php
        }
        ?>
        <li class="nav-item">
            <a class="nav-link collapsed" href="notifications.php">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="users-profile.php">
                <i class="bi bi-person-circle"></i>
                <span>Profile</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>logout</span>
            </a>
        </li>



    </ul>
</aside><!-- End Sidebar-->
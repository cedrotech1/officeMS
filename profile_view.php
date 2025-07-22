<?php
include_once 'connection.php';
$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$user_id) {
    echo '<div class="container mt-5"><div class="alert alert-danger">User not specified.</div></div>';
    exit;
}
$user = $connection->query("SELECT * FROM users WHERE id=$user_id AND active=1")->fetch_assoc();
if (!$user) {
    echo '<div class="container mt-5"><div class="alert alert-danger">User not found.</div></div>';
    exit;
}
$bio = $connection->query("SELECT biography FROM user_biography WHERE user_id=$user_id")->fetch_assoc();
$education = $connection->query("SELECT * FROM user_education WHERE user_id=$user_id ORDER BY start_year DESC");
$publications = $connection->query("SELECT * FROM user_publications WHERE user_id=$user_id ORDER BY year DESC");
$patents = $connection->query("SELECT * FROM user_patents WHERE user_id=$user_id ORDER BY date_filed DESC");
$grants = $connection->query("SELECT * FROM user_grants WHERE user_id=$user_id ORDER BY year_awarded DESC");
$awards = $connection->query("SELECT * FROM user_awards WHERE user_id=$user_id ORDER BY year DESC");
$community_engagements = $connection->query("SELECT * FROM user_community_engagements WHERE user_id=$user_id ORDER BY year DESC");
$consultancies = $connection->query("SELECT * FROM user_consultancies WHERE user_id=$user_id ORDER BY year DESC");
$memberships = $connection->query("SELECT * FROM user_memberships WHERE user_id=$user_id ORDER BY start_year DESC");
$certifications = $connection->query("SELECT * FROM user_certifications WHERE user_id=$user_id ORDER BY year_awarded DESC");
$extras = $connection->query("SELECT * FROM user_extras WHERE user_id=$user_id");
$research_areas = $connection->query("SELECT * FROM user_research_areas WHERE user_id=$user_id");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['names']); ?> - Profile</title>
    <link rel="stylesheet" href="dashboard/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboard/assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard/assets/css/style.css">
    <style>
        body {
            background: #232d4b;
        }

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

        .directory-header, .profile-hero {
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

        .profile-hero {
            background: #19213a;
            color: #fff;
            padding: 3rem 0 2rem 0;
            position: relative;
        }

        .profile-hero .profile-img {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            background: #eee;
        }

        .profile-hero .profile-initials {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: #223;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            border: 4px solid #fff;
        }

        .profile-hero .profile-name {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .profile-hero .profile-role {
            font-size: 1.2rem;
            color: #e0e0e0;
            margin-bottom: 0.5rem;
        }

        .profile-hero .profile-contact-card {
            background:#232d4b;
            border-radius: 8px;
            padding: 1.5rem 2rem;
            color: #fff;
            min-width: 260px;
            margin-left: auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .profile-hero .profile-contact-card a {
            color: #fff;
        }

        .profile-hero .profile-contact-card a:hover {
            color: #d7262b;
        }

        .profile-section {
            background: #232d4b;
            border-radius: 10px;
            /* margin-top: 2rem; */
            padding: 1rem 1.5rem;
            /* box-shadow: 0 2px 8px rgba(201, 193, 193, 0.1); */
            color: #fff;
        }

        .profile-section h4 {
            font-weight: 700;
            /* margin-bottom: 1.2rem; */
        }

        .profile-section ul, .profile-section li, .profile-section p, .profile-section a {
            color: #fff;
        }

        .profile-section .card {
            background:#232d4b;
            color: #fff;
            border: 1px solid whitesmoke;
        }

        .profile-section .card-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .profile-section .card-subtitle {
            font-size: 0.98rem;
        }

        .profile-section a {
            color: #fff;
            text-decoration: underline;
        }

        .profile-section a:hover {
            color: #d7262b;
        }

        @media (max-width: 767px) {
            .profile-hero .profile-contact-card {
                margin: 2rem 0 0 0;
                min-width: 0;
            }

            .profile-section {
                padding: 1.2rem 0.7rem;
            }
        }
        ul li{
            list-style: none;
        }
    </style>
</head>

<body>
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
    <div class="profile-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8 d-flex align-items-center gap-4">
                    <?php if ($user['image'] && $user['image'] != 'upload/icon1.png') { ?>
                        <img src="dashboard/<?php echo htmlspecialchars($user['image']); ?>" class="profile-img"
                            alt="<?php echo htmlspecialchars($user['names']); ?>">
                    <?php } else {
                        $names = explode(' ', $user['names']);
                        $initials = '';
                        foreach ($names as $n) {
                            $initials .= strtoupper(mb_substr($n, 0, 1));
                        }
                        echo '<div class="profile-initials">' . $initials . '</div>';
                    } ?>
                    <div>
                        <div class="profile-name"><?php echo htmlspecialchars($user['names']); ?></div>
                        <div class="profile-role"><?php echo htmlspecialchars($user['role']); ?></div>
                        <?php if (!empty($user['department'])) { ?>
                            <div class="profile-label mt-2"><?php echo htmlspecialchars($user['department']); ?></div>
                        <?php } ?>
                        <?php if (!empty($user['office'])) { ?>
                            <div class="profile-label mt-1"><?php echo htmlspecialchars($user['office']); ?></div>
                        <?php } ?>
                    </div>
                </div>
                <div class="col-md-4 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0">
                    <div class="profile-contact-card">
                        <div class="mb-2" style="font-size:0.98rem;letter-spacing:1px;opacity:0.8;">CONTACT INFO</div>
                        <?php if (!empty($user['phone'])) { ?>
                            <div class="mb-1"><a
                                    href="tel:<?php echo htmlspecialchars($user['phone']); ?>"><?php echo htmlspecialchars($user['phone']); ?></a>
                            </div>
                        <?php } ?>
                        <div class="mb-1"><a
                                href="mailto:<?php echo htmlspecialchars($user['email']); ?>"><?php echo htmlspecialchars($user['email']); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <?php if ($bio && !empty($bio['biography'])) { ?>
            <div class="profile-section">
                <h4>Biography</h4>
                <div><?php echo nl2br(htmlspecialchars($bio['biography'])); ?></div>
            </div>
        <?php } ?>
        <?php if ($education && $education->num_rows > 0) { ?>
            <div class="profile-section">
                <h4>Education</h4>
                <ul>
                    <?php while ($ed = $education->fetch_assoc()) { ?>
                        <li>
                            <strong>Degree:</strong> <?php echo htmlspecialchars($ed['degree']); ?><br>
                            <?php if ($ed['field_of_study']) { ?><strong>Field:</strong> <?php echo htmlspecialchars($ed['field_of_study']); ?><br><?php } ?>
                            <strong>Institution:</strong> <?php echo htmlspecialchars($ed['institution']); ?><br>
                            <strong>Years:</strong> <?php echo $ed['start_year']; ?> - <?php echo $ed['end_year']; ?><br>
                            <?php if ($ed['description']) { ?><strong>Description:</strong> <?php echo htmlspecialchars($ed['description']); ?><br><?php } ?>
                        </li><br>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
        <?php if ($publications && $publications->num_rows > 0) { ?>
            <div class="profile-section">
                <h4>Publications</h4>
                <ul>
                    <?php while ($pub = $publications->fetch_assoc()) {
                        $coauthors = $connection->query("SELECT name FROM publication_coauthors WHERE publication_id=" . $pub['id']);
                        $co_str = [];
                        while ($co = $coauthors->fetch_assoc()) $co_str[] = $co['name']; ?>
                        <li>
                            <strong>Title:</strong> <?php echo htmlspecialchars($pub['title']); ?><br>
                            <strong>Year:</strong> <?php echo htmlspecialchars($pub['year']); ?><br>
                            <strong>Publisher:</strong> <?php echo htmlspecialchars($pub['publisher']); ?><br>
                            <strong>Volume:</strong> <?php echo htmlspecialchars($pub['volume']); ?><br>
                            <strong>Issue:</strong> <?php echo htmlspecialchars($pub['issue']); ?><br>
                            <strong>DOI:</strong> <?php echo htmlspecialchars($pub['doi']); ?><br>
                            <strong>URL:</strong> <a href="<?php echo htmlspecialchars($pub['url_link']); ?>" target="_blank"><?php echo htmlspecialchars($pub['url_link']); ?></a><br>
                            <strong>Coauthors:</strong> <?php echo htmlspecialchars(implode(', ', $co_str)); ?><br>
                        </li><br>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
        <?php if ($patents && $patents->num_rows > 0) { ?>
            <div class="profile-section">
                <h4>Patents</h4>
                <ul>
                    <?php while ($pat = $patents->fetch_assoc()) { ?>
                        <li>
                            <strong>Title:</strong> <?php echo htmlspecialchars($pat['title']); ?><br>
                            <strong>Date Filed:</strong> <?php echo htmlspecialchars($pat['date_filed']); ?><br>
                            <strong>Status:</strong> <?php echo htmlspecialchars($pat['status']); ?><br>
                            <strong>Description:</strong> <?php echo htmlspecialchars($pat['description']); ?><br>
                        </li><br>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
        <?php if ($grants && $grants->num_rows > 0) { ?>
            <div class="profile-section">
                <h4>Grants</h4>
                <ul>
                    <?php while ($grant = $grants->fetch_assoc()) { ?>
                        <li>
                            <strong>Title:</strong> <?php echo htmlspecialchars($grant['title']); ?><br>
                            <strong>Sponsor:</strong> <?php echo htmlspecialchars($grant['sponsor']); ?><br>
                            <strong>Amount:</strong> <?php echo number_format($grant['amount'], 2); ?><br>
                            <strong>Year Awarded:</strong> <?php echo htmlspecialchars($grant['year_awarded']); ?><br>
                            <strong>Description:</strong> <?php echo htmlspecialchars($grant['description']); ?><br>
                        </li><br>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
        <?php if ($awards && $awards->num_rows > 0) { ?>
            <div class="profile-section">
                <h4>Honors & Awards</h4>
                <ul>
                    <?php while ($award = $awards->fetch_assoc()) { ?>
                        <li>
                            <strong>Title:</strong> <?php echo htmlspecialchars($award['title']); ?><br>
                            <strong>Organization:</strong> <?php echo htmlspecialchars($award['organization']); ?><br>
                            <strong>Year:</strong> <?php echo htmlspecialchars($award['year']); ?><br>
                            <strong>Description:</strong> <?php echo htmlspecialchars($award['description']); ?><br>
                        </li><br>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
        <?php if ($community_engagements && $community_engagements->num_rows > 0) { ?>
            <div class="profile-section">
                <h4>Community Engagements</h4>
                <ul>
                    <?php while ($comm = $community_engagements->fetch_assoc()) { ?>
                        <li>
                            <strong>Role:</strong> <?php echo htmlspecialchars($comm['role']); ?><br>
                            <strong>Organization:</strong> <?php echo htmlspecialchars($comm['organization']); ?><br>
                            <strong>Year:</strong> <?php echo htmlspecialchars($comm['year']); ?><br>
                            <strong>Description:</strong> <?php echo htmlspecialchars($comm['description']); ?><br>
                        </li><br>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
        <?php if ($consultancies && $consultancies->num_rows > 0) { ?>
            <div class="profile-section">
                <h4>Consultancies</h4>
                <ul>
                    <?php while ($consultancy = $consultancies->fetch_assoc()) { ?>
                        <li>
                            <strong>Title:</strong> <?php echo htmlspecialchars($consultancy['title']); ?><br>
                            <strong>Organization:</strong> <?php echo htmlspecialchars($consultancy['organization']); ?><br>
                            <strong>Year:</strong> <?php echo htmlspecialchars($consultancy['year']); ?><br>
                            <strong>Description:</strong> <?php echo htmlspecialchars($consultancy['description']); ?><br>
                        </li><br>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
        <?php if ($memberships && $memberships->num_rows > 0) { ?>
            <div class="profile-section">
                <h4>Memberships</h4>
                <ul>
                    <?php while ($membership = $memberships->fetch_assoc()) { ?>
                        <li>
                            <strong>Organization:</strong> <?php echo htmlspecialchars($membership['organization']); ?><br>
                            <strong>Role:</strong> <?php echo htmlspecialchars($membership['role']); ?><br>
                            <strong>Start Year:</strong> <?php echo htmlspecialchars($membership['start_year']); ?><br>
                            <strong>End Year:</strong> <?php echo htmlspecialchars($membership['end_year']); ?><br>
                        </li><br>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
        <?php if ($certifications && $certifications->num_rows > 0) { ?>
            <div class="profile-section">
                <h4>Certifications</h4>
                <ul>
                    <?php while ($certification = $certifications->fetch_assoc()) { ?>
                        <li>
                            <strong>Title:</strong> <?php echo htmlspecialchars($certification['title']); ?><br>
                            <strong>Provider:</strong> <?php echo htmlspecialchars($certification['provider']); ?><br>
                            <strong>Year Awarded:</strong> <?php echo htmlspecialchars($certification['year_awarded']); ?><br>
                            <strong>Certificate URL:</strong> <a href="<?php echo htmlspecialchars($certification['certificate_url']); ?>" target="_blank">View</a><br>
                        </li><br>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
        <?php if ($extras && $extras->num_rows > 0) { $extra = $extras->fetch_assoc(); ?>
            <div class="profile-section">
                <h4>Extras</h4>
                <ul>
                    <?php if (!empty($extra['languages'])) echo '<li><strong>Languages:</strong> ' . htmlspecialchars($extra['languages']) . '</li><br>';
                    if (!empty($extra['social_links'])) echo '<li><strong>Social Links:</strong> ' . nl2br(htmlspecialchars($extra['social_links'])) . '</li><br>';
                    if (!empty($extra['personal_website'])) echo '<li><strong>Personal Website:</strong> <a href="' . htmlspecialchars($extra['personal_website']) . '" target="_blank">' . htmlspecialchars($extra['personal_website']) . '</a></li><br>'; ?>
                </ul>
            </div>
        <?php } ?>
        <?php if ($research_areas && $research_areas->num_rows > 0) { ?>
            <div class="profile-section">
                <h4>Research Areas</h4>
                <ul>
                    <?php while ($research_area = $research_areas->fetch_assoc()) { ?>
                        <li><strong>Keyword:</strong> <?php echo htmlspecialchars($research_area['keyword']); ?></li><br>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
    </div>
</body>

</html>
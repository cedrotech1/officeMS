<?php
include_once '../connection.php';
session_start();
$user_id = $_SESSION['id'];
if (!$user_id) { echo 'User not found.'; exit; }
$user = $connection->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
if (!$user) { echo 'User not found.'; exit; }

// 1. Handle Biography Add/Edit
$bio_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bio_action'])) {
    $bio_text = trim($_POST['biography']);
    $exists = $connection->query("SELECT id FROM user_biography WHERE user_id=$user_id")->fetch_assoc();
    if ($exists) {
        $stmt = $connection->prepare("UPDATE user_biography SET biography=? WHERE user_id=?");
        $stmt->bind_param('si', $bio_text, $user_id);
        if ($stmt->execute()) $bio_msg = '<div class="alert alert-success">Biography updated.</div>';
        else $bio_msg = '<div class="alert alert-danger">Error updating biography.</div>';
    } else {
        $stmt = $connection->prepare("INSERT INTO user_biography (user_id, biography) VALUES (?, ?)");
        $stmt->bind_param('is', $user_id, $bio_text);
        if ($stmt->execute()) $bio_msg = '<div class="alert alert-success">Biography added.</div>';
        else $bio_msg = '<div class="alert alert-danger">Error adding biography.</div>';
    }
}
$bio = $connection->query("SELECT biography FROM user_biography WHERE user_id=$user_id")->fetch_assoc();

// Handle Education Add/Edit/Delete
$edu_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edu_action'])) {
    if ($_POST['edu_action'] === 'add') {
        $stmt = $connection->prepare("INSERT INTO user_education (user_id, degree, institution, field_of_study, start_year, end_year, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssiss', $user_id, $_POST['degree'], $_POST['institution'], $_POST['field_of_study'], $_POST['start_year'], $_POST['end_year'], $_POST['description']);
        if ($stmt->execute()) $edu_msg = '<div class="alert alert-success">Education added.</div>';
        else $edu_msg = '<div class="alert alert-danger">Error adding education.</div>';
    } elseif ($_POST['edu_action'] === 'edit' && isset($_POST['edu_id'])) {
        $stmt = $connection->prepare("UPDATE user_education SET degree=?, institution=?, field_of_study=?, start_year=?, end_year=?, description=? WHERE id=? AND user_id=?");
        $stmt->bind_param('sssissii', $_POST['degree'], $_POST['institution'], $_POST['field_of_study'], $_POST['start_year'], $_POST['end_year'], $_POST['description'], $_POST['edu_id'], $user_id);
        if ($stmt->execute()) $edu_msg = '<div class="alert alert-success">Education updated.</div>';
        else $edu_msg = '<div class="alert alert-danger">Error updating education.</div>';
    } elseif ($_POST['edu_action'] === 'delete' && isset($_POST['edu_id'])) {
        $stmt = $connection->prepare("DELETE FROM user_education WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $_POST['edu_id'], $user_id);
        if ($stmt->execute()) $edu_msg = '<div class="alert alert-success">Education deleted.</div>';
        else $edu_msg = '<div class="alert alert-danger">Error deleting education.</div>';
    }
}
$education = $connection->query("SELECT * FROM user_education WHERE user_id=$user_id ORDER BY start_year DESC");

// 2. Publications CRUD
$pub_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pub_action'])) {
    if ($_POST['pub_action'] === 'add') {
        $stmt = $connection->prepare("INSERT INTO user_publications (user_id, title, year, volume, issue, doi, url_link, publisher) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isisssss', $user_id, $_POST['title'], $_POST['year'], $_POST['volume'], $_POST['issue'], $_POST['doi'], $_POST['url_link'], $_POST['publisher']);
        if ($stmt->execute()) {
            $pub_id = $stmt->insert_id;
            if (!empty($_POST['coauthors'])) {
                foreach (explode(',', $_POST['coauthors']) as $co) {
                    $co = trim($co);
                    if ($co) $connection->query("INSERT INTO publication_coauthors (publication_id, name) VALUES ($pub_id, '".$connection->real_escape_string($co)."')");
                }
            }
            $pub_msg = '<div class="alert alert-success">Publication added.</div>';
        } else $pub_msg = '<div class="alert alert-danger">Error adding publication.</div>';
    } elseif ($_POST['pub_action'] === 'edit' && isset($_POST['pub_id'])) {
        $stmt = $connection->prepare("UPDATE user_publications SET title=?, year=?, volume=?, issue=?, doi=?, url_link=?, publisher=? WHERE id=? AND user_id=?");
        $stmt->bind_param('sisssssii', $_POST['title'], $_POST['year'], $_POST['volume'], $_POST['issue'], $_POST['doi'], $_POST['url_link'], $_POST['publisher'], $_POST['pub_id'], $user_id);
        if ($stmt->execute()) {
            $connection->query("DELETE FROM publication_coauthors WHERE publication_id=".(int)$_POST['pub_id']);
            if (!empty($_POST['coauthors'])) {
                foreach (explode(',', $_POST['coauthors']) as $co) {
                    $co = trim($co);
                    if ($co) $connection->query("INSERT INTO publication_coauthors (publication_id, name) VALUES (".(int)$_POST['pub_id'].", '".$connection->real_escape_string($co)."')");
                }
            }
            $pub_msg = '<div class="alert alert-success">Publication updated.</div>';
        } else $pub_msg = '<div class="alert alert-danger">Error updating publication.</div>';
    } elseif ($_POST['pub_action'] === 'delete' && isset($_POST['pub_id'])) {
        $connection->query("DELETE FROM publication_coauthors WHERE publication_id=".(int)$_POST['pub_id']);
        $stmt = $connection->prepare("DELETE FROM user_publications WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $_POST['pub_id'], $user_id);
        if ($stmt->execute()) $pub_msg = '<div class="alert alert-success">Publication deleted.</div>';
        else $pub_msg = '<div class="alert alert-danger">Error deleting publication.</div>';
    }
}
$publications = $connection->query("SELECT * FROM user_publications WHERE user_id=$user_id ORDER BY year DESC");

// 3. Patents CRUD
$pat_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pat_action'])) {
    if ($_POST['pat_action'] === 'add') {
        $stmt = $connection->prepare("INSERT INTO user_patents (user_id, title, description, date_filed, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issss', $user_id, $_POST['title'], $_POST['description'], $_POST['date_filed'], $_POST['status']);
        if ($stmt->execute()) $pat_msg = '<div class="alert alert-success">Patent added.</div>';
        else $pat_msg = '<div class="alert alert-danger">Error adding patent.</div>';
    } elseif ($_POST['pat_action'] === 'edit' && isset($_POST['pat_id'])) {
        $stmt = $connection->prepare("UPDATE user_patents SET title=?, description=?, date_filed=?, status=? WHERE id=? AND user_id=?");
        $stmt->bind_param('ssssii', $_POST['title'], $_POST['description'], $_POST['date_filed'], $_POST['status'], $_POST['pat_id'], $user_id);
        if ($stmt->execute()) $pat_msg = '<div class="alert alert-success">Patent updated.</div>';
        else $pat_msg = '<div class="alert alert-danger">Error updating patent.</div>';
    } elseif ($_POST['pat_action'] === 'delete' && isset($_POST['pat_id'])) {
        $stmt = $connection->prepare("DELETE FROM user_patents WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $_POST['pat_id'], $user_id);
        if ($stmt->execute()) $pat_msg = '<div class="alert alert-success">Patent deleted.</div>';
        else $pat_msg = '<div class="alert alert-danger">Error deleting patent.</div>';
    }
}
$patents = $connection->query("SELECT * FROM user_patents WHERE user_id=$user_id ORDER BY date_filed DESC");

// 4. Grants CRUD
$grant_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grant_action'])) {
    if ($_POST['grant_action'] === 'add') {
        $stmt = $connection->prepare("INSERT INTO user_grants (user_id, title, sponsor, amount, year_awarded, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issdss', $user_id, $_POST['title'], $_POST['sponsor'], $_POST['amount'], $_POST['year_awarded'], $_POST['description']);
        if ($stmt->execute()) $grant_msg = '<div class="alert alert-success">Grant added.</div>';
        else $grant_msg = '<div class="alert alert-danger">Error adding grant.</div>';
    } elseif ($_POST['grant_action'] === 'edit' && isset($_POST['grant_id'])) {
        $stmt = $connection->prepare("UPDATE user_grants SET title=?, sponsor=?, amount=?, year_awarded=?, description=? WHERE id=? AND user_id=?");
        $stmt->bind_param('ssdssii', $_POST['title'], $_POST['sponsor'], $_POST['amount'], $_POST['year_awarded'], $_POST['description'], $_POST['grant_id'], $user_id);
        if ($stmt->execute()) $grant_msg = '<div class="alert alert-success">Grant updated.</div>';
        else $grant_msg = '<div class="alert alert-danger">Error updating grant.</div>';
    } elseif ($_POST['grant_action'] === 'delete' && isset($_POST['grant_id'])) {
        $stmt = $connection->prepare("DELETE FROM user_grants WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $_POST['grant_id'], $user_id);
        if ($stmt->execute()) $grant_msg = '<div class="alert alert-success">Grant deleted.</div>';
        else $grant_msg = '<div class="alert alert-danger">Error deleting grant.</div>';
    }
}
$grants = $connection->query("SELECT * FROM user_grants WHERE user_id=$user_id ORDER BY year_awarded DESC");

// 5. Awards CRUD
$award_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['award_action'])) {
    if ($_POST['award_action'] === 'add') {
        $stmt = $connection->prepare("INSERT INTO user_awards (user_id, title, organization, year, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issis', $user_id, $_POST['title'], $_POST['organization'], $_POST['year'], $_POST['description']);
        if ($stmt->execute()) $award_msg = '<div class="alert alert-success">Award added.</div>';
        else $award_msg = '<div class="alert alert-danger">Error adding award.</div>';
    } elseif ($_POST['award_action'] === 'edit' && isset($_POST['award_id'])) {
        $stmt = $connection->prepare("UPDATE user_awards SET title=?, organization=?, year=?, description=? WHERE id=? AND user_id=?");
        $stmt->bind_param('ssisii', $_POST['title'], $_POST['organization'], $_POST['year'], $_POST['description'], $_POST['award_id'], $user_id);
        if ($stmt->execute()) $award_msg = '<div class="alert alert-success">Award updated.</div>';
        else $award_msg = '<div class="alert alert-danger">Error updating award.</div>';
    } elseif ($_POST['award_action'] === 'delete' && isset($_POST['award_id'])) {
        $stmt = $connection->prepare("DELETE FROM user_awards WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $_POST['award_id'], $user_id);
        if ($stmt->execute()) $award_msg = '<div class="alert alert-success">Award deleted.</div>';
        else $award_msg = '<div class="alert alert-danger">Error deleting award.</div>';
    }
}
$awards = $connection->query("SELECT * FROM user_awards WHERE user_id=$user_id ORDER BY year DESC");

// 6. Community Engagements CRUD
$comm_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comm_action'])) {
    if ($_POST['comm_action'] === 'add') {
        $stmt = $connection->prepare("INSERT INTO user_community_engagements (user_id, role, organization, year, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issis', $user_id, $_POST['role'], $_POST['organization'], $_POST['year'], $_POST['description']);
        if ($stmt->execute()) $comm_msg = '<div class="alert alert-success">Community engagement added.</div>';
        else $comm_msg = '<div class="alert alert-danger">Error adding community engagement.</div>';
    } elseif ($_POST['comm_action'] === 'edit' && isset($_POST['comm_id'])) {
        $stmt = $connection->prepare("UPDATE user_community_engagements SET role=?, organization=?, year=?, description=? WHERE id=? AND user_id=?");
        $stmt->bind_param('ssisii', $_POST['role'], $_POST['organization'], $_POST['year'], $_POST['description'], $_POST['comm_id'], $user_id);
        if ($stmt->execute()) $comm_msg = '<div class="alert alert-success">Community engagement updated.</div>';
        else $comm_msg = '<div class="alert alert-danger">Error updating community engagement.</div>';
    } elseif ($_POST['comm_action'] === 'delete' && isset($_POST['comm_id'])) {
        $stmt = $connection->prepare("DELETE FROM user_community_engagements WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $_POST['comm_id'], $user_id);
        if ($stmt->execute()) $comm_msg = '<div class="alert alert-success">Community engagement deleted.</div>';
        else $comm_msg = '<div class="alert alert-danger">Error deleting community engagement.</div>';
    }
}
$community_engagements = $connection->query("SELECT * FROM user_community_engagements WHERE user_id=$user_id ORDER BY year DESC");

// 7. Consultancies CRUD
$consultancy_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consultancy_action'])) {
    if ($_POST['consultancy_action'] === 'add') {
        $stmt = $connection->prepare("INSERT INTO user_consultancies (user_id, title, organization, year, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issis', $user_id, $_POST['title'], $_POST['organization'], $_POST['year'], $_POST['description']);
        if ($stmt->execute()) $consultancy_msg = '<div class="alert alert-success">Consultancy added.</div>';
        else $consultancy_msg = '<div class="alert alert-danger">Error adding consultancy.</div>';
    } elseif ($_POST['consultancy_action'] === 'edit' && isset($_POST['consultancy_id'])) {
        $stmt = $connection->prepare("UPDATE user_consultancies SET title=?, organization=?, year=?, description=? WHERE id=? AND user_id=?");
        $stmt->bind_param('ssisii', $_POST['title'], $_POST['organization'], $_POST['year'], $_POST['description'], $_POST['consultancy_id'], $user_id);
        if ($stmt->execute()) $consultancy_msg = '<div class="alert alert-success">Consultancy updated.</div>';
        else $consultancy_msg = '<div class="alert alert-danger">Error updating consultancy.</div>';
    } elseif ($_POST['consultancy_action'] === 'delete' && isset($_POST['consultancy_id'])) {
        $stmt = $connection->prepare("DELETE FROM user_consultancies WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $_POST['consultancy_id'], $user_id);
        if ($stmt->execute()) $consultancy_msg = '<div class="alert alert-success">Consultancy deleted.</div>';
        else $consultancy_msg = '<div class="alert alert-danger">Error deleting consultancy.</div>';
    }
}
$consultancies = $connection->query("SELECT * FROM user_consultancies WHERE user_id=$user_id ORDER BY year DESC");

// 8. Memberships CRUD
$membership_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['membership_action'])) {
    if ($_POST['membership_action'] === 'add') {
        $stmt = $connection->prepare("INSERT INTO user_memberships (user_id, organization, role, start_year, end_year) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issii', $user_id, $_POST['organization'], $_POST['role'], $_POST['start_year'], $_POST['end_year']);
        if ($stmt->execute()) $membership_msg = '<div class="alert alert-success">Membership added.</div>';
        else $membership_msg = '<div class="alert alert-danger">Error adding membership.</div>';
    } elseif ($_POST['membership_action'] === 'edit' && isset($_POST['membership_id'])) {
        $stmt = $connection->prepare("UPDATE user_memberships SET organization=?, role=?, start_year=?, end_year=? WHERE id=? AND user_id=?");
        $stmt->bind_param('ssiiii', $_POST['organization'], $_POST['role'], $_POST['start_year'], $_POST['end_year'], $_POST['membership_id'], $user_id);
        if ($stmt->execute()) $membership_msg = '<div class="alert alert-success">Membership updated.</div>';
        else $membership_msg = '<div class="alert alert-danger">Error updating membership.</div>';
    } elseif ($_POST['membership_action'] === 'delete' && isset($_POST['membership_id'])) {
        $stmt = $connection->prepare("DELETE FROM user_memberships WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $_POST['membership_id'], $user_id);
        if ($stmt->execute()) $membership_msg = '<div class="alert alert-success">Membership deleted.</div>';
        else $membership_msg = '<div class="alert alert-danger">Error deleting membership.</div>';
    }
}
$memberships = $connection->query("SELECT * FROM user_memberships WHERE user_id=$user_id ORDER BY start_year DESC");

// 9. Certifications CRUD
$certification_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['certification_action'])) {
    if ($_POST['certification_action'] === 'add') {
        $stmt = $connection->prepare("INSERT INTO user_certifications (user_id, title, provider, year_awarded, certificate_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issis', $user_id, $_POST['title'], $_POST['provider'], $_POST['year_awarded'], $_POST['certificate_url']);
        if ($stmt->execute()) $certification_msg = '<div class="alert alert-success">Certification added.</div>';
        else $certification_msg = '<div class="alert alert-danger">Error adding certification.</div>';
    } elseif ($_POST['certification_action'] === 'edit' && isset($_POST['certification_id'])) {
        $stmt = $connection->prepare("UPDATE user_certifications SET title=?, provider=?, year_awarded=?, certificate_url=? WHERE id=? AND user_id=?");
        $stmt->bind_param('ssissi', $_POST['title'], $_POST['provider'], $_POST['year_awarded'], $_POST['certificate_url'], $_POST['certification_id'], $user_id);
        if ($stmt->execute()) $certification_msg = '<div class="alert alert-success">Certification updated.</div>';
        else $certification_msg = '<div class="alert alert-danger">Error updating certification.</div>';
    } elseif ($_POST['certification_action'] === 'delete' && isset($_POST['certification_id'])) {
        $stmt = $connection->prepare("DELETE FROM user_certifications WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $_POST['certification_id'], $user_id);
        if ($stmt->execute()) $certification_msg = '<div class="alert alert-success">Certification deleted.</div>';
        else $certification_msg = '<div class="alert alert-danger">Error deleting certification.</div>';
    }
}
$certifications = $connection->query("SELECT * FROM user_certifications WHERE user_id=$user_id ORDER BY year_awarded DESC");

// 10. Extras CRUD
$extra_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['extra_action'])) {
    if ($_POST['extra_action'] === 'add') {
        $stmt = $connection->prepare("INSERT INTO user_extras (user_id, languages, social_links, personal_website) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isss', $user_id, $_POST['languages'], $_POST['social_links'], $_POST['personal_website']);
        if ($stmt->execute()) $extra_msg = '<div class="alert alert-success">Extra added.</div>';
        else $extra_msg = '<div class="alert alert-danger">Error adding extra.</div>';
    } elseif ($_POST['extra_action'] === 'edit' && isset($_POST['extra_id'])) {
        $stmt = $connection->prepare("UPDATE user_extras SET languages=?, social_links=?, personal_website=? WHERE id=? AND user_id=?");
        $stmt->bind_param('sssii', $_POST['languages'], $_POST['social_links'], $_POST['personal_website'], $_POST['extra_id'], $user_id);
        if ($stmt->execute()) $extra_msg = '<div class="alert alert-success">Extra updated.</div>';
        else $extra_msg = '<div class="alert alert-danger">Error updating extra.</div>';
    } elseif ($_POST['extra_action'] === 'delete' && isset($_POST['extra_id'])) {
        $stmt = $connection->prepare("DELETE FROM user_extras WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $_POST['extra_id'], $user_id);
        if ($stmt->execute()) $extra_msg = '<div class="alert alert-success">Extra deleted.</div>';
        else $extra_msg = '<div class="alert alert-danger">Error deleting extra.</div>';
    }
}
$extras = $connection->query("SELECT * FROM user_extras WHERE user_id=$user_id");

// 11. Research Areas CRUD
$research_area_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['research_area_action'])) {
    if ($_POST['research_area_action'] === 'add') {
        $stmt = $connection->prepare("INSERT INTO user_research_areas (user_id, keyword) VALUES (?, ?)");
        $stmt->bind_param('is', $user_id, $_POST['keyword']);
        if ($stmt->execute()) $research_area_msg = '<div class="alert alert-success">Research area added.</div>';
        else $research_area_msg = '<div class="alert alert-danger">Error adding research area.</div>';
    } elseif ($_POST['research_area_action'] === 'edit' && isset($_POST['research_area_id'])) {
        $stmt = $connection->prepare("UPDATE user_research_areas SET keyword=? WHERE id=? AND user_id=?");
        $stmt->bind_param('sii', $_POST['keyword'], $_POST['research_area_id'], $user_id);
        if ($stmt->execute()) $research_area_msg = '<div class="alert alert-success">Research area updated.</div>';
        else $research_area_msg = '<div class="alert alert-danger">Error updating research area.</div>';
    } elseif ($_POST['research_area_action'] === 'delete' && isset($_POST['research_area_id'])) {
        $stmt = $connection->prepare("DELETE FROM user_research_areas WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $_POST['research_area_id'], $user_id);
        if ($stmt->execute()) $research_area_msg = '<div class="alert alert-success">Research area deleted.</div>';
        else $research_area_msg = '<div class="alert alert-danger">Error deleting research area.</div>';
    }
}
$research_areas = $connection->query("SELECT * FROM user_research_areas WHERE user_id=$user_id");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile - <?php echo htmlspecialchars($user['names']); ?></title>
    <link rel="stylesheet" href="../dashboard/assets/vendor/bootstrap/css/bootstrap.min.css">
    <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>UR-HUYE</title>
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
  <!-- jQuery CDN (must be before any script using $) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .profile-section { margin-bottom: 2.5rem; }
        .profile-section h3 { border-bottom: 2px solid #eee; padding-bottom: 0.5rem; margin-bottom: 1.2rem; }
        .profile-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #e5e7eb; }
        .profile-header { background: #f7f8fa; padding: 2rem 0 1.5rem 0; margin-bottom: 2rem; }
    </style>
</head>
<body>
<?php
include ("./includes/header.php");
include ("./includes/menu.php");
?>
<main id="main" class="main">
  <div class="container-fluid">
    <div class="row">
      <!-- Local profile sidebar -->
      <div class="col-md-3 col-12 mb-3 mb-md-0 card pt-2">
        <ul class="nav nav-pills flex-column" id="profile-section-nav">
          <li class="nav-item mb-1"><a class="nav-link active" href="#section-biography" data-section="biography">Biography</a></li>
          <li class="nav-item mb-1"><a class="nav-link" href="#section-education" data-section="education">Education</a></li>
          <li class="nav-item mb-1"><a class="nav-link" href="#section-publications" data-section="publications">Publications</a></li>
          <li class="nav-item mb-1"><a class="nav-link" href="#section-patents" data-section="patents">Patents</a></li>
          <li class="nav-item mb-1"><a class="nav-link" href="#section-grants" data-section="grants">Grants</a></li>
          <li class="nav-item mb-1"><a class="nav-link" href="#section-awards" data-section="awards">Awards</a></li>
          <li class="nav-item mb-1"><a class="nav-link" href="#section-community" data-section="community">Community Engagements</a></li>
          <li class="nav-item mb-1"><a class="nav-link" href="#section-consultancies" data-section="consultancies">Consultancies</a></li>
          <li class="nav-item mb-1"><a class="nav-link" href="#section-memberships" data-section="memberships">Memberships</a></li>
          <li class="nav-item mb-1"><a class="nav-link" href="#section-certifications" data-section="certifications">Certifications</a></li>
          <li class="nav-item mb-1"><a class="nav-link" href="#section-extras" data-section="extras">Extras</a></li>
          <li class="nav-item mb-1"><a class="nav-link" href="#section-research" data-section="research">Research Areas</a></li>
        </ul>
      </div>
      <!-- Profile content -->
      <div class="col-md-9 col-12">
        <!-- <div class="profile-header text-center mb-4">
          <?php if ($user['image'] && $user['image'] != 'upload/icon1.png') { ?>
              <img src="../dashboard/<?php echo htmlspecialchars($user['image']); ?>" class="profile-avatar mb-2" alt="<?php echo htmlspecialchars($user['names']); ?>">
          <?php } else { 
              $names = explode(' ', $user['names']);
              $initials = '';
              foreach ($names as $n) { $initials .= strtoupper(mb_substr($n,0,1)); }
          ?>
              <div class="profile-avatar mb-2 d-flex align-items-center justify-content-center bg-secondary text-white" style="font-size:2rem;"> <?php echo $initials; ?> </div>
          <?php } ?>
          <h2><?php echo htmlspecialchars($user['names']); ?></h2>
          <div class="mb-2 text-muted"><?php echo htmlspecialchars($user['role']); ?></div>
          <div class="mb-2"><a href="mailto:<?php echo htmlspecialchars($user['email']); ?>"><?php echo htmlspecialchars($user['email']); ?></a></div>
          <?php if (!empty($user['phone'])) echo '<div class="mb-2">'.htmlspecialchars($user['phone']).'</div>'; ?>
        </div> -->
        <!-- Profile Sections -->
        <div id="section-biography" class="profile-section"> <!-- Biography -->
          <h3>Biography <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#editBioModal">Edit</button></h3>
          <?php echo $bio_msg; ?>
          <div><?php echo $bio && $bio['biography'] ? nl2br(htmlspecialchars($bio['biography'])) : '<span class="text-muted">No biography yet.</span>'; ?></div>
          <!-- Edit Modal -->
          <div class="modal fade" id="editBioModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
              <form method="post">
              <div class="modal-header"><h5 class="modal-title">Edit Biography</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                  <input type="hidden" name="bio_action" value="edit">
                  <div class="mb-2"><label>Biography</label><textarea name="biography" class="form-control" rows="6"><?php echo $bio ? htmlspecialchars($bio['biography']) : ''; ?></textarea></div>
              </div>
              <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
              </form>
            </div></div>
          </div>
        </div>
        <div id="section-education" class="profile-section d-none"> <!-- Education -->
          <h3>Education <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addEduModal">Add</button></h3>
          <?php echo $edu_msg; ?>
          <div class="row g-3">
              <?php while ($ed = $education->fetch_assoc()) { ?>
              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($ed['degree']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($ed['institution']); ?> (<?php echo $ed['start_year'].' - '.$ed['end_year']; ?>)</h6>
                    <p class="card-text"><strong>Field:</strong> <?php echo htmlspecialchars($ed['field_of_study']); ?></p>
                    <p class="card-text"><?php echo htmlspecialchars($ed['description']); ?></p>
                          <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editEduModal<?php echo $ed['id']; ?>">Edit</button>
                          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this education?');">
                              <input type="hidden" name="edu_action" value="delete">
                              <input type="hidden" name="edu_id" value="<?php echo $ed['id']; ?>">
                              <button class="btn btn-sm btn-danger">Delete</button>
                          </form>
                  </div>
                </div>
                  <!-- Edit Modal -->
                  <div class="modal fade" id="editEduModal<?php echo $ed['id']; ?>" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                      <form method="post">
                      <div class="modal-header"><h5 class="modal-title">Edit Education</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                          <input type="hidden" name="edu_action" value="edit">
                          <input type="hidden" name="edu_id" value="<?php echo $ed['id']; ?>">
                          <div class="mb-2"><label>Degree</label><input type="text" name="degree" class="form-control" value="<?php echo htmlspecialchars($ed['degree']); ?>" required></div>
                          <div class="mb-2"><label>Field of Study</label><input type="text" name="field_of_study" class="form-control" value="<?php echo htmlspecialchars($ed['field_of_study']); ?>"></div>
                          <div class="mb-2"><label>Institution</label><input type="text" name="institution" class="form-control" value="<?php echo htmlspecialchars($ed['institution']); ?>"></div>
                          <div class="mb-2"><label>Start Year</label><input type="number" name="start_year" class="form-control" value="<?php echo $ed['start_year']; ?>" required></div>
                          <div class="mb-2"><label>End Year</label><input type="number" name="end_year" class="form-control" value="<?php echo $ed['end_year']; ?>" required></div>
                          <div class="mb-2"><label>Description</label><textarea name="description" class="form-control"><?php echo htmlspecialchars($ed['description']); ?></textarea></div>
                      </div>
                      <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
                      </form>
                    </div></div>
                </div>
                  </div>
              <?php } ?>
          </div>
          <!-- Add Modal -->
          <div class="modal fade" id="addEduModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
              <form method="post">
              <div class="modal-header"><h5 class="modal-title">Add Education</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                  <input type="hidden" name="edu_action" value="add">
                  <div class="mb-2"><label>Degree</label><input type="text" name="degree" class="form-control" required></div>
                  <div class="mb-2"><label>Field of Study</label><input type="text" name="field_of_study" class="form-control"></div>
                  <div class="mb-2"><label>Institution</label><input type="text" name="institution" class="form-control"></div>
                  <div class="mb-2"><label>Start Year</label><input type="number" name="start_year" class="form-control" required></div>
                  <div class="mb-2"><label>End Year</label><input type="number" name="end_year" class="form-control" required></div>
                  <div class="mb-2"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
              </div>
              <div class="modal-footer"><button type="submit" class="btn btn-primary">Add</button></div>
              </form>
            </div></div>
          </div>
        </div>
        <div id="section-publications" class="profile-section d-none"> <!-- Publications -->
          <h3>Publications <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addPubModal">Add</button></h3>
          <?php echo $pub_msg; ?>
          <div class="row g-3">
              <?php while ($pub = $publications->fetch_assoc()) { 
                  $coauthors = $connection->query("SELECT name FROM publication_coauthors WHERE publication_id=".$pub['id']);
                  $co_str = [];
                  while ($co = $coauthors->fetch_assoc()) $co_str[] = $co['name'];
              ?>
              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($pub['publisher']); ?> (<?php echo htmlspecialchars($pub['year']); ?>)</h6>
                    <p class="card-text"><strong>Volume:</strong> <?php echo htmlspecialchars($pub['volume']); ?> <strong>Issue:</strong> <?php echo htmlspecialchars($pub['issue']); ?></p>
                    <p class="card-text"><strong>DOI:</strong> <?php echo htmlspecialchars($pub['doi']); ?></p>
                    <p class="card-text"><strong>URL:</strong> <a href="<?php echo htmlspecialchars($pub['url_link']); ?>" target="_blank"><?php echo htmlspecialchars($pub['url_link']); ?></a></p>
                    <p class="card-text"><strong>Coauthors:</strong> <?php echo htmlspecialchars(implode(', ', $co_str)); ?></p>
                          <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editPubModal<?php echo $pub['id']; ?>">Edit</button>
                          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this publication?');">
                              <input type="hidden" name="pub_action" value="delete">
                              <input type="hidden" name="pub_id" value="<?php echo $pub['id']; ?>">
                              <button class="btn btn-sm btn-danger">Delete</button>
                          </form>
                  </div>
                </div>
                  <!-- Edit Modal -->
                  <div class="modal fade" id="editPubModal<?php echo $pub['id']; ?>" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                      <form method="post">
                      <div class="modal-header"><h5 class="modal-title">Edit Publication</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                          <input type="hidden" name="pub_action" value="edit">
                          <input type="hidden" name="pub_id" value="<?php echo $pub['id']; ?>">
                          <div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($pub['title']); ?>" required></div>
                          <div class="mb-2"><label>Year</label><input type="number" name="year" class="form-control" value="<?php echo $pub['year']; ?>" required></div>
                          <div class="mb-2"><label>Publisher</label><input type="text" name="publisher" class="form-control" value="<?php echo htmlspecialchars($pub['publisher']); ?>"></div>
                          <div class="mb-2"><label>Volume</label><input type="text" name="volume" class="form-control" value="<?php echo htmlspecialchars($pub['volume']); ?>"></div>
                          <div class="mb-2"><label>Issue</label><input type="text" name="issue" class="form-control" value="<?php echo htmlspecialchars($pub['issue']); ?>"></div>
                          <div class="mb-2"><label>DOI</label><input type="text" name="doi" class="form-control" value="<?php echo htmlspecialchars($pub['doi']); ?>"></div>
                          <div class="mb-2"><label>URL</label><input type="text" name="url_link" class="form-control" value="<?php echo htmlspecialchars($pub['url_link']); ?>"></div>
                          <div class="mb-2"><label>Coauthors (comma separated)</label><input type="text" name="coauthors" class="form-control" value="<?php echo htmlspecialchars(implode(', ', $co_str)); ?>"></div>
                      </div>
                      <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
                      </form>
                    </div></div>
                </div>
                  </div>
              <?php } ?>
          </div>
          <!-- Add Modal -->
          <div class="modal fade" id="addPubModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
              <form method="post">
              <div class="modal-header"><h5 class="modal-title">Add Publication</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                  <input type="hidden" name="pub_action" value="add">
                  <div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                  <div class="mb-2"><label>Year</label><input type="number" name="year" class="form-control" required></div>
                  <div class="mb-2"><label>Publisher</label><input type="text" name="publisher" class="form-control"></div>
                  <div class="mb-2"><label>Volume</label><input type="text" name="volume" class="form-control"></div>
                  <div class="mb-2"><label>Issue</label><input type="text" name="issue" class="form-control"></div>
                  <div class="mb-2"><label>DOI</label><input type="text" name="doi" class="form-control"></div>
                  <div class="mb-2"><label>URL</label><input type="text" name="url_link" class="form-control"></div>
                  <div class="mb-2"><label>Coauthors (comma separated)</label><input type="text" name="coauthors" class="form-control"></div>
              </div>
              <div class="modal-footer"><button type="submit" class="btn btn-primary">Add</button></div>
              </form>
            </div></div>
          </div>
        </div>
        <div id="section-patents" class="profile-section d-none"> <!-- Patents -->
          <h3>Patents <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addPatModal">Add</button></h3>
          <?php echo $pat_msg; ?>
          <div class="row g-3">
              <?php while ($pat = $patents->fetch_assoc()) { ?>
              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($pat['title']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($pat['date_filed']); ?> | <?php echo htmlspecialchars($pat['status']); ?></h6>
                    <p class="card-text"><?php echo htmlspecialchars($pat['description']); ?></p>
                          <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editPatModal<?php echo $pat['id']; ?>">Edit</button>
                          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this patent?');">
                              <input type="hidden" name="pat_action" value="delete">
                              <input type="hidden" name="pat_id" value="<?php echo $pat['id']; ?>">
                              <button class="btn btn-sm btn-danger">Delete</button>
                          </form>
                  </div>
                </div>
                  <!-- Edit Modal -->
                  <div class="modal fade" id="editPatModal<?php echo $pat['id']; ?>" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                      <form method="post">
                      <div class="modal-header"><h5 class="modal-title">Edit Patent</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                          <input type="hidden" name="pat_action" value="edit">
                          <input type="hidden" name="pat_id" value="<?php echo $pat['id']; ?>">
                          <div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($pat['title']); ?>" required></div>
                          <div class="mb-2"><label>Date Filed</label><input type="date" name="date_filed" class="form-control" value="<?php echo htmlspecialchars($pat['date_filed']); ?>" required></div>
                          <div class="mb-2"><label>Status</label><input type="text" name="status" class="form-control" value="<?php echo htmlspecialchars($pat['status']); ?>"></div>
                          <div class="mb-2"><label>Description</label><textarea name="description" class="form-control"><?php echo htmlspecialchars($pat['description']); ?></textarea></div>
                      </div>
                      <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
                      </form>
                    </div></div>
                </div>
                  </div>
              <?php } ?>
          </div>
          <!-- Add Modal -->
          <div class="modal fade" id="addPatModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
              <form method="post">
              <div class="modal-header"><h5 class="modal-title">Add Patent</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                  <input type="hidden" name="pat_action" value="add">
                  <div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                  <div class="mb-2"><label>Date Filed</label><input type="date" name="date_filed" class="form-control" required></div>
                  <div class="mb-2"><label>Status</label><input type="text" name="status" class="form-control"></div>
                  <div class="mb-2"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
              </div>
              <div class="modal-footer"><button type="submit" class="btn btn-primary">Add</button></div>
              </form>
            </div></div>
          </div>
        </div>
        <div id="section-grants" class="profile-section d-none"> <!-- Grants -->
          <h3>Grants <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addGrantModal">Add</button></h3>
          <?php echo $grant_msg; ?>
          <div class="row g-3">
              <?php while ($grant = $grants->fetch_assoc()) { ?>
              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($grant['title']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($grant['sponsor']); ?> (<?php echo htmlspecialchars($grant['year_awarded']); ?>)</h6>
                    <p class="card-text"><strong>Amount:</strong> <?php echo number_format($grant['amount'], 2); ?></p>
                    <p class="card-text"><?php echo htmlspecialchars($grant['description']); ?></p>
                          <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editGrantModal<?php echo $grant['id']; ?>">Edit</button>
                          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this grant?');">
                              <input type="hidden" name="grant_action" value="delete">
                              <input type="hidden" name="grant_id" value="<?php echo $grant['id']; ?>">
                              <button class="btn btn-sm btn-danger">Delete</button>
                          </form>
                  </div>
                </div>
                  <!-- Edit Modal -->
                  <div class="modal fade" id="editGrantModal<?php echo $grant['id']; ?>" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                      <form method="post">
                      <div class="modal-header"><h5 class="modal-title">Edit Grant</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                          <input type="hidden" name="grant_action" value="edit">
                          <input type="hidden" name="grant_id" value="<?php echo $grant['id']; ?>">
                          <div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($grant['title']); ?>" required></div>
                          <div class="mb-2"><label>Sponsor</label><input type="text" name="sponsor" class="form-control" value="<?php echo htmlspecialchars($grant['sponsor']); ?>"></div>
                          <div class="mb-2"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" value="<?php echo htmlspecialchars($grant['amount']); ?>"></div>
                          <div class="mb-2"><label>Year Awarded</label><input type="number" name="year_awarded" class="form-control" value="<?php echo htmlspecialchars($grant['year_awarded']); ?>" required></div>
                          <div class="mb-2"><label>Description</label><textarea name="description" class="form-control"><?php echo htmlspecialchars($grant['description']); ?></textarea></div>
                      </div>
                      <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
                      </form>
                    </div></div>
                </div>
                  </div>
              <?php } ?>
          </div>
          <!-- Add Modal -->
          <div class="modal fade" id="addGrantModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
              <form method="post">
              <div class="modal-header"><h5 class="modal-title">Add Grant</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                  <input type="hidden" name="grant_action" value="add">
                  <div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                  <div class="mb-2"><label>Sponsor</label><input type="text" name="sponsor" class="form-control"></div>
                  <div class="mb-2"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control"></div>
                  <div class="mb-2"><label>Year Awarded</label><input type="number" name="year_awarded" class="form-control" required></div>
                  <div class="mb-2"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
              </div>
              <div class="modal-footer"><button type="submit" class="btn btn-primary">Add</button></div>
              </form>
            </div></div>
          </div>
        </div>
        <div id="section-awards" class="profile-section d-none"> <!-- Awards -->
          <h3>Awards <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addAwardModal">Add</button></h3>
          <?php echo $award_msg; ?>
          <div class="row g-3">
              <?php while ($award = $awards->fetch_assoc()) { ?>
              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($award['title']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($award['organization']); ?> (<?php echo htmlspecialchars($award['year']); ?>)</h6>
                    <p class="card-text"><?php echo htmlspecialchars($award['description']); ?></p>
                          <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editAwardModal<?php echo $award['id']; ?>">Edit</button>
                          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this award?');">
                              <input type="hidden" name="award_action" value="delete">
                              <input type="hidden" name="award_id" value="<?php echo $award['id']; ?>">
                              <button class="btn btn-sm btn-danger">Delete</button>
                          </form>
                  </div>
                </div>
                  <!-- Edit Modal -->
                  <div class="modal fade" id="editAwardModal<?php echo $award['id']; ?>" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                      <form method="post">
                      <div class="modal-header"><h5 class="modal-title">Edit Award</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                          <input type="hidden" name="award_action" value="edit">
                          <input type="hidden" name="award_id" value="<?php echo $award['id']; ?>">
                          <div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($award['title']); ?>" required></div>
                          <div class="mb-2"><label>Organization</label><input type="text" name="organization" class="form-control" value="<?php echo htmlspecialchars($award['organization']); ?>"></div>
                          <div class="mb-2"><label>Year</label><input type="number" name="year" class="form-control" value="<?php echo htmlspecialchars($award['year']); ?>" required></div>
                          <div class="mb-2"><label>Description</label><textarea name="description" class="form-control"><?php echo htmlspecialchars($award['description']); ?></textarea></div>
                      </div>
                      <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
                      </form>
                    </div></div>
                </div>
                  </div>
              <?php } ?>
          </div>
          <!-- Add Modal -->
          <div class="modal fade" id="addAwardModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
              <form method="post">
              <div class="modal-header"><h5 class="modal-title">Add Award</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                  <input type="hidden" name="award_action" value="add">
                  <div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                  <div class="mb-2"><label>Organization</label><input type="text" name="organization" class="form-control"></div>
                  <div class="mb-2"><label>Year</label><input type="number" name="year" class="form-control" required></div>
                  <div class="mb-2"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
              </div>
              <div class="modal-footer"><button type="submit" class="btn btn-primary">Add</button></div>
              </form>
            </div></div>
          </div>
        </div>
        <div id="section-community" class="profile-section d-none"> <!-- Community Engagements -->
          <h3>Community Engagements <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addCommModal">Add</button></h3>
          <?php echo $comm_msg; ?>
          <div class="row g-3">
              <?php 
              // Reset pointer
              $community_engagements->data_seek(0);
              while ($comm = $community_engagements->fetch_assoc()) { ?>
              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($comm['role']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($comm['organization']); ?> (<?php echo htmlspecialchars($comm['year']); ?>)</h6>
                    <p class="card-text"><?php echo htmlspecialchars($comm['description']); ?></p>
                          <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editCommModal<?php echo $comm['id']; ?>">Edit</button>
                          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this community engagement?');">
                              <input type="hidden" name="comm_action" value="delete">
                              <input type="hidden" name="comm_id" value="<?php echo $comm['id']; ?>">
                              <button class="btn btn-sm btn-danger">Delete</button>
                          </form>
                  </div>
                </div>
                  <!-- Edit Modal -->
                  <div class="modal fade" id="editCommModal<?php echo $comm['id']; ?>" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                      <form method="post">
                      <div class="modal-header"><h5 class="modal-title">Edit Community Engagement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                          <input type="hidden" name="comm_action" value="edit">
                          <input type="hidden" name="comm_id" value="<?php echo $comm['id']; ?>">
                          <div class="mb-2"><label>Role</label><input type="text" name="role" class="form-control" value="<?php echo htmlspecialchars($comm['role']); ?>" required></div>
                          <div class="mb-2"><label>Organization</label><input type="text" name="organization" class="form-control" value="<?php echo htmlspecialchars($comm['organization']); ?>"></div>
                          <div class="mb-2"><label>Year</label><input type="number" name="year" class="form-control" value="<?php echo htmlspecialchars($comm['year']); ?>" required></div>
                          <div class="mb-2"><label>Description</label><textarea name="description" class="form-control"><?php echo htmlspecialchars($comm['description']); ?></textarea></div>
                      </div>
                      <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
                      </form>
                    </div></div>
                </div>
                  </div>
              <?php } ?>
          </div>
          <!-- Add Modal -->
          <div class="modal fade" id="addCommModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
              <form method="post">
              <div class="modal-header"><h5 class="modal-title">Add Community Engagement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                  <input type="hidden" name="comm_action" value="add">
                  <div class="mb-2"><label>Role</label><input type="text" name="role" class="form-control" required></div>
                  <div class="mb-2"><label>Organization</label><input type="text" name="organization" class="form-control"></div>
                  <div class="mb-2"><label>Year</label><input type="number" name="year" class="form-control" required></div>
                  <div class="mb-2"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
              </div>
              <div class="modal-footer"><button type="submit" class="btn btn-primary">Add</button></div>
              </form>
            </div></div>
          </div>
        </div>
        <div id="section-consultancies" class="profile-section d-none"> <!-- Consultancies -->
          <h3>Consultancies <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addConsultancyModal">Add</button></h3>
          <?php echo $consultancy_msg; ?>
          <div class="row g-3">
              <?php while ($consultancy = $consultancies->fetch_assoc()) { ?>
              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($consultancy['title']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($consultancy['organization']); ?> (<?php echo htmlspecialchars($consultancy['year']); ?>)</h6>
                    <p class="card-text"><?php echo htmlspecialchars($consultancy['description']); ?></p>
                          <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editConsultancyModal<?php echo $consultancy['id']; ?>">Edit</button>
                          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this consultancy?');">
                              <input type="hidden" name="consultancy_action" value="delete">
                              <input type="hidden" name="consultancy_id" value="<?php echo $consultancy['id']; ?>">
                              <button class="btn btn-sm btn-danger">Delete</button>
                          </form>
                  </div>
                </div>
                  <!-- Edit Modal -->
                  <div class="modal fade" id="editConsultancyModal<?php echo $consultancy['id']; ?>" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                      <form method="post">
                      <div class="modal-header"><h5 class="modal-title">Edit Consultancy</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                          <input type="hidden" name="consultancy_action" value="edit">
                          <input type="hidden" name="consultancy_id" value="<?php echo $consultancy['id']; ?>">
                          <div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($consultancy['title']); ?>" required></div>
                          <div class="mb-2"><label>Organization</label><input type="text" name="organization" class="form-control" value="<?php echo htmlspecialchars($consultancy['organization']); ?>"></div>
                          <div class="mb-2"><label>Year</label><input type="number" name="year" class="form-control" value="<?php echo htmlspecialchars($consultancy['year']); ?>" required></div>
                          <div class="mb-2"><label>Description</label><textarea name="description" class="form-control"><?php echo htmlspecialchars($consultancy['description']); ?></textarea></div>
                      </div>
                      <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
                      </form>
                    </div></div>
                </div>
                  </div>
              <?php } ?>
          </div>
          <!-- Add Modal -->
          <div class="modal fade" id="addConsultancyModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
              <form method="post">
              <div class="modal-header"><h5 class="modal-title">Add Consultancy</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                  <input type="hidden" name="consultancy_action" value="add">
                  <div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                  <div class="mb-2"><label>Organization</label><input type="text" name="organization" class="form-control"></div>
                  <div class="mb-2"><label>Year</label><input type="number" name="year" class="form-control" required></div>
                  <div class="mb-2"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
              </div>
              <div class="modal-footer"><button type="submit" class="btn btn-primary">Add</button></div>
              </form>
            </div></div>
          </div>
        </div>
        <div id="section-memberships" class="profile-section d-none"> <!-- Memberships -->
          <h3>Memberships <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addMembershipModal">Add</button></h3>
          <?php echo $membership_msg; ?>
          <div class="row g-3">
              <?php while ($membership = $memberships->fetch_assoc()) { ?>
              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($membership['organization']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($membership['role']); ?> (<?php echo htmlspecialchars($membership['start_year']); ?> - <?php echo htmlspecialchars($membership['end_year']); ?>)</h6>
                          <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editMembershipModal<?php echo $membership['id']; ?>">Edit</button>
                          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this membership?');">
                              <input type="hidden" name="membership_action" value="delete">
                              <input type="hidden" name="membership_id" value="<?php echo $membership['id']; ?>">
                              <button class="btn btn-sm btn-danger">Delete</button>
                          </form>
                  </div>
                </div>
                  <!-- Edit Modal -->
                  <div class="modal fade" id="editMembershipModal<?php echo $membership['id']; ?>" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                      <form method="post">
                      <div class="modal-header"><h5 class="modal-title">Edit Membership</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                          <input type="hidden" name="membership_action" value="edit">
                          <input type="hidden" name="membership_id" value="<?php echo $membership['id']; ?>">
                          <div class="mb-2"><label>Organization</label><input type="text" name="organization" class="form-control" value="<?php echo htmlspecialchars($membership['organization']); ?>" required></div>
                          <div class="mb-2"><label>Role</label><input type="text" name="role" class="form-control" value="<?php echo htmlspecialchars($membership['role']); ?>"></div>
                          <div class="mb-2"><label>Start Year</label><input type="number" name="start_year" class="form-control" value="<?php echo htmlspecialchars($membership['start_year']); ?>" required></div>
                          <div class="mb-2"><label>End Year</label><input type="number" name="end_year" class="form-control" value="<?php echo htmlspecialchars($membership['end_year']); ?>"></div>
                      </div>
                      <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
                      </form>
                    </div></div>
                </div>
                  </div>
              <?php } ?>
          </div>
          <!-- Add Modal -->
          <div class="modal fade" id="addMembershipModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
              <form method="post">
              <div class="modal-header"><h5 class="modal-title">Add Membership</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                  <input type="hidden" name="membership_action" value="add">
                  <div class="mb-2"><label>Organization</label><input type="text" name="organization" class="form-control" required></div>
                  <div class="mb-2"><label>Role</label><input type="text" name="role" class="form-control"></div>
                  <div class="mb-2"><label>Start Year</label><input type="number" name="start_year" class="form-control" required></div>
                  <div class="mb-2"><label>End Year</label><input type="number" name="end_year" class="form-control"></div>
              </div>
              <div class="modal-footer"><button type="submit" class="btn btn-primary">Add</button></div>
              </form>
            </div></div>
          </div>
        </div>
        <div id="section-certifications" class="profile-section d-none"> <!-- Certifications -->
          <h3>Certifications <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addCertificationModal">Add</button></h3>
          <?php echo $certification_msg; ?>
          <div class="row g-3">
              <?php while ($certification = $certifications->fetch_assoc()) { ?>
              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($certification['title']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($certification['provider']); ?> (<?php echo htmlspecialchars($certification['year_awarded']); ?>)</h6>
                    <p class="card-text"><strong>Certificate URL:</strong> <a href="<?php echo htmlspecialchars($certification['certificate_url']); ?>" target="_blank">View</a></p>
                          <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editCertificationModal<?php echo $certification['id']; ?>">Edit</button>
                          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this certification?');">
                              <input type="hidden" name="certification_action" value="delete">
                              <input type="hidden" name="certification_id" value="<?php echo $certification['id']; ?>">
                              <button class="btn btn-sm btn-danger">Delete</button>
                          </form>
                  </div>
                </div>
                  <!-- Edit Modal -->
                  <div class="modal fade" id="editCertificationModal<?php echo $certification['id']; ?>" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                      <form method="post">
                      <div class="modal-header"><h5 class="modal-title">Edit Certification</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                          <input type="hidden" name="certification_action" value="edit">
                          <input type="hidden" name="certification_id" value="<?php echo $certification['id']; ?>">
                          <div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($certification['title']); ?>" required></div>
                          <div class="mb-2"><label>Provider</label><input type="text" name="provider" class="form-control" value="<?php echo htmlspecialchars($certification['provider']); ?>"></div>
                          <div class="mb-2"><label>Year Awarded</label><input type="number" name="year_awarded" class="form-control" value="<?php echo htmlspecialchars($certification['year_awarded']); ?>" required></div>
                          <div class="mb-2"><label>Certificate URL</label><input type="text" name="certificate_url" class="form-control" value="<?php echo htmlspecialchars($certification['certificate_url']); ?>"></div>
                      </div>
                      <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
                      </form>
                    </div></div>
                </div>
                  </div>
              <?php } ?>
          </div>
          <!-- Add Modal -->
          <div class="modal fade" id="addCertificationModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
              <form method="post">
              <div class="modal-header"><h5 class="modal-title">Add Certification</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                  <input type="hidden" name="certification_action" value="add">
                  <div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                  <div class="mb-2"><label>Provider</label><input type="text" name="provider" class="form-control"></div>
                  <div class="mb-2"><label>Year Awarded</label><input type="number" name="year_awarded" class="form-control" required></div>
                  <div class="mb-2"><label>Certificate URL</label><input type="text" name="certificate_url" class="form-control"></div>
              </div>
              <div class="modal-footer"><button type="submit" class="btn btn-primary">Add</button></div>
              </form>
            </div></div>
          </div>
        </div>
        <div id="section-extras" class="profile-section d-none"> <!-- Extras -->
          <h3>Extras</h3>
          <?php 
          $extra = $connection->query("SELECT * FROM user_extras WHERE user_id = $user_id LIMIT 1")->fetch_assoc();
          echo $extra_msg; 
          ?>
          <div class="card">
            <div class="card-body">
          <form method="post">
              <input type="hidden" name="extra_action" value="<?php echo $extra ? 'edit' : 'add'; ?>">
              <?php if ($extra) { echo '<input type="hidden" name="extra_id" value="'.htmlspecialchars($extra['id']).'">'; } ?>
              <div class="mb-2"><label>Languages</label><input type="text" name="languages" class="form-control" value="<?php echo $extra ? htmlspecialchars($extra['languages']) : ''; ?>"></div>
              <div class="mb-2"><label>Social Links</label><textarea name="social_links" class="form-control"><?php echo $extra ? htmlspecialchars($extra['social_links']) : ''; ?></textarea></div>
              <div class="mb-2"><label>Personal Website</label><input type="text" name="personal_website" class="form-control" value="<?php echo $extra ? htmlspecialchars($extra['personal_website']) : ''; ?>"></div>
              <button type="submit" class="btn btn-primary">Save</button>
          </form>
            </div>
          </div>
        </div>
        <div id="section-research" class="profile-section d-none"> <!-- Research Areas -->
          <h3>Research Areas <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addResearchAreaModal">Add</button></h3>
          <?php echo $research_area_msg; ?>
          <div class="row g-3">
              <?php while ($research_area = $research_areas->fetch_assoc()) { ?>
              <div class="col-md-6">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($research_area['keyword']); ?></h5>
                          <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editResearchAreaModal<?php echo $research_area['id']; ?>">Edit</button>
                          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this research area?');">
                              <input type="hidden" name="research_area_action" value="delete">
                              <input type="hidden" name="research_area_id" value="<?php echo $research_area['id']; ?>">
                              <button class="btn btn-sm btn-danger">Delete</button>
                          </form>
                  </div>
                </div>
                  <!-- Edit Modal -->
                  <div class="modal fade" id="editResearchAreaModal<?php echo $research_area['id']; ?>" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                      <form method="post">
                      <div class="modal-header"><h5 class="modal-title">Edit Research Area</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                          <input type="hidden" name="research_area_action" value="edit">
                          <input type="hidden" name="research_area_id" value="<?php echo $research_area['id']; ?>">
                          <div class="mb-2"><label>Keyword</label><input type="text" name="keyword" class="form-control" value="<?php echo htmlspecialchars($research_area['keyword']); ?>" required></div>
                      </div>
                      <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
                      </form>
                    </div></div>
                </div>
                  </div>
              <?php } ?>
          </div>
          <!-- Add Modal -->
          <div class="modal fade" id="addResearchAreaModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
              <form method="post">
              <div class="modal-header"><h5 class="modal-title">Add Research Area</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                  <input type="hidden" name="research_area_action" value="add">
                  <div class="mb-2"><label>Keyword</label><input type="text" name="keyword" class="form-control" required></div>
              </div>
              <div class="modal-footer"><button type="submit" class="btn btn-primary">Add</button></div>
              </form>
            </div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<script>
// Profile section nav logic
$('#profile-section-nav .nav-link').on('click', function(e) {
  e.preventDefault();
  $('#profile-section-nav .nav-link').removeClass('active');
  $(this).addClass('active');
  var section = $(this).data('section');
  $('.profile-section').addClass('d-none');
  $('#section-' + section).removeClass('d-none');
  window.scrollTo({top: 0, behavior: 'smooth'});
});
</script>
<script src="../dashboard/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
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
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $timeout = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header("Location: login.php?expired=1");
        exit;
    }

    $_SESSION['last_activity'] = time();
}

$currentPage = basename($_SERVER['PHP_SELF']);

function jp_nav_active($page, $currentPage) {
    return $currentPage === $page ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT8415 Job Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="app-body">

<nav class="navbar navbar-expand-lg navbar-light site-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <span class="brand-mark"><i class="bi bi-briefcase-fill"></i></span>
      TalentNet
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link<?php echo jp_nav_active('index.php', $currentPage); ?>" href="index.php">
            <i class="bi bi-house-door me-1"></i>Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php echo jp_nav_active('categories.php', $currentPage); ?>" href="categories.php">
            <i class="bi bi-grid me-1"></i>Categories
          </a>
        </li>
        
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <li class="nav-item">
              <a class="nav-link<?php echo in_array($currentPage, ['admin_dashboard.php', 'admin_reports.php']) ? ' active' : ''; ?>" href="admin_dashboard.php">
                <i class="bi bi-speedometer2 me-1"></i>Admin Dashboard
              </a>
            </li>
        <?php endif; ?>
      </ul>
      
      <ul class="navbar-nav">
        <?php if (isset($_SESSION['user_id'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle me-1"></i>
              <?php echo htmlspecialchars($_SESSION['user_name']); ?> 
              <span class="badge text-bg-light border"><?php echo ucfirst(htmlspecialchars($_SESSION['user_role'])); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <li><a class="dropdown-item" href="admin_dashboard.php"><i class="bi bi-sliders me-2"></i>Admin Panel</a></li>
                <li><hr class="dropdown-divider"></li>
              <?php elseif ($_SESSION['user_role'] === 'creator'): ?>
                <li><a class="dropdown-item" href="employer_panel.php"><i class="bi bi-card-list me-2"></i>My Listings</a></li>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </li>
          
          <?php if ($_SESSION['user_role'] === 'creator'): ?>
            <li class="nav-item">
              <a class="btn btn-primary btn-sm ms-lg-2" href="post_job.php">
                <i class="bi bi-plus-circle me-1"></i>Post a Job
              </a>
            </li>
          <?php endif; ?>

        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link<?php echo jp_nav_active('login.php', $currentPage); ?>" href="login.php">
              <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </a>
          </li>
          <li class="nav-item">
            <a class="btn btn-primary btn-sm ms-lg-2" href="register.php">
              <i class="bi bi-person-plus me-1"></i>Register
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container app-main">

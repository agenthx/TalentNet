<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['SCRIPT_NAME']);
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
<body>

<nav class="navbar navbar-expand-lg navbar-dark site-navbar">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <span class="brand-mark"><i class="bi bi-briefcase-fill"></i></span>
      <span>Job Portal</span>
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto gap-lg-1">
        <li class="nav-item">
          <a class="nav-link px-lg-3 <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
            <i class="bi bi-house-door me-1"></i>Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-lg-3 <?= $currentPage === 'categories.php' ? 'active' : '' ?>" href="categories.php">
            <i class="bi bi-grid me-1"></i>Categories
          </a>
        </li>
      </ul>
      
      <ul class="navbar-nav align-items-lg-center gap-lg-2">
        <?php 
        if (isset($_SESSION['user_id'])): ?>
          <!-- Displayed for logged in users -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle px-lg-3" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle me-1"></i>
              <?php echo htmlspecialchars($_SESSION['user_name']); ?>
              <span class="badge text-bg-light ms-1"><?php echo ucfirst(htmlspecialchars($_SESSION['user_role'])); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <li><a class="dropdown-item" href="admin_dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Panel</a></li>
                <li><hr class="dropdown-divider"></li>
              <?php elseif ($_SESSION['user_role'] === 'creator'): ?>
                <li><a class="dropdown-item" href="employer_panel.php"><i class="bi bi-list-check me-2"></i>My Listings</a></li>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </li>
          
          <?php if ($_SESSION['user_role'] === 'creator'): ?>
            <li class="nav-item">
              <a class="btn btn-primary" href="post_job.php"><i class="bi bi-plus-lg me-1"></i>Post a Job</a>
            </li>
          <?php endif; ?>

        <?php else: ?>
          <!-- Displayed for guests (non loged in) -->
          <li class="nav-item">
            <a class="nav-link px-lg-3 <?= $currentPage === 'login.php' ? 'active' : '' ?>" href="login.php">
              <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </a>
          </li>
          <li class="nav-item">
            <a class="btn btn-outline-light <?= $currentPage === 'register.php' ? 'active' : '' ?>" href="register.php">
              Register
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="site-main container">

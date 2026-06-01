<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT8415 Job Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">Job Portal</a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="categories.php">Categories</a>
        </li>
        
        <?php 
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // ADDED: Direct Admin Dashboard link in main nav
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <li class="nav-item">
              <a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a>
            </li>
        <?php endif; ?>
      </ul>
      
      <ul class="navbar-nav">
        <?php if (isset($_SESSION['user_id'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?> 
              <span class="badge bg-secondary"><?php echo ucfirst(htmlspecialchars($_SESSION['user_role'])); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <li><a class="dropdown-item" href="admin_dashboard.php">Admin Panel</a></li>
                <li><hr class="dropdown-divider"></li>
              <?php elseif ($_SESSION['user_role'] === 'creator'): ?>
                <li><a class="dropdown-item" href="employer_panel.php">My Listings</a></li>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
            </ul>
          </li>
          
          <?php if ($_SESSION['user_role'] === 'creator'): ?>
            <li class="nav-item">
              <a class="btn btn-primary ms-2" href="post_job.php">Post a Job</a>
            </li>
          <?php endif; ?>

        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="login.php">Login</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-outline-primary ms-2" href="register.php">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container">

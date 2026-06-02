<?php
/*
 * This file verifies the user email and password, then starts a secure session, 
 * and also implemented session expiry for security reasons
 */
session_start();

// Already logged in
if (isset($_SESSION['user_id'])) {
    $dest = ($_SESSION['user_role'] ?? '') === 'admin' ? 'admin_dashboard.php' : 'index.php';
    header("Location: $dest");
    exit;
}

require_once 'db.php';

$errors = [];
$email = '';

// Check if the user just registered
$success_msg = isset($_GET['registered']) ? "Registration successful! You can now log in" : "";

// The session is timed out
$expired_msg = isset($_GET['expired']) ? "Your session expired. Please log in again" : "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both email and password.";
    } else {
        try {
            // MySQLi uses '?' instead of named parameters
            $sql = "SELECT u.user_id, u.role_id, r.role_name, u.full_name, u.password_hash, u.is_active 
                    FROM dbProj_users u
                    JOIN dbProj_roles r ON u.role_id = r.role_id
                    WHERE u.email = ?
                    LIMIT 1";
                    
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }

            // "s" tells MySQLi that the $email variable is a String
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            // Get the result set and fetch it as an associative array
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $password_matches = false;
            if ($user) {
                $stored_hash = trim((string)$user['password_hash']);
                $password_matches = password_verify($password, $stored_hash);
            }

            // Verify password and account status
            if ($user && $password_matches) {
                if ($user['is_active']) {
                    session_regenerate_id(true);

                    // Store user data in the session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['role_name'];
                    $_SESSION['role_id'] = $user['role_id'];
                    
                    // A timestamp for session expiry stored in the session
                    $_SESSION['last_activity'] = time();

                    // Redirect based on role or back to home
                    if ($user['role_name'] === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit;
                } else {
                    $errors[] = "Your account has been deactivated. Please contact support";
                }
            } else {
                $errors[] = "Invalid email or password";
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div class="auth-shell">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card auth-card">
                <div class="card-header text-center">
                    <span class="category-icon bg-white text-primary mb-3"><i class="bi bi-person-check"></i></span>
                    <h1 class="h4 mb-1">Welcome Back</h1>
                    <p class="mb-0 small opacity-75">Sign in to manage your account and job activity.</p>
                </div>
                <div class="card-body p-4">
                    
                    <?php if ($expired_msg): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-clock-history me-1"></i>
                            <?php echo htmlspecialchars($expired_msg); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_msg): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-1"></i>
                            <?php echo htmlspecialchars($success_msg); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                            </button>
                        </div>
                        
                        <div class="auth-note mt-3 text-center p-3">
                            <p class="mb-0">Don't have an account? <a href="register.php" class="fw-bold">Register here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

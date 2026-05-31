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
            // Fetch the user by email by joining the roles table to get the role name immediately
            $stmt = $pdo->prepare("
                SELECT u.user_id, u.role_id, r.role_name, u.full_name, u.password_hash, u.is_active 
                FROM dbProj_users u
                JOIN dbProj_roles r ON u.role_id = r.role_id
                WHERE u.email = :email
                LIMIT 1
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // Verify password and account status
            if ($user && password_verify($password, $user['password_hash'])) {
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
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div class="row justify-content-center auth-shell g-4">
    <div class="col-lg-5 d-none d-lg-block">
        <aside class="auth-aside d-flex flex-column justify-content-between">
            <div>
                <span class="eyebrow"><i class="bi bi-shield-lock"></i> Secure access</span>
                <h1 class="h2 fw-bold mb-3">Welcome Back</h1>
                <p class="mb-0">Sign in to continue to the job portal workspace.</p>
            </div>
            <div>
                <div class="auth-feature">
                    <i class="bi bi-check-circle-fill"></i>
                    <div>
                        <strong>Role-based access</strong>
                        <p class="small mb-0 text-white-50">Admins, creators, and viewers each land in the right place.</p>
                    </div>
                </div>
                <div class="auth-feature">
                    <i class="bi bi-clock-history"></i>
                    <div>
                        <strong>Session expiry</strong>
                        <p class="small mb-0 text-white-50">Inactive sessions are handled by the existing security helper.</p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
    <div class="col-md-8 col-lg-5">
        <div class="card auth-card border-0">
            <div class="card-header text-center py-4">
                <h2 class="h4 mb-1">Log in</h2>
                <p class="text-muted mb-0">Use your project account details.</p>
            </div>
            <div class="card-body p-4 p-lg-5">
                
                <?php if ($expired_msg): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-clock-history me-1"></i>
                        <?php echo htmlspecialchars($expired_msg); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_msg): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
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
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </button>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

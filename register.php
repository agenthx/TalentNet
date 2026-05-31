<?php
/*
 * This page handles both the display of the registration form and the processing of the data
 * Bcrypt is used for secure password hashing and prepared statements to prevent SQL injection attacks
 */

require_once 'db.php';


$errors = [];
$full_name = '';
$email = '';
$role_id = 1;

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and filter input data
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role_id = (int)($_POST['role_id'] ?? 1);

    // Validation
    if (empty($full_name)) {
        $errors[] = "Please enter your full name";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (!in_array($role_id, [1, 2])) { // Only allow 1 (Job Seeker) or 2 (Employer)
        $errors[] = "Invalid role selected";
    }

    // Check if email exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM dbProj_users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "This email is already registered. Please login in instead";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            // Hashing password using bcrypt
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Insert the new user into the database
            $stmt = $pdo->prepare("
                INSERT INTO dbProj_users (role_id, full_name, email, password_hash, is_active) 
                VALUES (:role_id, :full_name, :email, :password_hash, TRUE)
            ");
            
            $stmt->execute([
                'role_id' => $role_id,
                'full_name' => $full_name,
                'email' => $email,
                'password_hash' => $password_hash
            ]);

            header("Location: login.php?registered=1");
            exit;

        } catch (PDOException $e) {
            $errors[] = "Oops! Something went wrong while saving your account. Please try again.";
        }
    }
}


include 'header.php';
?>

<div class="row justify-content-center auth-shell g-4">
    <div class="col-lg-5 d-none d-lg-block">
        <aside class="auth-aside d-flex flex-column justify-content-between">
            <div>
                <span class="eyebrow"><i class="bi bi-person-plus"></i> Join the portal</span>
                <h1 class="h2 fw-bold mb-3">Create an Account</h1>
                <p class="mb-0">Choose a role and start using the job portal with your own credentials.</p>
            </div>
            <div>
                <div class="auth-feature">
                    <i class="bi bi-people-fill"></i>
                    <div>
                        <strong>Job seeker</strong>
                        <p class="small mb-0 text-white-50">Browse and interact with available opportunities.</p>
                    </div>
                </div>
                <div class="auth-feature">
                    <i class="bi bi-building-fill"></i>
                    <div>
                        <strong>Employer</strong>
                        <p class="small mb-0 text-white-50">Prepare for creator workflows as your team expands them.</p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
    <div class="col-md-9 col-lg-6">
        <div class="card auth-card border-0">
            <div class="card-header text-center py-4">
                <h2 class="h4 mb-1">Create an Account</h2>
                <p class="text-muted mb-0">Your details stay connected to the existing database flow.</p>
            </div>
            <div class="card-body p-4 p-lg-5">
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form id="registrationForm" method="POST" action="register.php" novalidate>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                               value="<?php echo htmlspecialchars($full_name); ?>" required>
                            <div class="invalid-feedback">Please enter your name</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo htmlspecialchars($email); ?>" required>
                            <div class="invalid-feedback">Please enter a valid email</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">I am a:</label>
                        <div class="role-choice">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="role_id" id="role_seeker" value="1" <?php echo $role_id == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="role_seeker">Job Seeker</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="role_id" id="role_employer" value="2" <?php echo $role_id == 2 ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="role_employer">Employer</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        </div>
                        <div class="small text-muted">Must be at least 6 characters</div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-check2-square"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus me-1"></i>Sign Up
                        </button>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>


/* Client-side validation */
document.getElementById('registrationForm').addEventListener('submit', function(event) {
    let isValid = true;
    const form = this;
    
    // Reset validation states
    form.classList.remove('was-validated');
    
    // Check password matching
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        alert("Passwords do not match!");
        isValid = false;
    }
    
    if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
        isValid = false;
    }
    
    form.classList.add('was-validated');
    
    if (!isValid) {
        event.preventDefault();
    }
});
</script>

<?php include 'footer.php'; ?>

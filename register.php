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
            // MySQLi implementation for checking existing email
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM dbProj_users WHERE email = ?");
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['total'] > 0) {
                $errors[] = "This email is already registered. Please login in instead";
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            // Hashing password using bcrypt
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Insert the new user into the database using MySQLi
            $stmt = $conn->prepare("
                INSERT INTO dbProj_users (role_id, full_name, email, password_hash, is_active) 
                VALUES (?, ?, ?, ?, TRUE)
            ");
            
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            
            // "isss" = integer (role_id), string (full_name), string (email), string (password_hash)
            $stmt->bind_param("isss", $role_id, $full_name, $email, $password_hash);
            $stmt->execute();

            header("Location: login.php?registered=1");
            exit;

        } catch (Exception $e) {
            $errors[] = "Oops! Something went wrong while saving your account. Please try again.";
        }
    }
}

include 'header.php';
?>

<div class="auth-shell">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card auth-card">
                <div class="card-header text-center">
                    <span class="category-icon bg-white text-primary mb-3"><i class="bi bi-person-plus"></i></span>
                    <h1 class="h4 mb-1">Create an Account</h1>
                    <p class="mb-0 small opacity-75">Choose your role and join the job portal.</p>
                </div>
                <div class="card-body p-4">
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div id="client-password-error" class="alert alert-danger d-none" role="alert">
                        Passwords do not match.
                    </div>

                    <form id="registrationForm" method="POST" action="register.php" novalidate>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($full_name); ?>" required>
                            <div class="invalid-feedback">Please enter your name</div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" required>
                            <div class="invalid-feedback">Please enter a valid email</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">I am a:</label>
                            <div class="soft-panel p-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="role_id" id="role_seeker" value="1" <?php echo $role_id == 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="role_seeker">Job Seeker</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="role_id" id="role_employer" value="2" <?php echo $role_id == 2 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="role_employer">Employer</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <div class="small text-muted mt-1">Must be at least 6 characters</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-person-plus me-1"></i>Sign Up
                            </button>
                        </div>
                        
                        <div class="auth-note mt-3 text-center p-3">
                            <p class="mb-0">Already have an account? <a href="login.php" class="fw-bold">Login here</a></p>
                        </div>
                    </form>
                </div>
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
    
    const passwordError = document.getElementById('client-password-error');
    const confirmInput = document.getElementById('confirm_password');

    if (password !== confirm) {
        confirmInput.setCustomValidity('Passwords do not match.');
        passwordError.classList.remove('d-none');
        isValid = false;
    } else {
        confirmInput.setCustomValidity('');
        passwordError.classList.add('d-none');
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

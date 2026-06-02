<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
require_once 'header.php';

// --- SECURITY CHECK: Only 'creator' role can access this page ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'creator') {
    echo "<div class='container my-5'>
            <div class='alert alert-danger shadow-sm'>
                <h4 class='alert-heading'><i class='bi bi-shield-lock me-2'></i>Access Denied</h4>
                <p>You must be logged in with a Creator account to post new job listings.</p>
                <hr>
                <a href='index.php' class='btn btn-outline-danger btn-sm'>Return to Home</a>
            </div>
          </div>";
    require_once 'footer.php';
    exit;
}

$message = '';
$message_type = '';

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and capture inputs
    $title = trim($_POST['title'] ?? '');
    $employer_id = intval($_POST['employer_id'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $employment_type = trim($_POST['employment_type'] ?? 'Full-time');
    
    // Strictly handle blank inputs to ensure they become true NULLs, not 0s
    $salary_min = (isset($_POST['salary_min']) && trim($_POST['salary_min']) !== '') ? intval($_POST['salary_min']) : null;
    $salary_max = (isset($_POST['salary_max']) && trim($_POST['salary_max']) !== '') ? intval($_POST['salary_max']) : null;
    
    $short_description = trim($_POST['short_description'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    
    // Basic Server-Side Validation
    if (empty($title) || empty($employer_id) || empty($category_id) || empty($short_description) || empty($description)) {
        $message = "Please fill in all required fields.";
        $message_type = "danger";
    } 
    // Catch Min > Max before it hits the database to prevent the constraint crash
    elseif ($salary_min !== null && $salary_max !== null && $salary_min > $salary_max) {
        $message = "The minimum salary cannot be greater than the maximum salary.";
        $message_type = "danger";
    } 
    else {
        try {
            // Grab the ID of the creator who is currently logged in
            $created_by_user_id = $_SESSION['user_id'];

            // Insert new job into database
            $stmt = $conn->prepare("
                INSERT INTO dbProj_job_listings 
                (employer_id, category_id, created_by_user_id, title, short_description, description, requirements, location, employment_type, salary_min, salary_max, currency, status, published_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'USD', 'published', NOW())
            ");
            
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            
            $stmt->bind_param("iiissssssii", 
                $employer_id, 
                $category_id, 
                $created_by_user_id,
                $title, 
                $short_description, 
                $description, 
                $requirements, 
                $location, 
                $employment_type, 
                $salary_min, 
                $salary_max
            );
            
if ($stmt->execute()) {
                $new_job_id = $stmt->insert_id;
                $message = "Job listing successfully published! <a href='job_details.php?id={$new_job_id}' class='alert-link'>View your listing here.</a>";
                $message_type = "success";
                
// --- ENHANCED IMAGE UPLOAD WITH ABSOLUTE PATHING ---
                if (isset($_FILES['job_image'])) {
                    if ($_FILES['job_image']['error'] === UPLOAD_ERR_OK) {
                        
                        // 1. Absolute path for saving the file to your hard drive
                        $upload_dir_absolute = __DIR__ . '/uploads/jobs/';
                        
                        // 2. Relative path for saving to the database so the HTML <img> tag works
                        $upload_dir_relative = 'uploads/jobs/';
                        
                        if (!is_dir($upload_dir_absolute)) {
                            mkdir($upload_dir_absolute, 0777, true);
                        }
                        
                        $file_name = time() . '_' . basename($_FILES['job_image']['name']);
                        
                        $target_path_absolute = $upload_dir_absolute . $file_name;
                        $target_path_relative = $upload_dir_relative . $file_name;
                        
                        // Attempt to move the file using the absolute hard drive path
                        if (move_uploaded_file($_FILES['job_image']['tmp_name'], $target_path_absolute)) {
                            // Save the relative path to the database
                            $media_stmt = $conn->prepare("INSERT INTO dbProj_job_media (job_id, media_type, file_path, is_primary) VALUES (?, 'image', ?, TRUE)");
                            $media_stmt->bind_param("is", $new_job_id, $target_path_relative);
                            $media_stmt->execute();
                            $media_stmt->close();
                        } else {
                            $message .= "<br><br><b>Image Warning:</b> The server blocked moving the file. Check folder permissions.";
                            $message_type = "warning";
                        }
                    } else {
                        // Catch exactly why the server rejected it
                        $err = $_FILES['job_image']['error'];
                        $message .= "<br><br><b>Image Upload Failed (Error Code $err):</b> ";
                        if ($err == 1 || $err == 2) {
                            $message .= "The image is too large! Please upload a file smaller than 2MB.";
                        } else {
                            $message .= "Something went wrong with the file. Please try a different image.";
                        }
                        $message_type = "warning";
                    }
                }
                // Clear the form if everything went perfectly
                if ($message_type === "success") {
                    $_POST = array(); 
                }
                
            } else {
                throw new Exception("Execution error: " . $stmt->error);
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $message = "Failed to post job: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// --- FETCH DATA FOR DROPDOWNS ---
$employers = [];
$categories = [];

try {
    $empResult = $conn->query("SELECT employer_id, company_name FROM dbProj_employers ORDER BY company_name ASC");
    if ($empResult) $employers = $empResult->fetch_all(MYSQLI_ASSOC);

    $catResult = $conn->query("SELECT category_id, category_name FROM dbProj_job_categories ORDER BY category_name ASC");
    if ($catResult) $categories = $catResult->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Post a New Opportunity</h1>
                    <p class="text-muted mb-0">Fill out the details below to publish a new job listing to the portal.</p>
                </div>
                <div class="stat-icon bg-primary text-white shadow-sm" style="width: 3.5rem; height: 3.5rem; border-radius: 50%;">
                    <i class="bi bi-pencil-square fs-4"></i>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="detail-card p-4 p-md-5">
                <form method="POST" action="post_job.php" enctype="multipart/form-data">
                    
                    <h5 class="fw-bold border-bottom pb-2 mb-4 text-primary">1. Basic Information</h5>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">Job Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required placeholder="e.g., Senior Frontend Developer" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="employer_id" class="form-label fw-semibold">Employer / Company <span class="text-danger">*</span></label>
                            <select class="form-select" id="employer_id" name="employer_id" required>
                                <option value="">Select Employer...</option>
                                <?php foreach ($employers as $emp): ?>
                                    <option value="<?= $emp['employer_id'] ?>" <?= (isset($_POST['employer_id']) && $_POST['employer_id'] == $emp['employer_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($emp['company_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="category_id" class="form-label fw-semibold">Job Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h5 class="fw-bold border-bottom pb-2 mb-4 text-primary mt-5">2. Job Details</h5>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="location" class="form-label fw-semibold">Location</label>
                            <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Manama, Bahrain (Or Remote)" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="employment_type" class="form-label fw-semibold">Employment Type</label>
                            <select class="form-select" id="employment_type" name="employment_type">
                                <option value="Full-time" <?= (isset($_POST['employment_type']) && $_POST['employment_type'] === 'Full-time') ? 'selected' : '' ?>>Full-time</option>
                                <option value="Part-time" <?= (isset($_POST['employment_type']) && $_POST['employment_type'] === 'Part-time') ? 'selected' : '' ?>>Part-time</option>
                                <option value="Contract" <?= (isset($_POST['employment_type']) && $_POST['employment_type'] === 'Contract') ? 'selected' : '' ?>>Contract</option>
                                <option value="Internship" <?= (isset($_POST['employment_type']) && $_POST['employment_type'] === 'Internship') ? 'selected' : '' ?>>Internship</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="salary_min" class="form-label fw-semibold">Minimum Salary (USD)</label>
                            <input type="number" class="form-control" id="salary_min" name="salary_min" placeholder="e.g., 50000" value="<?= htmlspecialchars($_POST['salary_min'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="salary_max" class="form-label fw-semibold">Maximum Salary (USD)</label>
                            <input type="number" class="form-control" id="salary_max" name="salary_max" placeholder="e.g., 80000" value="<?= htmlspecialchars($_POST['salary_max'] ?? '') ?>">
                        </div>
                    </div>

                    <h5 class="fw-bold border-bottom pb-2 mb-4 text-primary mt-5">3. Content & Description</h5>

                    <div class="mb-3">
                        <label for="short_description" class="form-label fw-semibold">Short Description Summary <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="short_description" name="short_description" rows="2" required placeholder="A brief 1-2 sentence overview for the search feed..."><?= htmlspecialchars($_POST['short_description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Full Job Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="6" required placeholder="Detail the core responsibilities and daily operations..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="requirements" class="form-label fw-semibold">Requirements</label>
                        <textarea class="form-control" id="requirements" name="requirements" rows="4" placeholder="List the skills, experience, and education required..."><?= htmlspecialchars($_POST['requirements'] ?? '') ?></textarea>
                    </div>
                    <h5 class="fw-bold border-bottom pb-2 mb-4 text-primary mt-5">4. Media Upload</h5>
                    <div class="mb-4">
                        <label for="job_image" class="form-label fw-semibold">Cover Image (Required)</label>
                        <input type="file" class="form-control" id="job_image" name="job_image" accept="image/*" required>
                        <div class="form-text">Upload a high-quality image to represent this job listing on the main feed.</div>
                    </div>
                    <div class="d-grid gap-2 mt-5">
                        <button type="submit" class="btn btn-success btn-lg fw-bold">
                            <i class="bi bi-cloud-arrow-up me-2"></i>Publish Job Listing
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
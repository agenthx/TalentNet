<?php
require_once 'auth_helper.php';
require_once 'db.php';
require_once 'upload_helper.php';

require_role('creator');

$currentUserId = (int)$_SESSION['user_id'];
$message = '';
$message_type = 'success';
$errors = [];
$job = null;
$editJobId = isset($_GET['edit']) ? max(0, (int)$_GET['edit']) : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editJobId = max(0, (int)($_POST['edit_job_id'] ?? 0));
}

function jp_post_job_value($name, $job, $default = '') {
    if (isset($_POST[$name])) {
        return $_POST[$name];
    }

    return $job[$name] ?? $default;
}

function jp_is_selected($value, $current) {
    return (string)$value === (string)$current ? 'selected' : '';
}

$employers = [];
$categories = [];

try {
    $empStmt = $conn->prepare("
        SELECT employer_id, company_name
        FROM dbProj_employers
        WHERE owner_user_id = ?
        ORDER BY company_name ASC
    ");
    if (!$empStmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    $empStmt->bind_param("i", $currentUserId);
    $empStmt->execute();
    $empResult = $empStmt->get_result();
    $employers = $empResult ? $empResult->fetch_all(MYSQLI_ASSOC) : [];
    $empStmt->close();

    $catResult = $conn->query("SELECT category_id, category_name FROM dbProj_job_categories ORDER BY category_name ASC");
    if ($catResult) {
        $categories = $catResult->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $errors[] = "Could not load form options: " . $e->getMessage();
}

$ownedEmployerIds = array_map('intval', array_column($employers, 'employer_id'));

if ($editJobId > 0) {
    try {
        $jobStmt = $conn->prepare("
            SELECT j.*
            FROM dbProj_job_listings j
            INNER JOIN dbProj_employers e ON e.employer_id = j.employer_id
            WHERE j.job_id = ? AND e.owner_user_id = ?
            LIMIT 1
        ");
        if (!$jobStmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }

        $jobStmt->bind_param("ii", $editJobId, $currentUserId);
        $jobStmt->execute();
        $jobResult = $jobStmt->get_result();
        $job = $jobResult ? $jobResult->fetch_assoc() : null;
        $jobStmt->close();

        if (!$job) {
            $errors[] = "This listing does not exist or does not belong to one of your employer profiles.";
            $editJobId = 0;
        }
    } catch (Exception $e) {
        $errors[] = "Could not load the listing for editing: " . $e->getMessage();
        $editJobId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $title = trim($_POST['title'] ?? '');
    $employer_id = (int)($_POST['employer_id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $employment_type = trim($_POST['employment_type'] ?? 'Full-time');
    $work_mode = trim($_POST['work_mode'] ?? 'On-site');
    $salary_min = trim($_POST['salary_min'] ?? '');
    $salary_max = trim($_POST['salary_max'] ?? '');
    $currency = strtoupper(trim($_POST['currency'] ?? 'OMR'));
    $application_url = trim($_POST['application_url'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $status = trim($_POST['status'] ?? 'published');

    if ($title === '') {
        $errors[] = "Job title is required.";
    }
    if (!in_array($employer_id, $ownedEmployerIds, true)) {
        $errors[] = "Choose one of your own employer profiles.";
    }
    if ($category_id < 1) {
        $errors[] = "Choose a job category.";
    }
    if ($location === '') {
        $errors[] = "Location is required.";
    }
    if (!in_array($employment_type, ['Full-time', 'Part-time', 'Contract', 'Internship'], true)) {
        $errors[] = "Choose a valid employment type.";
    }
    if (!in_array($work_mode, ['On-site', 'Hybrid', 'Remote'], true)) {
        $errors[] = "Choose a valid work mode.";
    }
    if (!preg_match('/^[A-Z]{3}$/', $currency)) {
        $errors[] = "Currency must be a three-letter code such as OMR or USD.";
    }
    if ($application_url !== '' && !filter_var($application_url, FILTER_VALIDATE_URL)) {
        $errors[] = "Application URL must be a valid URL.";
    }
    if ($short_description === '' || $description === '') {
        $errors[] = "Short description and full job description are required.";
    }
    if (!in_array($status, ['draft', 'published', 'closed'], true)) {
        $errors[] = "Choose draft, published, or closed status.";
    }

    $salary_min_value = $salary_min === '' ? null : (float)$salary_min;
    $salary_max_value = $salary_max === '' ? null : (float)$salary_max;

    if ($salary_min !== '' && (!is_numeric($salary_min) || $salary_min_value < 0)) {
        $errors[] = "Minimum salary must be a positive number.";
    }
    if ($salary_max !== '' && (!is_numeric($salary_max) || $salary_max_value < 0)) {
        $errors[] = "Maximum salary must be a positive number.";
    }
    if ($salary_min_value !== null && $salary_max_value !== null && $salary_min_value > $salary_max_value) {
        $errors[] = "The minimum salary cannot be greater than the maximum salary.";
    }

    if (empty($errors)) {
        try {
            if ($editJobId > 0) {
                $stmt = $conn->prepare("
                    UPDATE dbProj_job_listings j
                    INNER JOIN dbProj_employers e ON e.employer_id = j.employer_id
                    SET
                        j.employer_id = ?,
                        j.category_id = ?,
                        j.title = ?,
                        j.short_description = ?,
                        j.description = ?,
                        j.requirements = ?,
                        j.location = ?,
                        j.employment_type = ?,
                        j.work_mode = ?,
                        j.salary_min = ?,
                        j.salary_max = ?,
                        j.currency = ?,
                        j.application_url = ?,
                        j.status = ?,
                        j.published_at = CASE WHEN ? = 'published' THEN COALESCE(j.published_at, NOW()) ELSE NULL END
                    WHERE j.job_id = ? AND e.owner_user_id = ?
                ");
                if (!$stmt) {
                    throw new Exception("Database prepare error: " . $conn->error);
                }

                $stmt->bind_param(
                    "iisssssssddssssii",
                    $employer_id,
                    $category_id,
                    $title,
                    $short_description,
                    $description,
                    $requirements,
                    $location,
                    $employment_type,
                    $work_mode,
                    $salary_min_value,
                    $salary_max_value,
                    $currency,
                    $application_url,
                    $status,
                    $status,
                    $editJobId,
                    $currentUserId
                );
                $stmt->execute();
                $savedJobId = $editJobId;
                $message = "Listing updated successfully.";
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO dbProj_job_listings
                    (employer_id, category_id, created_by_user_id, title, short_description, description, requirements, location, employment_type, work_mode, salary_min, salary_max, currency, application_url, status, published_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ? = 'published' THEN NOW() ELSE NULL END)
                ");
                if (!$stmt) {
                    throw new Exception("Database prepare error: " . $conn->error);
                }

                $stmt->bind_param(
                    "iiisssssssddssss",
                    $employer_id,
                    $category_id,
                    $currentUserId,
                    $title,
                    $short_description,
                    $description,
                    $requirements,
                    $location,
                    $employment_type,
                    $work_mode,
                    $salary_min_value,
                    $salary_max_value,
                    $currency,
                    $application_url,
                    $status,
                    $status
                );
                $stmt->execute();
                $savedJobId = $stmt->insert_id;
                $editJobId = $savedJobId;
                $message = "Job listing saved successfully.";
            }
            $stmt->close();

            $upload = jp_upload_image('job_image', 'uploads/jobs');
            if (!$upload['ok']) {
                $message .= " Image upload was skipped: " . $upload['message'];
                $message_type = 'warning';
            } elseif ($upload['path']) {
                $clearStmt = $conn->prepare("UPDATE dbProj_job_media SET is_primary = FALSE WHERE job_id = ? AND media_type = 'image'");
                if ($clearStmt) {
                    $clearStmt->bind_param("i", $savedJobId);
                    $clearStmt->execute();
                    $clearStmt->close();
                }

                $altText = $title . ' cover image';
                $mediaStmt = $conn->prepare("
                    INSERT INTO dbProj_job_media (job_id, media_type, file_path, alt_text, is_primary)
                    VALUES (?, 'image', ?, ?, TRUE)
                ");
                if ($mediaStmt) {
                    $mediaStmt->bind_param("iss", $savedJobId, $upload['path'], $altText);
                    $mediaStmt->execute();
                    $mediaStmt->close();
                }
            }

            $message .= " <a href='job_details.php?id=" . (int)$savedJobId . "' class='alert-link'>View public page</a> or <a href='employer_panel.php' class='alert-link'>return to your employer panel</a>.";

            $job = [
                'job_id' => $savedJobId,
                'employer_id' => $employer_id,
                'category_id' => $category_id,
                'title' => $title,
                'short_description' => $short_description,
                'description' => $description,
                'requirements' => $requirements,
                'location' => $location,
                'employment_type' => $employment_type,
                'work_mode' => $work_mode,
                'salary_min' => $salary_min_value,
                'salary_max' => $salary_max_value,
                'currency' => $currency,
                'application_url' => $application_url,
                'status' => $status,
            ];
        } catch (Exception $e) {
            $message = "Failed to save listing: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

$pageTitle = $editJobId > 0 ? 'Edit Job Listing' : 'Post a New Opportunity';
$statusValue = jp_post_job_value('status', $job, 'published');

include 'header.php';
?>

<section class="section-hero mb-4">
    <div class="d-flex justify-content-between flex-wrap align-items-center gap-3">
        <div>
            <div class="hero-kicker"><i class="bi bi-pencil-square"></i> Employer tools</div>
            <h1 class="h2 mb-2"><?php echo htmlspecialchars($pageTitle); ?></h1>
            <p class="lead mb-0">Create, edit, save as draft, or publish your own employer listings.</p>
        </div>
        <a href="employer_panel.php" class="btn btn-light">
            <i class="bi bi-card-list me-1"></i>Employer Panel
        </a>
    </div>
</section>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show shadow-sm" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (empty($employers)): ?>
    <div class="empty-state text-center p-5">
        <i class="bi bi-building-add display-6 d-block mb-3"></i>
        <h2 class="h5">Create an employer profile first</h2>
        <p class="mb-4">A listing must belong to one of your company profiles before it can be posted.</p>
        <a href="employer_panel.php" class="btn btn-primary">
            <i class="bi bi-building-add me-1"></i>Open Employer Panel
        </a>
    </div>
<?php else: ?>
    <div class="detail-card p-4 p-md-5">
        <form method="POST" action="post_job.php<?php echo $editJobId > 0 ? '?edit=' . (int)$editJobId : ''; ?>" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="edit_job_id" value="<?php echo (int)$editJobId; ?>">

            <h2 class="h5 fw-bold border-bottom pb-2 mb-4 text-primary">1. Basic Information</h2>
            
            <div class="mb-3">
                <label for="title" class="form-label fw-semibold">Job Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars(jp_post_job_value('title', $job)); ?>">
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="employer_id" class="form-label fw-semibold">Employer / Company <span class="text-danger">*</span></label>
                    <select class="form-select" id="employer_id" name="employer_id" required>
                        <option value="">Select Employer</option>
                        <?php foreach ($employers as $emp): ?>
                            <option value="<?php echo (int)$emp['employer_id']; ?>" <?php echo jp_is_selected($emp['employer_id'], jp_post_job_value('employer_id', $job)); ?>>
                                <?php echo htmlspecialchars($emp['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="category_id" class="form-label fw-semibold">Job Category <span class="text-danger">*</span></label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat['category_id']; ?>" <?php echo jp_is_selected($cat['category_id'], jp_post_job_value('category_id', $job)); ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h2 class="h5 fw-bold border-bottom pb-2 mb-4 text-primary mt-5">2. Job Details</h2>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="location" class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="location" name="location" required value="<?php echo htmlspecialchars(jp_post_job_value('location', $job)); ?>">
                </div>
                <div class="col-md-3">
                    <label for="employment_type" class="form-label fw-semibold">Employment Type</label>
                    <select class="form-select" id="employment_type" name="employment_type">
                        <?php foreach (['Full-time', 'Part-time', 'Contract', 'Internship'] as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo jp_is_selected($type, jp_post_job_value('employment_type', $job, 'Full-time')); ?>><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="work_mode" class="form-label fw-semibold">Work Mode</label>
                    <select class="form-select" id="work_mode" name="work_mode">
                        <?php foreach (['On-site', 'Hybrid', 'Remote'] as $mode): ?>
                            <option value="<?php echo $mode; ?>" <?php echo jp_is_selected($mode, jp_post_job_value('work_mode', $job, 'On-site')); ?>><?php echo $mode; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="salary_min" class="form-label fw-semibold">Minimum Salary</label>
                    <input type="number" min="0" step="0.01" class="form-control" id="salary_min" name="salary_min" value="<?php echo htmlspecialchars(jp_post_job_value('salary_min', $job)); ?>">
                </div>
                <div class="col-md-4">
                    <label for="salary_max" class="form-label fw-semibold">Maximum Salary</label>
                    <input type="number" min="0" step="0.01" class="form-control" id="salary_max" name="salary_max" value="<?php echo htmlspecialchars(jp_post_job_value('salary_max', $job)); ?>">
                </div>
                <div class="col-md-4">
                    <label for="currency" class="form-label fw-semibold">Currency</label>
                    <input type="text" maxlength="3" class="form-control text-uppercase" id="currency" name="currency" value="<?php echo htmlspecialchars(jp_post_job_value('currency', $job, 'OMR')); ?>">
                </div>
            </div>

            <div class="mb-4">
                <label for="application_url" class="form-label fw-semibold">Application URL</label>
                <input type="url" class="form-control" id="application_url" name="application_url" value="<?php echo htmlspecialchars(jp_post_job_value('application_url', $job)); ?>">
            </div>

            <h2 class="h5 fw-bold border-bottom pb-2 mb-4 text-primary mt-5">3. Content & Publishing</h2>

            <div class="mb-3">
                <label for="short_description" class="form-label fw-semibold">Short Description <span class="text-danger">*</span></label>
                <textarea class="form-control" id="short_description" name="short_description" rows="2" required><?php echo htmlspecialchars(jp_post_job_value('short_description', $job)); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Full Job Description <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="6" required><?php echo htmlspecialchars(jp_post_job_value('description', $job)); ?></textarea>
            </div>

            <div class="mb-4">
                <label for="requirements" class="form-label fw-semibold">Requirements</label>
                <textarea class="form-control" id="requirements" name="requirements" rows="4"><?php echo htmlspecialchars(jp_post_job_value('requirements', $job)); ?></textarea>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="status" class="form-label fw-semibold">Publishing Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="draft" <?php echo jp_is_selected('draft', $statusValue); ?>>Draft</option>
                        <option value="published" <?php echo jp_is_selected('published', $statusValue); ?>>Published</option>
                        <option value="closed" <?php echo jp_is_selected('closed', $statusValue); ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="job_image" class="form-label fw-semibold">Cover Image</label>
                    <input type="file" class="form-control" id="job_image" name="job_image" accept="image/png,image/jpeg,image/gif,image/webp">
                    <div class="form-text">Optional. JPG, PNG, GIF, or WebP only. Maximum 2MB.</div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-5">
                <button type="submit" class="btn btn-success btn-lg fw-bold">
                    <i class="bi bi-cloud-arrow-up me-2"></i><?php echo $editJobId > 0 ? 'Save Listing' : 'Create Listing'; ?>
                </button>
                <a href="employer_panel.php" class="btn btn-outline-secondary btn-lg">
                    Cancel
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>

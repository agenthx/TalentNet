<?php
require_once 'auth_helper.php';
require_once 'db.php';
require_once 'upload_helper.php';

require_role('creator');

$currentUserId = (int)$_SESSION['user_id'];
$message = '';
$message_type = 'success';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save_employer') {
            $employerId = (int)($_POST['employer_id'] ?? 0);
            $companyName = trim($_POST['company_name'] ?? '');
            $companyWebsite = trim($_POST['company_website'] ?? '');
            $companyDescription = trim($_POST['company_description'] ?? '');

            if ($companyName === '') {
                $errors[] = "Company name is required.";
            }
            if ($companyDescription === '') {
                $errors[] = "Company description is required.";
            }
            if ($companyWebsite !== '' && !filter_var($companyWebsite, FILTER_VALIDATE_URL)) {
                $errors[] = "Company website must be a valid URL.";
            }

            if (empty($errors)) {
                if ($employerId > 0) {
                    $stmt = $conn->prepare("
                        UPDATE dbProj_employers
                        SET company_name = ?, company_website = ?, company_description = ?
                        WHERE employer_id = ? AND owner_user_id = ?
                    ");
                    if (!$stmt) {
                        throw new Exception("Database prepare error: " . $conn->error);
                    }
                    $stmt->bind_param("sssii", $companyName, $companyWebsite, $companyDescription, $employerId, $currentUserId);
                    $stmt->execute();
                    $stmt->close();
                    $savedEmployerId = $employerId;
                    $message = "Employer profile updated.";
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO dbProj_employers (owner_user_id, company_name, company_website, company_description)
                        VALUES (?, ?, ?, ?)
                    ");
                    if (!$stmt) {
                        throw new Exception("Database prepare error: " . $conn->error);
                    }
                    $stmt->bind_param("isss", $currentUserId, $companyName, $companyWebsite, $companyDescription);
                    $stmt->execute();
                    $savedEmployerId = $stmt->insert_id;
                    $stmt->close();
                    $message = "Employer profile created.";
                }

                $upload = jp_upload_image('logo_file', 'uploads/logos');
                if (!$upload['ok']) {
                    $message .= " Logo upload was skipped: " . $upload['message'];
                    $message_type = 'warning';
                } elseif ($upload['path']) {
                    $logoStmt = $conn->prepare("
                        UPDATE dbProj_employers
                        SET logo_path = ?
                        WHERE employer_id = ? AND owner_user_id = ?
                    ");
                    if (!$logoStmt) {
                        throw new Exception("Database prepare error: " . $conn->error);
                    }
                    $logoStmt->bind_param("sii", $upload['path'], $savedEmployerId, $currentUserId);
                    $logoStmt->execute();
                    $logoStmt->close();
                }
            }
        }

        if ($action === 'update_job_status') {
            $jobId = (int)($_POST['job_id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? '');

            if (!in_array($newStatus, ['draft', 'published', 'closed'], true)) {
                $errors[] = "Choose a valid listing status.";
            } else {
                $stmt = $conn->prepare("
                    UPDATE dbProj_job_listings j
                    INNER JOIN dbProj_employers e ON e.employer_id = j.employer_id
                    SET
                        j.status = ?,
                        j.published_at = CASE WHEN ? = 'published' THEN COALESCE(j.published_at, NOW()) ELSE NULL END
                    WHERE j.job_id = ? AND e.owner_user_id = ?
                ");
                if (!$stmt) {
                    throw new Exception("Database prepare error: " . $conn->error);
                }
                $stmt->bind_param("ssii", $newStatus, $newStatus, $jobId, $currentUserId);
                $stmt->execute();
                $message = $stmt->affected_rows > 0 ? "Listing status updated." : "No listing was changed.";
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

$employers = [];
$listings = [];
$stats = [
    'companies' => 0,
    'total_jobs' => 0,
    'published_jobs' => 0,
];

try {
    $empStmt = $conn->prepare("
        SELECT
            e.*,
            COUNT(j.job_id) AS listing_count,
            COALESCE(SUM(j.status = 'published'), 0) AS published_count
        FROM dbProj_employers e
        LEFT JOIN dbProj_job_listings j ON j.employer_id = e.employer_id
        WHERE e.owner_user_id = ?
        GROUP BY e.employer_id, e.owner_user_id, e.company_name, e.company_website, e.company_description, e.logo_path, e.created_at, e.updated_at
        ORDER BY e.company_name ASC
    ");
    if (!$empStmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    $empStmt->bind_param("i", $currentUserId);
    $empStmt->execute();
    $empResult = $empStmt->get_result();
    $employers = $empResult ? $empResult->fetch_all(MYSQLI_ASSOC) : [];
    $empStmt->close();

    $jobStmt = $conn->prepare("
        SELECT
            j.job_id,
            j.title,
            j.status,
            j.published_at,
            j.updated_at,
            e.company_name,
            c.category_name,
            COUNT(DISTINCT v.view_id) AS view_count,
            COALESCE(ROUND(AVG(r.rating_value), 1), 0) AS average_rating,
            COUNT(DISTINCT cm.comment_id) AS comment_count
        FROM dbProj_job_listings j
        INNER JOIN dbProj_employers e ON e.employer_id = j.employer_id
        INNER JOIN dbProj_job_categories c ON c.category_id = j.category_id
        LEFT JOIN dbProj_job_views v ON v.job_id = j.job_id
        LEFT JOIN dbProj_ratings r ON r.job_id = j.job_id
        LEFT JOIN dbProj_comments cm ON cm.job_id = j.job_id AND cm.is_removed = FALSE
        WHERE e.owner_user_id = ?
        GROUP BY j.job_id, j.title, j.status, j.published_at, j.updated_at, e.company_name, c.category_name
        ORDER BY j.updated_at DESC, j.created_at DESC
    ");
    if (!$jobStmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    $jobStmt->bind_param("i", $currentUserId);
    $jobStmt->execute();
    $jobResult = $jobStmt->get_result();
    $listings = $jobResult ? $jobResult->fetch_all(MYSQLI_ASSOC) : [];
    $jobStmt->close();

    $stats['companies'] = count($employers);
    $stats['total_jobs'] = count($listings);
    foreach ($listings as $listing) {
        if ($listing['status'] === 'published') {
            $stats['published_jobs']++;
        }
    }
} catch (Exception $e) {
    $errors[] = "Could not load employer panel data: " . $e->getMessage();
}

include 'header.php';
?>

<section class="section-hero mb-4">
    <div class="d-flex justify-content-between flex-wrap align-items-center gap-3">
        <div>
            <div class="hero-kicker"><i class="bi bi-card-list"></i> Employer workspace</div>
            <h1 class="h2 mb-2">Employer Panel</h1>
            <p class="lead mb-0">Manage company profiles, upload logos, and control your own job listings.</p>
        </div>
        <a href="post_job.php" class="btn btn-light">
            <i class="bi bi-plus-circle me-1"></i>Post a Job
        </a>
    </div>
</section>

<?php if ($message): ?>
    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show shadow-sm" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-danger shadow-sm">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icon"><i class="bi bi-building"></i></span>
                <div>
                    <div class="text-muted small">Company Profiles</div>
                    <div class="h4 mb-0 fw-bold"><?php echo (int)$stats['companies']; ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icon"><i class="bi bi-briefcase"></i></span>
                <div>
                    <div class="text-muted small">Total Listings</div>
                    <div class="h4 mb-0 fw-bold"><?php echo (int)$stats['total_jobs']; ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icon"><i class="bi bi-broadcast"></i></span>
                <div>
                    <div class="text-muted small">Published Listings</div>
                    <div class="h4 mb-0 fw-bold"><?php echo (int)$stats['published_jobs']; ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-lg-5">
        <div class="detail-card p-4 h-100">
            <h2 class="h5 fw-bold border-bottom pb-2 mb-4">Add Company Profile</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_employer">
                <input type="hidden" name="employer_id" value="0">

                <div class="mb-3">
                    <label for="company_name_new" class="form-label">Company Name</label>
                    <input type="text" id="company_name_new" name="company_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="company_website_new" class="form-label">Company Website</label>
                    <input type="url" id="company_website_new" name="company_website" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="company_description_new" class="form-label">Company Description</label>
                    <textarea id="company_description_new" name="company_description" rows="4" class="form-control" required></textarea>
                </div>

                <div class="mb-4">
                    <label for="logo_file_new" class="form-label">Company Logo</label>
                    <input type="file" id="logo_file_new" name="logo_file" class="form-control" accept="image/png,image/jpeg,image/gif,image/webp">
                    <div class="form-text">Optional. JPG, PNG, GIF, or WebP only. Maximum 2MB.</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-building-add me-1"></i>Save Profile
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <?php if (empty($employers)): ?>
            <div class="empty-state text-center p-5 h-100">
                <i class="bi bi-building-add display-6 d-block mb-3"></i>
                <h2 class="h5">No employer profile yet</h2>
                <p class="mb-0">Create a profile on the left, then use Post a Job to publish listings.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($employers as $employer): ?>
                    <div class="col-12">
                        <div class="detail-card p-4">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_employer">
                                <input type="hidden" name="employer_id" value="<?php echo (int)$employer['employer_id']; ?>">

                                <div class="d-flex gap-3 align-items-start mb-3">
                                    <div class="employer-logo-preview">
                                        <?php if (!empty($employer['logo_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($employer['logo_path']); ?>" alt="<?php echo htmlspecialchars($employer['company_name']); ?> logo" onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                            <span class="job-card-logo-fallback d-none"><i class="bi bi-building"></i></span>
                                        <?php else: ?>
                                            <span class="job-card-logo-fallback"><i class="bi bi-building"></i></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="company_name_<?php echo (int)$employer['employer_id']; ?>">Company Name</label>
                                                <input type="text" id="company_name_<?php echo (int)$employer['employer_id']; ?>" name="company_name" class="form-control" value="<?php echo htmlspecialchars($employer['company_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="company_website_<?php echo (int)$employer['employer_id']; ?>">Website</label>
                                                <input type="url" id="company_website_<?php echo (int)$employer['employer_id']; ?>" name="company_website" class="form-control" value="<?php echo htmlspecialchars($employer['company_website']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="company_description_<?php echo (int)$employer['employer_id']; ?>">Description</label>
                                    <textarea id="company_description_<?php echo (int)$employer['employer_id']; ?>" name="company_description" rows="3" class="form-control" required><?php echo htmlspecialchars($employer['company_description']); ?></textarea>
                                </div>

                                <div class="row g-3 align-items-end">
                                    <div class="col-md-7">
                                        <label class="form-label" for="logo_file_<?php echo (int)$employer['employer_id']; ?>">Replace Logo</label>
                                        <input type="file" id="logo_file_<?php echo (int)$employer['employer_id']; ?>" name="logo_file" class="form-control" accept="image/png,image/jpeg,image/gif,image/webp">
                                    </div>
                                    <div class="col-md-5 d-flex justify-content-md-end gap-2">
                                        <span class="badge text-bg-light border align-self-center">
                                            <?php echo (int)$employer['published_count']; ?> published / <?php echo (int)$employer['listing_count']; ?> total
                                        </span>
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="bi bi-save me-1"></i>Save
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1">My Job Listings</h2>
        <p class="text-muted mb-0">Edit content or change whether a listing is draft, published, or closed.</p>
    </div>
    <a href="post_job.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>New Listing
    </a>
</div>

<?php if (empty($listings)): ?>
    <div class="empty-state text-center p-5">
        <i class="bi bi-briefcase display-6 d-block mb-3"></i>
        <h2 class="h5">No job listings yet</h2>
        <p class="mb-4">Create your first listing once you have at least one company profile.</p>
        <a href="post_job.php" class="btn btn-primary">Post a Job</a>
    </div>
<?php else: ?>
    <div class="table-responsive table-card">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Company</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Activity</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listings as $listing): ?>
                    <tr>
                        <td class="fw-semibold"><?php echo htmlspecialchars($listing['title']); ?></td>
                        <td><?php echo htmlspecialchars($listing['company_name']); ?></td>
                        <td><?php echo htmlspecialchars($listing['category_name']); ?></td>
                        <td>
                            <span class="badge <?php echo $listing['status'] === 'published' ? 'bg-success' : ($listing['status'] === 'closed' ? 'bg-secondary' : 'bg-warning'); ?>">
                                <?php echo ucfirst(htmlspecialchars($listing['status'])); ?>
                            </span>
                        </td>
                        <td class="small text-muted">
                            <span class="me-2"><i class="bi bi-eye me-1"></i><?php echo (int)$listing['view_count']; ?></span>
                            <span class="me-2"><i class="bi bi-star-fill text-warning me-1"></i><?php echo number_format((float)$listing['average_rating'], 1); ?></span>
                            <span><i class="bi bi-chat-square-text me-1"></i><?php echo (int)$listing['comment_count']; ?></span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($listing['updated_at'])); ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-outline-primary" href="post_job.php?edit=<?php echo (int)$listing['job_id']; ?>">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </a>
                                <?php if ($listing['status'] === 'published'): ?>
                                    <a class="btn btn-sm btn-light" href="job_details.php?id=<?php echo (int)$listing['job_id']; ?>">
                                        <i class="bi bi-eye me-1"></i>View
                                    </a>
                                <?php endif; ?>
                                <form method="POST" class="listing-status-form">
                                    <input type="hidden" name="action" value="update_job_status">
                                    <input type="hidden" name="job_id" value="<?php echo (int)$listing['job_id']; ?>">
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="draft" <?php echo $listing['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="published" <?php echo $listing['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                        <option value="closed" <?php echo $listing['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Update</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>

<?php
require_once 'db.php';
require_once 'header.php';

// 1. Catch the ID from the URL securely
// If someone visits job_details.php without an ID, we default to 0 to prevent a crash
$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($jobId === 0) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Invalid Job Request.</div></div>";
    require_once 'footer.php';
    exit;
}

// 2. Fetch the job AND employer details using a JOIN
$sql = "SELECT j.*, e.company_name, e.company_website, e.company_description, e.logo_path 
        FROM dbProj_job_listings j
        JOIN dbProj_employers e ON j.employer_id = e.employer_id
        WHERE j.job_id = :id AND j.status = 'published'";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $jobId, PDO::PARAM_INT);
$stmt->execute();

$job = $stmt->fetch();

// 3. What if the user types ?id=999 in the URL and the job doesn't exist?
if (!$job) {
    echo "<div class='container mt-5'><div class='alert alert-warning'>Sorry, this job could not be found or has been removed.</div></div>";
    require_once 'footer.php';
    exit;
}
?>

<div class="row mt-4 mb-4">
    <div class="col-md-8">
        <h2 class="text-primary mb-3"><?= htmlspecialchars($job['title']) ?></h2>
        
        <div class="d-flex align-items-center mb-3">
            <span class="badge bg-primary me-2 px-3 py-2"><?= htmlspecialchars($job['employment_type']) ?></span>
            <span class="badge bg-secondary me-2 px-3 py-2"><?= htmlspecialchars($job['work_mode']) ?></span>
            <span class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
        </div>

        <h5 class="text-success fw-bold">
            <?= htmlspecialchars($job['salary_min']) ?> - <?= htmlspecialchars($job['salary_max']) ?> <?= htmlspecialchars($job['currency']) ?>
        </h5>
    </div>
    
    <div class="col-md-4 text-md-end">
        <div class="card bg-light border-0">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($job['company_name']) ?></h5>
                <p class="small text-muted mb-2"><?= htmlspecialchars($job['company_description']) ?></p>
                <a href="<?= htmlspecialchars($job['company_website']) ?>" target="_blank" class="btn btn-outline-dark btn-sm">Visit Website</a>
            </div>
        </div>
    </div>
</div>

<hr>

<div class="row mt-4">
    <div class="col-lg-8">
        <h4 class="mb-3">Job Description</h4>
        <div class="p-4 bg-white border rounded shadow-sm">
            <p style="white-space: pre-line;"><?= nl2br(htmlspecialchars($job['description'])) ?></p>
        </div>

        <?php if (!empty($job['application_url'])): ?>
            <div class="mt-4">
                <a href="<?= htmlspecialchars($job['application_url']) ?>" target="_blank" class="btn btn-success btn-lg px-5">Apply Now</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4 mt-4 mt-lg-0">
        <div class="card border-warning shadow-sm">
            <div class="card-header bg-warning text-dark fw-bold">
                ⚠️ M4 Workspace: Ratings & Comments
            </div>
            <div class="card-body bg-light">
                <p class="text-muted small">
                    <em>M4: Add your PHP/AJAX code here to query the `dbProj_ratings` and `dbProj_comments` tables for Job ID <strong><?= $jobId ?></strong>.</em>
                </p>
                <div class="alert alert-secondary text-center p-4 border-dashed">
                    [Star Rating UI Placeholder]
                </div>
                <div class="alert alert-secondary text-center p-4 border-dashed mt-3">
                    [Comment Form & List Placeholder]
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once 'footer.php'; 
?>
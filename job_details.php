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

<section class="page-hero compact mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <a href="index.php" class="btn btn-sm btn-light mb-3">
                <i class="bi bi-arrow-left me-1"></i>Back to jobs
            </a>
            <h1 class="display-6 fw-bold mb-3"><?= htmlspecialchars($job['title']) ?></h1>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge text-bg-light px-3 py-2"><?= htmlspecialchars($job['employment_type']) ?></span>
                <span class="badge text-bg-secondary px-3 py-2"><?= htmlspecialchars($job['work_mode']) ?></span>
                <span><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($job['location']) ?></span>
            </div>
        </div>
        <div class="col-lg-4 text-lg-end">
            <div class="hero-metric">
                <i class="bi bi-cash-coin"></i>
                <?= htmlspecialchars($job['salary_min']) ?> - <?= htmlspecialchars($job['salary_max']) ?> <?= htmlspecialchars($job['currency']) ?>
            </div>
        </div>
    </div>
</section>

<div class="row mt-4">
    <div class="col-lg-8">
        <div class="section-heading">
            <div>
                <h2 class="h4 fw-bold mb-1">Job Description</h2>
                <p>Details supplied by the employer.</p>
            </div>
        </div>
        <div class="job-detail-panel p-4">
            <p style="white-space: pre-line;"><?= nl2br(htmlspecialchars($job['description'])) ?></p>
        </div>

        <?php if (!empty($job['application_url'])): ?>
            <div class="mt-4">
                <a href="<?= htmlspecialchars($job['application_url']) ?>" target="_blank" class="btn btn-success btn-lg px-5">
                    Apply Now <i class="bi bi-box-arrow-up-right ms-1"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4 mt-4 mt-lg-0">
        <div class="card company-card mb-4">
            <div class="card-body p-4">
                <span class="eyebrow text-primary"><i class="bi bi-building"></i> Company</span>
                <h2 class="h5 card-title"><?= htmlspecialchars($job['company_name']) ?></h2>
                <p class="small text-muted mb-3"><?= htmlspecialchars($job['company_description']) ?></p>
                <a href="<?= htmlspecialchars($job['company_website']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                    Visit Website <i class="bi bi-box-arrow-up-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="card border-0">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-chat-square-heart me-1 text-warning"></i>M4 Workspace: Ratings & Comments
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    <em>M4: Add your PHP/AJAX code here to query the `dbProj_ratings` and `dbProj_comments` tables for Job ID <strong><?= $jobId ?></strong>.</em>
                </p>
                <div class="placeholder-panel text-center p-4">
                    <i class="bi bi-star fs-3 text-warning d-block mb-2"></i>
                    Star Rating UI Placeholder
                </div>
                <div class="placeholder-panel text-center p-4 mt-3">
                    <i class="bi bi-chat-left-text fs-3 text-primary d-block mb-2"></i>
                    Comment Form & List Placeholder
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once 'footer.php'; 
?>

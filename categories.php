<?php
require_once 'db.php';
require_once 'header.php';

// Fetch categories and count how many published jobs exist in each
$sql = "SELECT c.category_id, c.category_name, c.category_description, COUNT(j.job_id) as job_count
        FROM dbProj_job_categories c
        LEFT JOIN dbProj_job_listings j ON c.category_id = j.category_id AND j.status = 'published'
        GROUP BY c.category_id
        ORDER BY c.category_name ASC";

$stmt = $pdo->query($sql);
$categories = $stmt->fetchAll();
?>

<section class="page-hero compact mb-4">
    <div class="row align-items-center g-3">
        <div class="col-lg-8">
            <span class="eyebrow"><i class="bi bi-grid-3x3-gap"></i> Browse by field</span>
            <h1 class="h2 fw-bold mb-2">Job Categories</h1>
            <p class="mb-0">Browse opportunities by your area of expertise.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <a href="index.php" class="btn btn-light">
                <i class="bi bi-search me-1"></i>Search all jobs
            </a>
        </div>
    </div>
</section>

<div class="row">
    <?php if ($categories): ?>
        <?php foreach ($categories as $cat): ?>
            <div class="col-md-4 mb-4">
                <article class="card category-card h-100 border-0">
                    <div class="card-body text-center p-4">
                        <span class="category-icon mb-3">
                            <i class="bi bi-folder2-open"></i>
                        </span>
                        <h2 class="h4 card-title fw-bold text-dark mt-2 mb-3">
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </h2>
                        <p class="card-text text-muted mb-4">
                            <?= htmlspecialchars($cat['category_description']) ?>
                        </p>
                        <span class="badge text-bg-primary rounded-pill px-3 py-2">
                            <?= $cat['job_count'] ?> Active Jobs
                        </span>
                    </div>
                    <div class="card-footer bg-white border-0 text-center pb-4">
                        <a href="index.php?search_category=<?= $cat['category_id'] ?>" class="btn btn-outline-primary w-75">
                            Browse Jobs <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="empty-state p-4 text-center text-muted">
                <i class="bi bi-folder-x fs-2 d-block mb-2"></i>
                No categories found.
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
require_once 'footer.php'; 
?>

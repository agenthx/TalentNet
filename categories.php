<?php
require_once 'db.php';
require_once 'header.php';

// Fetch categories and count how many published jobs exist in each
$sql = "SELECT c.category_id, c.category_name, c.category_description, COUNT(j.job_id) as job_count
        FROM dbProj_job_categories c
        LEFT JOIN dbProj_job_listings j ON c.category_id = j.category_id AND j.status = 'published'
        GROUP BY c.category_id
        ORDER BY c.category_name ASC";

$result = $conn->query($sql);
$categories = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<section class="section-hero text-center mb-4">
    <div class="hero-kicker"><i class="bi bi-grid"></i> Browse by category</div>
    <h1 class="h2 mb-2">Job Categories</h1>
    <p class="lead mb-0">Browse opportunities by your area of expertise.</p>
</section>

<div class="row g-4">
    <?php if ($categories): ?>
        <?php foreach ($categories as $cat): ?>
            <div class="col-md-4">
                <div class="card category-card h-100">
                    <div class="card-body text-center p-4">
                        <span class="category-icon mb-3"><i class="bi bi-folder2-open"></i></span>
                        <h2 class="h5 fw-bold mb-3">
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </h2>
                        <p class="card-text text-muted mb-4">
                            <?= htmlspecialchars($cat['category_description']) ?>
                        </p>
                        <span class="badge text-bg-light border px-3 py-2">
                            <i class="bi bi-briefcase me-1"></i><?= $cat['job_count'] ?> Active Jobs
                        </span>
                    </div>
                    <div class="card-footer bg-white border-0 text-center p-4 pt-0">
                        <a href="index.php?search_category=<?= $cat['category_id'] ?>" class="btn btn-outline-primary w-100">
                            Browse Jobs <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="empty-state text-center p-5">
                <i class="bi bi-folder-x display-6 d-block mb-3"></i>
                <h2 class="h5">No categories found</h2>
                <p class="mb-0">Categories will appear here after they are added.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
require_once 'footer.php'; 
?>

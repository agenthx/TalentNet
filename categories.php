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

<div class="row mt-4 mb-3">
    <div class="col-12 text-center">
        <h2 class="text-primary fw-bold">Job Categories</h2>
        <p class="lead text-muted">Browse opportunities by your area of expertise.</p>
    </div>
</div>

<div class="row">
    <?php if ($categories): ?>
        <?php foreach ($categories as $cat): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm border-0 bg-light">
                    <div class="card-body text-center p-4">
                        <h4 class="card-title fw-bold text-dark mt-2 mb-3">
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </h4>
                        <p class="card-text text-muted mb-4">
                            <?= htmlspecialchars($cat['category_description']) ?>
                        </p>
                        <span class="badge bg-primary rounded-pill px-3 py-2 fs-6">
                            <?= $cat['job_count'] ?> Active Jobs
                        </span>
                    </div>
                    <div class="card-footer bg-light border-0 text-center pb-4">
                        <a href="index.php?search_category=<?= $cat['category_id'] ?>" class="btn btn-outline-primary w-75">
                            Browse Jobs
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-warning text-center">No categories found.</div>
        </div>
    <?php endif; ?>
</div>

<?php 
require_once 'footer.php'; 
?>
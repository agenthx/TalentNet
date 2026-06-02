<?php
require_once 'db.php';

function jp_valid_date($value) {
    return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
}

$catResult = $conn->query("SELECT category_id, category_name FROM dbProj_job_categories ORDER BY category_name ASC");
$formCategories = $catResult ? $catResult->fetch_all(MYSQLI_ASSOC) : [];

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$keyword = trim($_GET['search_keyword'] ?? '');
$employerName = trim($_GET['search_employer_name'] ?? '');
$categoryId = !empty($_GET['search_category']) ? (int)$_GET['search_category'] : 0;
$dateFrom = jp_valid_date($_GET['search_date_from'] ?? '') ? $_GET['search_date_from'] : '';
$dateTo = jp_valid_date($_GET['search_date_to'] ?? '') ? $_GET['search_date_to'] : '';
$sortBy = $_GET['sort_by'] ?? 'newest';
if (!in_array($sortBy, ['newest', 'popularity'], true)) {
    $sortBy = 'newest';
}

$fromSql = "
    FROM dbProj_job_listings j
    INNER JOIN dbProj_employers e ON e.employer_id = j.employer_id
    LEFT JOIN (
        SELECT job_id, MIN(file_path) AS file_path, MIN(alt_text) AS alt_text
        FROM dbProj_job_media
        WHERE is_primary = TRUE AND media_type = 'image'
        GROUP BY job_id
    ) m ON m.job_id = j.job_id
    WHERE j.status = 'published'
";

$conditions = [];
$params = [];
$types = "";

if ($keyword !== '') {
    $conditions[] = "MATCH(j.title, j.description) AGAINST(? IN NATURAL LANGUAGE MODE)";
    $params[] = $keyword;
    $types .= "s";
}

if ($employerName !== '') {
    $conditions[] = "e.company_name LIKE ?";
    $params[] = '%' . $employerName . '%';
    $types .= "s";
}

if ($categoryId > 0) {
    $conditions[] = "j.category_id = ?";
    $params[] = $categoryId;
    $types .= "i";
}

if ($dateFrom !== '') {
    $conditions[] = "j.published_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
    $types .= "s";
}

if ($dateTo !== '') {
    $conditions[] = "j.published_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
    $types .= "s";
}

if ($conditions) {
    $fromSql .= " AND " . implode(" AND ", $conditions);
}

$countSql = "SELECT COUNT(DISTINCT j.job_id) AS total " . $fromSql;
$totalJobs = 0;

$countStmt = $conn->prepare($countSql);
if ($countStmt) {
    if ($params) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalJobs = (int)($countResult->fetch_assoc()['total'] ?? 0);
    $countStmt->close();
}

$totalPages = max(1, (int)ceil($totalJobs / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $limit;

$sql = "
    SELECT
        j.job_id,
        j.title,
        j.short_description,
        j.location,
        j.employment_type,
        j.work_mode,
        j.published_at,
        e.company_name,
        e.logo_path,
        m.file_path AS primary_image_path,
        m.alt_text AS primary_image_alt,
        (SELECT COUNT(*) FROM dbProj_job_views v WHERE v.job_id = j.job_id) AS view_count,
        COALESCE((SELECT ROUND(AVG(r.rating_value), 1) FROM dbProj_ratings r WHERE r.job_id = j.job_id), 0) AS average_rating
    " . $fromSql;

if ($sortBy === 'popularity') {
    $sql .= " ORDER BY view_count DESC, average_rating DESC, j.published_at DESC";
} else {
    $sql .= " ORDER BY j.published_at DESC";
}

$sql .= " LIMIT ? OFFSET ?";

$mainParams = $params;
$mainParams[] = $limit;
$mainParams[] = $offset;
$mainTypes = $types . "ii";
$jobs = [];

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($mainTypes, ...$mainParams);
    $stmt->execute();
    $jobsResult = $stmt->get_result();
    $jobs = $jobsResult ? $jobsResult->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
}

$baseQuery = $_GET;
unset($baseQuery['page']);
$pageUrl = function($targetPage) use ($baseQuery) {
    $query = $baseQuery;
    $query['page'] = $targetPage;
    return 'index.php?' . http_build_query($query);
};

require_once 'header.php';
?>

<section class="page-hero mb-4">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="hero-kicker"><i class="bi bi-search"></i> Job portal search</div>
            <h1 class="display-5 mb-3">Find the right role faster.</h1>
            <p class="lead mb-0">Search published opportunities by title keyword, employer name, date range, popularity, and category.</p>
        </div>
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="soft-panel p-3 text-dark">
                <div class="d-flex align-items-center gap-3">
                    <span class="stat-icon"><i class="bi bi-shield-check"></i></span>
                    <div>
                        <div class="fw-bold">Verified project data</div>
                        <div class="small text-muted">FULLTEXT search, prepared filters, ratings, and view counts.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="search-panel mb-5">
    <div class="card">
        <div class="card-body p-4">
            <form method="GET" action="index.php">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label" for="search_keyword">Job Title Keyword</label>
                        <input type="text" id="search_keyword" name="search_keyword" class="form-control" placeholder="e.g. Developer, Designer" value="<?php echo htmlspecialchars($keyword); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="search_employer_name">Employer Name</label>
                        <input type="text" id="search_employer_name" name="search_employer_name" class="form-control" placeholder="e.g. Gulf Tech" value="<?php echo htmlspecialchars($employerName); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="search_category">Category</label>
                        <select id="search_category" name="search_category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($formCategories as $category): ?>
                                <option value="<?php echo (int)$category['category_id']; ?>" <?php echo $categoryId === (int)$category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="search_date_from">Posted From</label>
                        <input type="date" id="search_date_from" name="search_date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="search_date_to">Posted To</label>
                        <input type="date" id="search_date_to" name="search_date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="sort_by">Sort By</label>
                        <select id="sort_by" name="sort_by" class="form-select">
                            <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="popularity" <?php echo $sortBy === 'popularity' ? 'selected' : ''; ?>>Most Popular</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                        <a class="btn btn-outline-secondary" href="index.php" aria-label="Reset search filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1">Latest Jobs</h2>
        <p class="text-muted mb-0"><?php echo $totalJobs; ?> published listing<?php echo $totalJobs === 1 ? '' : 's'; ?> match the current filters.</p>
    </div>
</div>

<div class="row g-4">
    <?php if ($jobs): ?>
        <?php foreach ($jobs as $job): ?>
            <?php
            $imagePath = $job['primary_image_path'] ?: '';
            $imageAlt = $job['primary_image_alt'] ?: $job['title'];
            $logoPath = $job['logo_path'] ?: '';
            ?>
            <div class="col-md-6">
                <article class="card job-card h-100">
                    <div class="job-card-media">
                        <?php if ($imagePath): ?>
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($imageAlt); ?>" onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                        <?php endif; ?>
                        <div class="job-card-image-fallback <?php echo $imagePath ? 'd-none' : ''; ?>">
                            <i class="bi bi-briefcase"></i>
                        </div>
                        <div class="job-card-logo">
                            <?php if ($logoPath): ?>
                                <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?> logo" onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                <span class="job-card-logo-fallback d-none"><i class="bi bi-building"></i></span>
                            <?php else: ?>
                                <span class="job-card-logo-fallback"><i class="bi bi-building"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <span class="badge text-bg-light border"><?php echo htmlspecialchars($job['employment_type']); ?></span>
                            <small class="text-muted">
                                <i class="bi bi-calendar3 me-1"></i><?php echo date('M d, Y', strtotime($job['published_at'])); ?>
                            </small>
                        </div>
                        <h3 class="h5 card-title mb-2"><?php echo htmlspecialchars($job['title']); ?></h3>
                        <p class="fw-semibold text-muted small mb-3">
                            <i class="bi bi-building me-1"></i><?php echo htmlspecialchars($job['company_name']); ?>
                        </p>
                        <div class="job-meta mb-3">
                            <span><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($job['location']); ?></span>
                            <span><i class="bi bi-laptop me-1"></i><?php echo htmlspecialchars($job['work_mode']); ?></span>
                            <span><i class="bi bi-eye me-1"></i><?php echo (int)$job['view_count']; ?> views</span>
                            <span><i class="bi bi-star-fill text-warning me-1"></i><?php echo number_format((float)$job['average_rating'], 1); ?></span>
                        </div>
                        <p class="card-text text-muted mb-0">
                            <?php echo htmlspecialchars($job['short_description']); ?>
                        </p>
                    </div>
                    <div class="card-footer bg-white d-flex align-items-center justify-content-between p-4 pt-0 border-0">
                        <a href="job_details.php?id=<?php echo (int)$job['job_id']; ?>" class="btn btn-outline-primary btn-sm">
                            View More <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="empty-state text-center p-5">
                <i class="bi bi-search display-6 d-block mb-3"></i>
                <h3 class="h5">No jobs found</h3>
                <p class="mb-0">Try another keyword, employer name, category, or date range.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav aria-label="Job listings pagination" class="col-12 mt-4">
            <ul class="pagination justify-content-center flex-wrap gap-1">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo htmlspecialchars($pageUrl(max(1, $page - 1))); ?>">Previous</a>
                </li>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($page === $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo htmlspecialchars($pageUrl($i)); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo htmlspecialchars($pageUrl(min($totalPages, $page + 1))); ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

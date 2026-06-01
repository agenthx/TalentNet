<?php 
// Establish the database connection
require_once 'db.php';
// Include the layout header
require_once 'header.php'; 

// Fetch Employers
$empResult = $conn->query("SELECT employer_id, company_name FROM dbProj_employers ORDER BY company_name ASC");
$employers = $empResult ? $empResult->fetch_all(MYSQLI_ASSOC) : [];

// Fetch Categories
$catResult = $conn->query("SELECT category_id, category_name FROM dbProj_job_categories ORDER BY category_name ASC");
$formCategories = $catResult ? $catResult->fetch_all(MYSQLI_ASSOC) : [];
?>

<div class="row">
    <div class="col-12 text-center py-5">
        <h1 class="display-4">Find Your Dream Job</h1>
        <p class="lead">Search through top companies and exclusive opportunities.</p>
        
        <div class="card shadow-sm mt-4 text-start">
            <div class="card-body bg-light">
                <form method="GET" action="index.php">
                    <div class="row g-3">

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Keyword (Job Title or Description)</label>
                            <input type="text" name="search_keyword" class="form-control" placeholder="e.g. Developer, Designer..." value="<?= isset($_GET['search_keyword']) ? htmlspecialchars($_GET['search_keyword']) : '' ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Employer</label>
                            <select name="search_employer" class="form-select">
                                <option value="">All Employers</option>
                                <?php foreach ($employers as $emp): ?>
                                    <option value="<?= $emp['employer_id'] ?>" <?= (isset($_GET['search_employer']) && $_GET['search_employer'] == $emp['employer_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($emp['company_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Posted After</label>
                            <input type="date" name="search_date_from" class="form-control" value="<?= isset($_GET['search_date_from']) ? htmlspecialchars($_GET['search_date_from']) : '' ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Sort By</label>
                            <select name="sort_by" class="form-select">
                                <option value="newest" <?= (isset($_GET['sort_by']) && $_GET['sort_by'] == 'newest') ? 'selected' : '' ?>>Newest First</option>
                                <option value="popularity" <?= (isset($_GET['sort_by']) && $_GET['sort_by'] == 'popularity') ? 'selected' : '' ?>>Most Popular</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Category</label>
                            <select name="search_category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($formCategories as $c): ?>
                                    <option value="<?= $c['category_id'] ?>" <?= (isset($_GET['search_category']) && $_GET['search_category'] == $c['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12 mb-3">
        <h3>Latest Jobs</h3>
    </div>

<?php
    // --- PAGINATION & SEARCH SETUP ---
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    // 1. The Base Queries
    $sql = "SELECT * FROM dbProj_job_listings WHERE status = 'published'";
    $countSql = "SELECT COUNT(*) as total FROM dbProj_job_listings WHERE status = 'published'";

    // Arrays to hold dynamic MySQLi filters
    $conditions = [];
    $params = [];
    $types = ""; // String to hold data types (s = string, i = integer)

    // 2. Keyword Search (FULLTEXT Index)
    if (!empty($_GET['search_keyword'])) {
        $conditions[] = "MATCH(title, description) AGAINST(?)";
        $params[] = $_GET['search_keyword'];
        $types .= "s";
    }

    // 3. Employer Search
    if (!empty($_GET['search_employer'])) {
        $conditions[] = "employer_id = ?";
        $params[] = (int)$_GET['search_employer'];
        $types .= "i";
    }

    // Category Search (Catches the button click from categories.php)
    if (!empty($_GET['search_category'])) {
        $conditions[] = "category_id = ?";
        $params[] = (int)$_GET['search_category'];
        $types .= "i";
    }
    
    // 4. Date Search (Posted After)
    if (!empty($_GET['search_date_from'])) {
        $conditions[] = "published_at >= ?";
        $params[] = $_GET['search_date_from'] . ' 00:00:00';
        $types .= "s";
    }

    // 5. Append all conditions to the SQL strings
    if (count($conditions) > 0) {
        $whereClause = " AND " . implode(" AND ", $conditions);
        $sql .= $whereClause;
        $countSql .= $whereClause;
    }

    // 6. Sorting Logic (Newest vs Popularity)
    $sortBy = $_GET['sort_by'] ?? 'newest';
    if ($sortBy === 'popularity') {
        $sql .= " ORDER BY (SELECT COUNT(*) FROM dbProj_job_views WHERE job_id = dbProj_job_listings.job_id) DESC";
    } else {
        $sql .= " ORDER BY published_at DESC";
    }

    // Add final limits for pagination
    $sql .= " LIMIT ? OFFSET ?";

    // --- EXECUTE THE COUNT (For accurate page numbers) ---
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        // Use the splat operator (...) to unpack the dynamic parameters array
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalJobs = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalJobs / $limit);

    // --- EXECUTE THE MAIN SEARCH ---
    // We need a separate array for the main query because it includes the LIMIT and OFFSET integers
    $mainParams = $params;
    $mainParams[] = $limit;
    $mainParams[] = $offset;
    $mainTypes = $types . "ii"; // Add two integers for limit and offset

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($mainTypes, ...$mainParams);
    $stmt->execute();
    
    $jobsResult = $stmt->get_result();
    $jobs = $jobsResult ? $jobsResult->fetch_all(MYSQLI_ASSOC) : [];
    
    // Loop through the array to generate Bootstrap cards
    if ($jobs): 
        foreach ($jobs as $job): 
    ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-primary"><?= htmlspecialchars($job['title']) ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            <span class="badge bg-secondary"><?= htmlspecialchars($job['employment_type']) ?></span>
                            <?= htmlspecialchars($job['location']) ?>
                        </h6>
                        <p class="card-text mt-3">
                            <?= htmlspecialchars($job['short_description']) ?>
                        </p>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <a href="job_details.php?id=<?= $job['job_id'] ?>" class="btn btn-outline-primary btn-sm">View More</a>
                        <small class="text-muted float-end mt-1">
                            Posted: <?= date('M d, Y', strtotime($job['published_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
    <?php 
        endforeach; 
    else: 
    ?>
        <div class="col-12">
            <div class="alert alert-info">No jobs found. Check back later!</div>
        </div>
    <?php endif; ?>
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Job listings pagination" class="mt-5">
            <ul class="pagination justify-content-center">
                
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                </li>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                </li>
                
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php 
// Include the layout footer
require_once 'footer.php'; 
?>
<?php
/*
 * Generates 2 administrative reports using stored procedures
 * Report 1 is the most popular jobs within a date range
 * Report 2 is all jobs posted by a specific employer
 */

require_once 'auth_helper.php';
require_once 'db.php';

// Security check
require_role('admin');


$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$popular_jobs = [];

try {
    $stmt = $pdo->prepare("CALL dbProj_get_top_rated_jobs(:start_date, :end_date, NULL, 10)");
    $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    $popular_jobs = $stmt->fetchAll();

    $stmt->closeCursor();
} catch (PDOException $e) {
    $error_msg = "Error fetching popular jobs: " . $e->getMessage();
}


$employer_id = (int)($_GET['employer_id'] ?? 0);
$employer_jobs = [];
$employers = [];

try {
    // List employers for the dropdown
    $employers = $pdo->query("SELECT employer_id, company_name FROM dbProj_employers ORDER BY company_name ASC")->fetchAll();

    if ($employer_id > 0) {
        // Find the owner user ID for the employer
        $stmt = $pdo->prepare("SELECT owner_user_id FROM dbProj_employers WHERE employer_id = :id");
        $stmt->execute(['id' => $employer_id]);
        $owner_id = $stmt->fetchColumn();
        
        if ($owner_id) {
            // Call the stored procedure for jobs by the creator
            $stmt = $pdo->prepare("CALL dbProj_get_jobs_by_creator(:owner_id)");
            $stmt->execute(['owner_id' => $owner_id]);
            $employer_jobs = $stmt->fetchAll();
            $stmt->closeCursor();
        }
    }
} catch (PDOException $e) {
    $error_msg = ($error_msg ?? '') . " Error fetching employer jobs: " . $e->getMessage();
}

include 'header.php';
?>

<div class="admin-topbar d-flex justify-content-between flex-wrap gap-3 align-items-center mb-4">
    <div>
        <span class="eyebrow text-primary"><i class="bi bi-bar-chart-line"></i> Reporting</span>
        <h1 class="h2 fw-bold mb-1">Admin Reports</h1>
        <p class="text-muted mb-0">Review popular listings and employer posting activity.</p>
    </div>
    <a href="admin_dashboard.php" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
    </a>
</div>

<?php if (isset($error_msg)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<!-- Report 1 -->
<div class="card report-card mb-4 border-0">
    <div class="card-header py-3">
        <h2 class="h5 mb-0"><i class="bi bi-stars me-1 text-warning"></i>Most Popular Jobs</h2>
    </div>
    <div class="card-body p-4">
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-arrow-repeat me-1"></i>Generate Report
                </button>
            </div>
        </form>

        <div class="table-shell">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Avg Rating</th>
                        <th>Total Ratings</th>
                        <th>Total Views</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($popular_jobs)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No data found</td></tr>
                    <?php else: ?>
                        <?php foreach ($popular_jobs as $job): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($job['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                            <td>
                                <i class="bi bi-star-fill text-warning"></i>
                                <?php echo number_format($job['avg_rating'], 1); ?>
                            </td>
                            <td><?php echo $job['rating_count']; ?></td>
                            <td><?php echo $job['view_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>
</div>

<!-- Report 2 -->
<div class="card report-card border-0">
    <div class="card-header py-3">
        <h2 class="h5 mb-0"><i class="bi bi-building me-1 text-primary"></i>Jobs by Employer</h2>
    </div>
    <div class="card-body p-4">
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-8">
                <label for="employer_id" class="form-label">Select Employer</label>
                <select class="form-select" id="employer_id" name="employer_id">
                    <option value="0">-- Choose an Employer --</option>
                    <?php foreach ($employers as $emp): ?>
                        <option value="<?php echo $emp['employer_id']; ?>" <?php echo $employer_id == $emp['employer_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['company_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i>Filter Jobs
                </button>
            </div>
        </form>

        <div class="table-shell">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Avg Rating</th>
                        <th>Comments</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($employer_id == 0): ?>
                        <tr><td colspan="5" class="text-center text-muted">Please select an employer above.</td></tr>
                    <?php elseif (empty($employer_jobs)): ?>
                        <tr><td colspan="5" class="text-center text-muted">This employer hasn't posted any jobs yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($employer_jobs as $job): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($job['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($job['category_name']); ?></td>
                            <td>
                                <span class="badge <?php echo $job['status'] === 'published' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($job['avg_rating'], 1); ?></td>
                            <td><?php echo $job['visible_comment_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

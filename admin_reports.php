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

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Admin Reports</h1>
    <a href="admin_dashboard.php" class="btn btn-sm btn-outline-secondary">Back to Dashboard</a>
</div>

<?php if (isset($error_msg)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<!-- Report 1 -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0">Most Popular Jobs</h5>
    </div>
    <div class="card-body">
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
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>

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
                                <span class="text-warning">★</span> 
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

<!-- Report 2 -->
<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0">Jobs by Employer</h5>
    </div>
    <div class="card-body">
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
                <button type="submit" class="btn btn-primary w-100">Filter Jobs</button>
            </div>
        </form>

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

<?php include 'footer.php'; ?>

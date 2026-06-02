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
    // MySQLi: Use ? placeholders for the stored procedure
    $stmt = $conn->prepare("CALL dbProj_get_top_rated_jobs(?, ?, NULL, 10)");
    if (!$stmt) throw new Exception("Database prepare error: " . $conn->error);
    
    // "ss" = string (start_date), string (end_date)
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    
    $result = $stmt->get_result();
    if ($result) {
        $popular_jobs = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
    
    // CRITICAL FIX FOR MYSQLI: Flush remaining results from the Stored Procedure
    while ($conn->more_results() && $conn->next_result()) {
        if ($extraResult = $conn->store_result()) {
            $extraResult->free();
        }
    }

} catch (Exception $e) {
    $error_msg = "Error fetching popular jobs: " . $e->getMessage();
}


$employer_id = (int)($_GET['employer_id'] ?? 0);
$employer_jobs = [];
$employers = [];

try {
    // List employers for the dropdown
    $empResult = $conn->query("SELECT employer_id, company_name FROM dbProj_employers ORDER BY company_name ASC");
    if (!$empResult) throw new Exception($conn->error);
    $employers = $empResult->fetch_all(MYSQLI_ASSOC);

    if ($employer_id > 0) {
        // Find the owner user ID for the employer
        $stmt = $conn->prepare("SELECT owner_user_id FROM dbProj_employers WHERE employer_id = ?");
        if (!$stmt) throw new Exception("Database prepare error: " . $conn->error);
        
        $stmt->bind_param("i", $employer_id);
        $stmt->execute();
        $ownerResult = $stmt->get_result();
        $ownerRow = $ownerResult->fetch_assoc();
        $stmt->close();
        
        $owner_id = $ownerRow ? $ownerRow['owner_user_id'] : null;
        
        if ($owner_id) {
            // Call the stored procedure for jobs by the creator
            $stmt = $conn->prepare("CALL dbProj_get_jobs_by_creator(?)");
            if (!$stmt) throw new Exception("Database prepare error: " . $conn->error);
            
            $stmt->bind_param("i", $owner_id);
            $stmt->execute();
            
            $jobsResult = $stmt->get_result();
            if ($jobsResult) {
                $employer_jobs = $jobsResult->fetch_all(MYSQLI_ASSOC);
            }
            $stmt->close();
            
            // CRITICAL FIX FOR MYSQLI: Flush remaining results from the Stored Procedure
            while ($conn->more_results() && $conn->next_result()) {
                if ($extraResult = $conn->store_result()) {
                    $extraResult->free();
                }
            }
        }
    }
} catch (Exception $e) {
    $error_msg = ($error_msg ?? '') . " Error fetching employer jobs: " . $e->getMessage();
}

include 'header.php';
?>

<section class="section-hero mb-4">
    <div class="d-flex justify-content-between flex-wrap align-items-center gap-3">
        <div>
            <div class="hero-kicker"><i class="bi bi-bar-chart-line"></i> Reports</div>
            <h1 class="h2 mb-2">Admin Reports</h1>
            <p class="lead mb-0">Review popularity by date range and listings by employer.</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>
</section>

<?php if (isset($error_msg)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0"><i class="bi bi-star-fill text-warning me-2"></i>Most Popular Jobs</h2>
    </div>
    <div class="card-body p-4">
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i>Generate Report
                </button>
            </div>
        </form>

        <div class="table-responsive table-card">
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
                        <tr><td colspan="5" class="text-center text-muted py-4">No data found</td></tr>
                    <?php else: ?>
                        <?php foreach ($popular_jobs as $job): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($job['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                            <td>
                                <span class="text-warning"><i class="bi bi-star-fill"></i></span> 
                                <?php echo number_format($job['average_rating'] ?? 0, 1); ?>
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

<div class="card">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0"><i class="bi bi-building me-2 text-primary"></i>Jobs by Employer</h2>
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

        <div class="table-responsive table-card">
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
                        <tr><td colspan="5" class="text-center text-muted py-4">Please select an employer above.</td></tr>
                    <?php elseif (empty($employer_jobs)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">This employer hasn't posted any jobs yet.</td></tr>
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
                            <td><?php echo number_format($job['average_rating'] ?? 0, 1); ?></td>
                            <td><?php echo $job['comment_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

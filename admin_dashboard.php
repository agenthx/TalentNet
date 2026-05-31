<?php
/**
 * Admin dashboard in where users is managed, job listings and comments
 * It uses auth_helper to ensure only admins can access this page
 */

require_once 'auth_helper.php';
require_once 'db.php';

// Security check
require_role('admin');


$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action === 'toggle_user_status' && isset($_POST['user_id'])) {
            $uid = (int)$_POST['user_id'];
            $new_status = (int)$_POST['status'];
            
            // Prevent admin from deactivating themselves
            if ($uid == $_SESSION['user_id']) {
                $message = "You cannot deactivate your own account!";
                $message_type = 'danger';
            } else {
                $stmt = $pdo->prepare("UPDATE dbProj_users SET is_active = :status WHERE user_id = :id");
                $stmt->execute(['status' => $new_status, 'id' => $uid]);
                $message = "User status updated successfully.";
            }
        }
        
        if ($action === 'remove_job' && isset($_POST['job_id'])) {
            $jid = (int)$_POST['job_id'];
            $stmt = $pdo->prepare("UPDATE dbProj_job_listings SET status = 'removed' WHERE job_id = :id");
            $stmt->execute(['id' => $jid]);
            $message = "Job listing has been removed from public view.";
        }
        
        if ($action === 'delete_comment' && isset($_POST['comment_id'])) {
            $cid = (int)$_POST['comment_id'];
            // Delete the comment as per DB schema
            $stmt = $pdo->prepare("
                UPDATE dbProj_comments 
                SET is_removed = TRUE, 
                    removed_by_user_id = :admin_id, 
                    removed_reason = 'Removed by administrator' 
                WHERE comment_id = :id
            ");
            $stmt->execute(['admin_id' => $_SESSION['user_id'], 'id' => $cid]);
            $message = "Comment has been hidden.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Fetch Data for the Dashboard
try {
    // Users
    $users = $pdo->query("
        SELECT u.*, r.role_name 
        FROM dbProj_users u 
        JOIN dbProj_roles r ON u.role_id = r.role_id 
        ORDER BY u.created_at DESC
    ")->fetchAll();

    // Active Job Listings
    $jobs = $pdo->query("
        SELECT j.job_id, j.title, e.company_name, j.status, j.created_at 
        FROM dbProj_job_listings j
        JOIN dbProj_employers e ON j.employer_id = e.employer_id
        WHERE j.status != 'removed'
        ORDER BY j.created_at DESC
    ")->fetchAll();

    // Recent Comments
    $comments = $pdo->query("
        SELECT c.comment_id, c.comment_text, u.full_name, j.title as job_title, c.is_removed
        FROM dbProj_comments c
        JOIN dbProj_users u ON c.user_id = u.user_id
        JOIN dbProj_job_listings j ON c.job_id = j.job_id
        ORDER BY c.created_at DESC
        LIMIT 10
    ")->fetchAll();

} catch (PDOException $e) {
    die("Error loading dashboard data: " . $e->getMessage());
}

$total_users = count($users);
$active_users = 0;
foreach ($users as $user) {
    if ($user['is_active']) {
        $active_users++;
    }
}

$visible_jobs = count($jobs);
$visible_comments = 0;
foreach ($comments as $comment) {
    if (!$comment['is_removed']) {
        $visible_comments++;
    }
}

include 'header.php';
?>

<div class="admin-topbar d-flex justify-content-between flex-wrap gap-3 align-items-center mb-4">
    <div>
        <span class="eyebrow text-primary"><i class="bi bi-speedometer2"></i> Admin workspace</span>
        <h1 class="h2 fw-bold mb-1">Admin Dashboard</h1>
        <p class="text-muted mb-0">Manage users, job listings, and recent comments.</p>
    </div>
    <div>
        <a href="admin_reports.php" class="btn btn-outline-primary">
            <i class="bi bi-bar-chart-line me-1"></i>View Reports
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="metric-card">
            <div class="text-muted small fw-bold text-uppercase">Users</div>
            <div class="metric-value"><?php echo $total_users; ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="metric-card">
            <div class="text-muted small fw-bold text-uppercase">Active Users</div>
            <div class="metric-value"><?php echo $active_users; ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="metric-card">
            <div class="text-muted small fw-bold text-uppercase">Visible Jobs</div>
            <div class="metric-value"><?php echo $visible_jobs; ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="metric-card">
            <div class="text-muted small fw-bold text-uppercase">Visible Comments</div>
            <div class="metric-value"><?php echo $visible_comments; ?></div>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<ul class="nav admin-tabs mb-4" id="adminTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
            <i class="bi bi-people me-1"></i>Users
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="jobs-tab" data-bs-toggle="tab" data-bs-target="#jobs" type="button" role="tab">
            <i class="bi bi-briefcase me-1"></i>Job Listings
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab">
            <i class="bi bi-chat-left-text me-1"></i>Comments
        </button>
    </li>
</ul>

<div class="tab-content" id="adminTabsContent">
    <!-- Users Management Tab -->
    <div class="tab-pane fade show active" id="users" role="tabpanel">
        <div class="table-shell">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="badge bg-info text-dark"><?php echo ucfirst($user['role_name']); ?></span></td>
                        <td>
                            <?php if ($user['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_user_status">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                <button type="submit" class="btn btn-sm <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                    <i class="bi <?php echo $user['is_active'] ? 'bi-pause-circle' : 'bi-check-circle'; ?> me-1"></i>
                                    <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>

    <!-- Job Listings Tab -->
    <div class="tab-pane fade" id="jobs" role="tabpanel">
        <div class="table-shell">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Employer</th>
                        <th>Status</th>
                        <th>Posted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($job['title']); ?></td>
                        <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                        <td>
                            <span class="badge <?php echo $job['status'] === 'published' ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo ucfirst($job['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to remove this listing?');">
                                <input type="hidden" name="action" value="remove_job">
                                <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash me-1"></i>Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>

    <!-- Comments Tab -->
    <div class="tab-pane fade" id="comments" role="tabpanel">
        <div class="table-shell">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Job</th>
                        <th>Comment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($comment['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($comment['job_title']); ?></td>
                        <td><small><?php echo htmlspecialchars(substr($comment['comment_text'], 0, 50)) . '...'; ?></small></td>
                        <td>
                            <?php if ($comment['is_removed']): ?>
                                <span class="badge bg-danger">Hidden</span>
                            <?php else: ?>
                                <span class="badge bg-success">Visible</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$comment['is_removed']): ?>
                                <form method="POST" onsubmit="return confirm('Delete this comment?');">
                                    <input type="hidden" name="action" value="delete_comment">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-eye-slash me-1"></i>Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

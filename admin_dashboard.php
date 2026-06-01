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
                $stmt = $conn->prepare("UPDATE dbProj_users SET is_active = ? WHERE user_id = ?");
                if (!$stmt) throw new Exception("Database prepare error: " . $conn->error);
                
                $stmt->bind_param("ii", $new_status, $uid);
                $stmt->execute();
                $message = "User status updated successfully.";
            }
        }
        
        if ($action === 'remove_job' && isset($_POST['job_id'])) {
            $jid = (int)$_POST['job_id'];
            $stmt = $conn->prepare("UPDATE dbProj_job_listings SET status = 'removed' WHERE job_id = ?");
            if (!$stmt) throw new Exception("Database prepare error: " . $conn->error);
            
            $stmt->bind_param("i", $jid);
            $stmt->execute();
            $message = "Job listing has been removed from public view.";
        }
        
        if ($action === 'delete_comment' && isset($_POST['comment_id'])) {
            $cid = (int)$_POST['comment_id'];
            // Delete the comment as per DB schema
            $stmt = $conn->prepare("
                UPDATE dbProj_comments 
                SET is_removed = TRUE, 
                    removed_by_user_id = ?, 
                    removed_reason = 'Removed by administrator' 
                WHERE comment_id = ?
            ");
            if (!$stmt) throw new Exception("Database prepare error: " . $conn->error);
            
            $admin_id = (int)$_SESSION['user_id'];
            $stmt->bind_param("ii", $admin_id, $cid);
            $stmt->execute();
            $message = "Comment has been hidden.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Fetch Data for the Dashboard
try {
    // Users
    $userResult = $conn->query("
        SELECT u.*, r.role_name 
        FROM dbProj_users u 
        JOIN dbProj_roles r ON u.role_id = r.role_id 
        ORDER BY u.created_at DESC
    ");
    if (!$userResult) throw new Exception($conn->error);
    $users = $userResult->fetch_all(MYSQLI_ASSOC);

    // Active Job Listings
    $jobResult = $conn->query("
        SELECT j.job_id, j.title, e.company_name, j.status, j.created_at 
        FROM dbProj_job_listings j
        JOIN dbProj_employers e ON j.employer_id = e.employer_id
        WHERE j.status != 'removed'
        ORDER BY j.created_at DESC
    ");
    if (!$jobResult) throw new Exception($conn->error);
    $jobs = $jobResult->fetch_all(MYSQLI_ASSOC);

    // Recent Comments
    $commentResult = $conn->query("
        SELECT c.comment_id, c.comment_text, u.full_name, j.title as job_title, c.is_removed
        FROM dbProj_comments c
        JOIN dbProj_users u ON c.user_id = u.user_id
        JOIN dbProj_job_listings j ON c.job_id = j.job_id
        ORDER BY c.created_at DESC
        LIMIT 10
    ");
    if (!$commentResult) throw new Exception($conn->error);
    $comments = $commentResult->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    die("Error loading dashboard data: " . $e->getMessage());
}

include 'header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Admin Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="admin_reports.php" class="btn btn-sm btn-outline-secondary">View Reports</a>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">Users</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="jobs-tab" data-bs-toggle="tab" data-bs-target="#jobs" type="button" role="tab">Job Listings</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab">Comments</button>
    </li>
</ul>

<div class="tab-content" id="adminTabsContent">
    <div class="tab-pane fade show active" id="users" role="tabpanel">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
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

    <div class="tab-pane fade" id="jobs" role="tabpanel">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
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
                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="comments" role="tabpanel">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
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
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
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

<?php include 'footer.php'; ?>
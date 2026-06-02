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
    ");
    if (!$commentResult) throw new Exception($conn->error);
    $comments = $commentResult->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    die("Error loading dashboard data: " . $e->getMessage());
}

include 'header.php';
?>

<!-- ── Confirmation Modals ─────────────────────────────────────────── -->

<!-- Remove Job Modal -->
<div class="modal fade" id="modalRemoveJob" tabindex="-1" aria-labelledby="modalRemoveJobLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="rounded-circle bg-danger bg-opacity-10 p-2 me-2">
                    <i class="bi bi-trash3 text-danger fs-5"></i>
                </div>
                <h5 class="modal-title" id="modalRemoveJobLabel">Remove this listing?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-muted">
                This will hide the job listing from public view. The employer can resubmit it for review if needed.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="formRemoveJob">
                    <input type="hidden" name="action" value="remove_job">
                    <input type="hidden" name="job_id" id="removeJobId">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3 me-1"></i>Remove listing
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Hide Comment Modal -->
<div class="modal fade" id="modalHideComment" tabindex="-1" aria-labelledby="modalHideCommentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="rounded-circle bg-danger bg-opacity-10 p-2 me-2">
                    <i class="bi bi-eye-slash text-danger fs-5"></i>
                </div>
                <h5 class="modal-title" id="modalHideCommentLabel">Hide this comment?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-muted">
                The comment will be hidden from public view and marked as removed by an administrator.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="formHideComment">
                    <input type="hidden" name="action" value="delete_comment">
                    <input type="hidden" name="comment_id" id="hideCommentId">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-eye-slash me-1"></i>Hide comment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toggle User Status Modal -->
<div class="modal fade" id="modalToggleUser" tabindex="-1" aria-labelledby="modalToggleUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="rounded-circle bg-warning bg-opacity-10 p-2 me-2">
                    <i class="bi bi-person-dash text-warning fs-5" id="toggleUserIcon"></i>
                </div>
                <h5 class="modal-title" id="modalToggleUserLabel">Deactivate this account?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-muted" id="toggleUserBody">
                The user will lose access to their account. You can reactivate them at any time from this dashboard.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="formToggleUser">
                    <input type="hidden" name="action" value="toggle_user_status">
                    <input type="hidden" name="user_id" id="toggleUserId">
                    <input type="hidden" name="status" id="toggleUserStatus">
                    <button type="submit" class="btn btn-warning" id="toggleUserBtn">
                        <i class="bi bi-pause-circle me-1" id="toggleUserBtnIcon"></i>
                        <span id="toggleUserBtnLabel">Deactivate</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── Page Content ────────────────────────────────────────────────── -->

<section class="section-hero mb-4">
    <div class="d-flex justify-content-between flex-wrap align-items-center gap-3">
        <div>
            <div class="hero-kicker"><i class="bi bi-speedometer2"></i> Administrator</div>
            <h1 class="h2 mb-2">Admin Dashboard</h1>
            <p class="lead mb-0">Manage users, published listings, and recent comments.</p>
        </div>
        <a href="admin_reports.php" class="btn btn-light">
            <i class="bi bi-bar-chart-line me-1"></i>View Reports
        </a>
    </div>
</section>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icon"><i class="bi bi-people"></i></span>
                <div>
                    <div class="text-muted small">Users</div>
                    <div class="h4 mb-0 fw-bold"><?php echo count($users); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icon"><i class="bi bi-briefcase"></i></span>
                <div>
                    <div class="text-muted small">Active Listings</div>
                    <div class="h4 mb-0 fw-bold"><?php echo count($jobs); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icon"><i class="bi bi-chat-square-text"></i></span>
                <div>
                    <div class="text-muted small">Recent Comments</div>
                    <div class="h4 mb-0 fw-bold"><?php echo count($comments); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

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
        <div class="table-responsive table-card">
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
                            <button type="button"
                                class="btn btn-sm <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#modalToggleUser"
                                data-user-id="<?php echo $user['user_id']; ?>"
                                data-new-status="<?php echo $user['is_active'] ? 0 : 1; ?>"
                                data-is-active="<?php echo (int)$user['is_active']; ?>"
                                data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>">
                                <i class="bi <?php echo $user['is_active'] ? 'bi-pause-circle' : 'bi-check-circle'; ?> me-1"></i><?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="jobs" role="tabpanel">
        <div class="table-responsive table-card">
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
                            <button type="button"
                                class="btn btn-sm btn-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#modalRemoveJob"
                                data-job-id="<?php echo $job['job_id']; ?>">
                                <i class="bi bi-trash3 me-1"></i>Remove
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="comments" role="tabpanel">
        <div class="table-responsive table-card">
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
                                <button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalHideComment"
                                    data-comment-id="<?php echo $comment['comment_id']; ?>">
                                    <i class="bi bi-eye-slash me-1"></i>Hide
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Populate Remove Job modal
document.getElementById('modalRemoveJob').addEventListener('show.bs.modal', function (e) {
    document.getElementById('removeJobId').value = e.relatedTarget.dataset.jobId;
});

// Populate Hide Comment modal
document.getElementById('modalHideComment').addEventListener('show.bs.modal', function (e) {
    document.getElementById('hideCommentId').value = e.relatedTarget.dataset.commentId;
});

// Populate Toggle User modal — adjusts copy for activate vs deactivate
document.getElementById('modalToggleUser').addEventListener('show.bs.modal', function (e) {
    const btn      = e.relatedTarget;
    const isActive = btn.dataset.isActive === '1';
    const name     = btn.dataset.userName;

    document.getElementById('toggleUserId').value     = btn.dataset.userId;
    document.getElementById('toggleUserStatus').value = btn.dataset.newStatus;

    const title    = document.getElementById('modalToggleUserLabel');
    const body     = document.getElementById('toggleUserBody');
    const icon     = document.getElementById('toggleUserIcon');
    const btnEl    = document.getElementById('toggleUserBtn');
    const btnIcon  = document.getElementById('toggleUserBtnIcon');
    const btnLabel = document.getElementById('toggleUserBtnLabel');

    if (isActive) {
        title.textContent    = 'Deactivate ' + name + '?';
        body.textContent     = 'This user will lose access to their account immediately. You can reactivate them at any time from this dashboard.';
        icon.className       = 'bi bi-person-dash text-warning fs-5';
        btnEl.className      = 'btn btn-warning';
        btnIcon.className    = 'bi bi-pause-circle me-1';
        btnLabel.textContent = 'Deactivate';
    } else {
        title.textContent    = 'Activate ' + name + '?';
        body.textContent     = 'This will restore access to their account and allow them to log in again.';
        icon.className       = 'bi bi-person-check text-success fs-5';
        btnEl.className      = 'btn btn-success';
        btnIcon.className    = 'bi bi-check-circle me-1';
        btnLabel.textContent = 'Activate';
    }
});
</script>

<?php include 'footer.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php'; 

$job_id = isset($_GET['id']) ? intval($_GET['id']) : 14; 
$is_logged_in = isset($_SESSION['user_id']);

$job = null;
try {
    // FIX: Added a LEFT JOIN to the dbProj_employers table to pull the actual company_name
    $job_query = $conn->prepare("
        SELECT j.*, e.company_name 
        FROM dbProj_job_listings j
        LEFT JOIN dbProj_employers e ON j.employer_id = e.employer_id
        WHERE j.job_id = ?
    ");
    
    if ($job_query) {
        $job_query->bind_param("i", $job_id);
        $job_query->execute();
        $result = $job_query->get_result();
        $job = $result->fetch_assoc();
        $job_query->close();
    }
} catch (Exception $e) {
    // Silently catch errors to allow the placeholder block to load if the DB fails
}

// Placeholder fallback for testing
if (!$job) {
    $job = [
        'title' => 'Senior Full-Stack Web Developer',
        'company_name' => 'TechSolutions International',
        'location' => 'Remote / Hybrid',
        'salary' => '$95,000 - $120,000 / year',
        'type' => 'Full-time',
        'description' => 'We are seeking an ambitious Full-Stack Developer to join our core engineering squad. You will focus on building high-performance web applications, designing reliable database structures, and working closely with design modules to deliver pristine user interfaces.',
        'requirements' => 'Minimum 3+ years of web development experience. Deep understanding of raw PHP, asynchronous JavaScript (jQuery/AJAX), Bootstrap layout architectures, and clean MySQL relational queries.'
    ];
}

// Fetch Ratings
$avg_rating = 0;
$total_ratings = 0;
try {
    $rating_query = $conn->prepare("SELECT AVG(rating_value) as avg_rate, COUNT(rating_id) as total_rates FROM dbProj_ratings WHERE job_id = ?");
    if ($rating_query) {
        $rating_query->bind_param("i", $job_id);
        $rating_query->execute();
        $rating_result = $rating_query->get_result();
        $rating_data = $rating_result->fetch_assoc();
        
        $avg_rating = round($rating_data['avg_rate'] ?? 0, 1);
        $total_ratings = $rating_data['total_rates'] ?? 0;
        $rating_query->close();
    }
} catch (Exception $e) {}

// Fetch Comments
$comments = [];
try {
    $comments_query = $conn->prepare("
        SELECT c.comment_text, c.created_at, u.full_name 
        FROM dbProj_comments c 
        JOIN dbProj_users u ON c.user_id = u.user_id 
        WHERE c.job_id = ? 
        ORDER BY c.created_at DESC
    ");
    if ($comments_query) {
        $comments_query->bind_param("i", $job_id);
        $comments_query->execute();
        $comments_result = $comments_query->get_result();
        if ($comments_result) {
            $comments = $comments_result->fetch_all(MYSQLI_ASSOC);
        }
        $comments_query->close();
    }
} catch (Exception $e) {}

$job_type = $job['employment_type'] ?? $job['type'] ?? 'Full-time';
$salary_label = $job['salary'] ?? 'Competitive';
if (!empty($job['salary_min']) || !empty($job['salary_max'])) {
    $currency = $job['currency'] ?? '';
    $salary_min = !empty($job['salary_min']) ? number_format((float)$job['salary_min'], 0) : null;
    $salary_max = !empty($job['salary_max']) ? number_format((float)$job['salary_max'], 0) : null;
    if ($salary_min && $salary_max) {
        $salary_label = trim($currency . ' ' . $salary_min . ' - ' . $salary_max);
    } elseif ($salary_min) {
        $salary_label = trim($currency . ' ' . $salary_min . '+');
    } elseif ($salary_max) {
        $salary_label = trim('Up to ' . $currency . ' ' . $salary_max);
    }
}
$posted_label = !empty($job['published_at']) ? date('M d, Y', strtotime($job['published_at'])) : 'Just now';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['title']) ?> | Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="job-detail-page">

<nav class="navbar navbar-expand-lg navbar-light site-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <span class="brand-mark"><i class="bi bi-briefcase-fill"></i></span>
            TalentNet
        </a>
        <div class="navbar-nav ms-auto">
            <?php if ($is_logged_in): ?>
                <span class="nav-link me-3"><i class="bi bi-person-circle me-1"></i>Welcome Back</span>
                <a class="btn btn-outline-danger btn-sm my-auto" href="logout.php">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            <?php else: ?>
                <a class="btn btn-primary btn-sm my-auto" href="login.php">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Account Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container my-4">
    <a href="index.php" class="text-decoration-none small d-inline-flex align-items-center gap-1 mb-3">
        <i class="bi bi-arrow-left"></i>Back to Job Openings Feed
    </a>
    
    <div class="row g-4">
        
        <div class="col-lg-8">
            
            <div class="detail-card p-4 mb-4">
                <span class="badge text-bg-light border mb-3 px-3 py-2"><?= htmlspecialchars($job_type) ?></span>
                <h1 class="h2 fw-bold mb-2"><?= htmlspecialchars($job['title']) ?></h1>
                <h2 class="h5 text-muted mb-4"><?= htmlspecialchars($job['company_name'] ?? 'Unknown Company') ?></h2>
                
                <div class="detail-meta">
                    <div class="detail-meta-item">
                        <div class="small text-muted">Location</div>
                        <div class="fw-bold"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($job['location'] ?? 'Not Specified') ?></div>
                    </div>
                    <div class="detail-meta-item">
                        <div class="small text-muted">Compensation</div>
                        <div class="fw-bold"><i class="bi bi-cash-coin me-1"></i><?= htmlspecialchars($salary_label) ?></div>
                    </div>
                </div>
            </div>

            <div class="detail-card p-4 mb-4">
                <h2 class="h5 fw-bold border-bottom pb-2 mb-3">Job Overview & Operations</h2>
                <p class="text-secondary lh-lg small"><?= nl2br(htmlspecialchars($job['description'] ?? 'No description provided.')) ?></p>
                
                <?php if (!empty($job['requirements'])): ?>
                    <h2 class="h5 fw-bold border-bottom pb-2 mt-4 mb-3">Candidate Requirements</h2>
                    <p class="text-secondary lh-lg small"><?= nl2br(htmlspecialchars($job['requirements'])) ?></p>
                <?php endif; ?>
            </div>

            <div class="detail-card mb-4 overflow-hidden">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="bi bi-chat-square-heart text-primary me-2"></i>Ratings & Feedback Hub
                </div>
                <div class="card-body p-4 bg-white">
                    
                    <div class="rating-section mb-4 text-center p-3 soft-panel">
                        <h6 class="fw-bold text-dark mb-1">Rate this Job Listing</h6>
                        <div class="star-rating my-2">
                            <?php 
                            $rounded_avg = round($avg_rating);
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rounded_avg) {
                                    echo "<span class='star-item mx-1' data-value='{$i}'>&#9733;</span>";
                                } else {
                                    echo "<span class='star-item mx-1' data-value='{$i}'>&#9734;</span>";
                                }
                            }
                            ?>
                        </div>
                        <p class="text-muted small mb-0">
                            Average Score: <strong id="avg-display" class="text-dark"><?= $avg_rating ?></strong> / 5 
                            (<span id="count-display" class="fw-bold"><?= $total_ratings ?></span> metrics recorded)
                        </p>
                    </div>

                    <hr class="text-muted opacity-25">

                    <h6 class="fw-bold text-dark mb-3">Community Thread (<span id="comment-count"><?= count($comments) ?></span>)</h6>
                    <div id="comments-container" class="comments-scroll mb-4 pe-1">
                        <?php if (empty($comments)): ?>
                            <p id="no-comments" class="empty-state text-center py-4 my-2 small">No comments posted yet. Be the first to start the conversation!</p>
                        <?php else: ?>
                            <?php foreach ($comments as $com): ?>
                                <div class="p-3 border rounded bg-light mb-2">
                                    <div class="d-flex justify-content-between fw-bold small text-primary mb-1">
                                        <span>@<?= htmlspecialchars($com['full_name']) ?></span>
                                        <span class="text-muted fw-normal small"><?= date('M d, Y', strtotime($com['created_at'])) ?></span>
                                    </div>
                                    <p class="mb-0 small text-secondary lh-base"><?= htmlspecialchars($com['comment_text']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_logged_in): ?>
                        <form id="ajax-comment-form" class="mt-2">
                            <input type="hidden" id="job_id" value="<?= $job_id ?>">
                            <div class="input-group">
                                <input type="text" id="comment_text" class="form-control py-2 small" placeholder="Write a constructive feedback comment..." required autocomplete="off">
                                <button class="btn btn-primary px-4" type="submit" id="submitCommentBtn">
                                    <i class="bi bi-send me-1"></i>Post
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning py-2 small text-center mb-0 rounded-3">
                             Please <a href="login.php" class="alert-link fw-bold">Login</a> to submit a star rating or leave a dynamic comment thread.
                        </div>
                    <?php endif; ?>

                </div>
            </div>
            </div>

        <div class="col-lg-4">
            <div class="detail-card p-4 sticky-top job-summary-card">
                <h2 class="h5 fw-bold mb-3">Application Summary</h2>
                
                <ul class="list-unstyled mb-4 small text-secondary">
                    <li class="mb-2"><i class="bi bi-building me-2 text-primary"></i><strong>Employer:</strong> <?= htmlspecialchars($job['company_name'] ?? 'Unknown Company') ?></li>
                    <li class="mb-2"><i class="bi bi-geo-alt me-2 text-primary"></i><strong>Location:</strong> <?= htmlspecialchars($job['location'] ?? 'Not Specified') ?></li>
                    <li class="mb-2"><i class="bi bi-clock me-2 text-primary"></i><strong>Job Nature:</strong> <?= htmlspecialchars($job_type) ?></li>
                    <li class="mb-1"><i class="bi bi-calendar3 me-2 text-primary"></i><strong>Posted:</strong> <?= htmlspecialchars($posted_label) ?></li>
                </ul>
                
                <button class="btn btn-success w-100 py-2 mb-2" onclick="alert('Application submitted successfully via Milestone 3 pipeline hook!')">
                    <i class="bi bi-send-check me-1"></i>Apply For Position
                </button>
                <button class="btn btn-outline-secondary w-100 py-2 small">
                    <i class="bi bi-bookmark me-1"></i>Bookmark Job Posting
                </button>
            </div>
        </div>

    </div>
</div>

<footer class="site-footer text-center py-4 mt-5">
    <div class="container">
        <p class="mb-1 fw-semibold">TalentNet Job Portal</p>
        <p class="mb-0 small">&copy; 2026 IT8415 Job Portal Group Project. All rights reserved.</p>
    </div>
</footer>

<script>
$(document).ready(function() {
    const jobId = $('#job_id').val() || <?= $job_id ?>; 
    const isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;
    
    let currentSavedAvg = Math.round(<?= $avg_rating ?>);

    $('.star-item').on('mouseover', function() {
        let index = $(this).data('value');
        $('.star-item').each(function() {
            $(this).html($(this).data('value') <= index ? '&#9733;' : '&#9734;');
        });
    }).on('mouseleave', function() {
        $('.star-item').each(function() {
            $(this).html($(this).data('value') <= currentSavedAvg ? '&#9733;' : '&#9734;');
        });
    });

    $('.star-item').on('click', function() {
        if (!isLoggedIn) {
            alert('Access Denied: Please sign in to rate job listings!');
            return;
        }
        let ratingValue = $(this).data('value');

        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'submit_rating',
                job_id: jobId,
                rating: ratingValue
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#avg-display').text(response.average);
                    $('#count-display').text(response.count);
                    
                    currentSavedAvg = Math.round(response.average);
                    
                    $('.star-item').each(function() {
                        $(this).html($(this).data('value') <= currentSavedAvg ? '&#9733;' : '&#9734;');
                    });
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Connection error communicating with data hub parameters.');
            }
        });
    });

    // AJAX Comment Submission
    $('#ajax-comment-form').on('submit', function(e) {
        e.preventDefault();
        
        let textInput = $('#comment_text').val().trim();
        if (textInput === '') return;

        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'submit_comment',
                job_id: jobId,
                comment_text: textInput
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#no-comments').remove(); 
                    
                    let newCommentHtml = `
                        <div class="p-3 border rounded bg-light mb-2 shadow-sm border-start border-primary" style="display:none;">
                            <div class="d-flex justify-content-between font-weight-bold small text-primary mb-1">
                                <span>@${response.username}</span>
                                <span class="text-success font-weight-bold small">${response.date}</span>
                            </div>
                            <p class="mb-0 small text-secondary lh-base">${response.comment}</p>
                        </div>`;
                    
                    $('#comments-container').prepend(newCommentHtml);
                    $('#comments-container div:first-child').slideDown(250); 
                    $('#comment_text').val(''); 
                    
                    let countSpan = $('#comment-count');
                    countSpan.text(parseInt(countSpan.text()) + 1);
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Failed to transmit message parameters asynchronously.');
            }
        });
    });
});
</script>
</body>
</html>

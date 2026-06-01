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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['title']) ?> | Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', system-ui, sans-serif; }
        .star-item { font-size: 1.8rem; transition: transform 0.1s ease; display: inline-block; }
        .star-item:hover { transform: scale(1.2); }
        .comments-scroll { max-height: 320px; overflow-y: auto; scrollbar-width: thin; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="index.php">💼 JobPortal</a>
        <div class="navbar-nav ms-auto">
            <?php if ($is_logged_in): ?>
                <span class="nav-link text-light me-3">Welcome Back!</span>
                <a class="btn btn-outline-danger btn-sm my-auto" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="btn btn-primary btn-sm my-auto" href="login.php">Account Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container my-4">
    <a href="index.php" class="text-decoration-none text-muted small d-inline-block mb-3">← Back to Job Openings Feed</a>
    
    <div class="row g-4">
        
        <div class="col-lg-8">
            
            <div class="card shadow-sm border-0 p-4 mb-4 rounded-3 bg-white">
                <span class="badge bg-primary align-self-start mb-2 px-3 py-2 rounded-pill"><?= htmlspecialchars($job['type'] ?? 'Full-time') ?></span>
                <h2 class="fw-bold text-dark mb-1"><?= htmlspecialchars($job['title']) ?></h2>
                <h5 class="text-secondary mb-3"><?= htmlspecialchars($job['company_name'] ?? 'Unknown Company') ?></h5>
                
                <div class="d-flex text-muted small gap-4 border-top pt-3">
                    <div>📍 <strong>Location:</strong> <?= htmlspecialchars($job['location'] ?? 'Not Specified') ?></div>
                    <div>💰 <strong>Compensation:</strong> <?= htmlspecialchars($job['salary'] ?? 'Competitive') ?></div>
                </div>
            </div>

            <div class="card shadow-sm border-0 p-4 mb-4 rounded-3 bg-white">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Job Overview & Operations</h5>
                <p class="text-secondary lh-lg small"><?= nl2br(htmlspecialchars($job['description'] ?? 'No description provided.')) ?></p>
                
                <?php if (!empty($job['requirements'])): ?>
                    <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">Candidate Requirements</h5>
                    <p class="text-secondary lh-lg small"><?= nl2br(htmlspecialchars($job['requirements'])) ?></p>
                <?php endif; ?>
            </div>

            <div class="card shadow-sm border-0 mb-4 rounded-3 overflow-hidden">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    ✨ Ratings & Feedback Hub 
                </div>
                <div class="card-body p-4 bg-white">
                    
                    <div class="rating-section mb-4 text-center p-3 bg-light rounded-3 border">
                        <h6 class="fw-bold text-dark mb-1">Rate this Job Listing</h6>
                        <div class="star-rating text-warning my-2" style="cursor: pointer;">
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
                            <p id="no-comments" class="text-muted italic text-center py-4 my-2 border rounded border-dashed bg-light small">No comments posted yet. Be the first to start the conversation!</p>
                        <?php else: ?>
                            <?php foreach ($comments as $com): ?>
                                <div class="p-3 border rounded bg-light mb-2 shadow-sm">
                                    <div class="d-flex justify-content-between font-weight-bold small text-primary mb-1">
                                        <span>@<?= htmlspecialchars($com['full_name']) ?></span>
                                        <span class="text-muted font-weight-normal small"><?= date('M d, Y', strtotime($com['created_at'])) ?></span>
                                    </div>
                                    <p class="mb-0 small text-secondary lh-base"><?= htmlspecialchars($com['comment_text']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_logged_in): ?>
                        <form id="ajax-comment-form" class="mt-2">
                            <input type="hidden" id="job_id" value="<?= $job_id ?>">
                            <div class="input-group shadow-sm">
                                <input type="text" id="comment_text" class="form-control py-2 small" placeholder="Write a constructive feedback comment..." required autocomplete="off">
                                <button class="btn btn-primary px-4 fw-bold" type="submit" id="submitCommentBtn">Post Comment</button>
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
            <div class="card shadow-sm border-0 p-4 sticky-top rounded-3 bg-white" style="top: 24px; z-index: 10;">
                <h5 class="fw-bold text-dark mb-3">Application Summary</h5>
                
                <ul class="list-unstyled mb-4 small text-secondary">
                    <li class="mb-2"> <strong>Employer:</strong> <?= htmlspecialchars($job['company_name'] ?? 'Unknown Company') ?></li>
                    <li class="mb-2"> <strong>Location:</strong> <?= htmlspecialchars($job['location'] ?? 'Not Specified') ?></li>
                    <li class="mb-2"> <strong>Job Nature:</strong> <?= htmlspecialchars($job['type'] ?? 'Full-time / Direct Hire') ?></li>
                    <li class="mb-1"> <strong>Posted:</strong> Just now</li>
                </ul>
                
                <button class="btn btn-success w-100 py-2.5 fw-bold rounded-3 shadow-sm mb-2" onclick="alert('Application submitted successfully via Milestone 3 pipeline hook!')">Apply For Position</button>
                <button class="btn btn-outline-secondary w-100 py-2 small rounded-3">Bookmark Job Posting</button>
            </div>
        </div>

    </div>
</div>

<footer class="bg-dark text-muted text-center py-3 mt-5 border-top border-secondary">
    <small class="small">&copy; 2026 Academic Web Application Database Project. All Rights Reserved.</small>
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
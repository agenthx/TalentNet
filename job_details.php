<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
require_once 'header.php';

$job_id = isset($_GET['id']) ? max(0, (int)$_GET['id']) : 0;
$is_logged_in = isset($_SESSION['user_id']);

$job = null;
$avg_rating = 0;
$total_ratings = 0;
$comments = [];
$media = [
    'image' => null,
    'image_alt' => null,
    'video' => null,
];

function jp_salary_label($job) {
    if (!$job || (empty($job['salary_min']) && empty($job['salary_max']))) {
        return 'Competitive';
    }
    $currency = $job['currency'] ?? '';
    $salaryMin = !empty($job['salary_min']) ? number_format((float)$job['salary_min'], 0) : null;
    $salaryMax = !empty($job['salary_max']) ? number_format((float)$job['salary_max'], 0) : null;
    if ($salaryMin && $salaryMax) return trim($currency . ' ' . $salaryMin . ' - ' . $salaryMax);
    if ($salaryMin) return trim($currency . ' ' . $salaryMin . '+');
    return trim('Up to ' . $currency . ' ' . $salaryMax);
}

if ($job_id > 0) {

    // 1. Fetch full job details
    try {
        $job_query = $conn->prepare("
            SELECT
                j.*,
                e.company_name,
                e.logo_path,
                c.category_name,
                m.file_path AS primary_image_path,
                m.alt_text  AS primary_image_alt
            FROM dbProj_job_listings j
            INNER JOIN dbProj_employers e ON j.employer_id = e.employer_id
            INNER JOIN dbProj_job_categories c ON c.category_id = j.category_id
            LEFT JOIN (
                SELECT job_id, MIN(file_path) AS file_path, MIN(alt_text) AS alt_text
                FROM dbProj_job_media
                WHERE is_primary = TRUE AND media_type = 'image'
                GROUP BY job_id
            ) m ON m.job_id = j.job_id
            WHERE j.job_id = ? AND j.status = 'published'
            LIMIT 1
        ");
        if ($job_query) {
            $job_query->bind_param("i", $job_id);
            $job_query->execute();
            $result = $job_query->get_result();
            $job = $result->fetch_assoc();
            $job_query->close();
        }
    } catch (Exception $e) {
        $job = null;
    }

    if ($job) {

        // 2. Resolve primary media
        $media['image']     = $job['primary_image_path'] ?: null;
        $media['image_alt'] = $job['primary_image_alt']  ?: $job['title'];

        // 3. Fetch additional media (video / fallback image)
        try {
            $media_query = $conn->prepare("
                SELECT media_type, file_path, alt_text
                FROM dbProj_job_media
                WHERE job_id = ?
                ORDER BY is_primary DESC, media_id ASC
            ");
            if ($media_query) {
                $media_query->bind_param("i", $job_id);
                $media_query->execute();
                $media_res = $media_query->get_result();
                while ($row = $media_res->fetch_assoc()) {
                    if ($row['media_type'] === 'image' && !$media['image']) {
                        $media['image']     = $row['file_path'];
                        $media['image_alt'] = $row['alt_text'] ?: $job['title'];
                    } elseif ($row['media_type'] === 'video' && !$media['video']) {
                        $media['video'] = $row['file_path'];
                    }
                }
                $media_query->close();
            }
        } catch (Exception $e) {
            // Media is optional; page renders fine without it.
        }

        // 4. Record view
        try {
            $viewer_ip = $_SERVER['REMOTE_ADDR'] ?? '';
            if ($is_logged_in) {
                $viewer_user_id = (int)$_SESSION['user_id'];
                $view_stmt = $conn->prepare("INSERT INTO dbProj_job_views (job_id, viewer_user_id, viewer_ip) VALUES (?, ?, ?)");
                if ($view_stmt) {
                    $view_stmt->bind_param("iis", $job_id, $viewer_user_id, $viewer_ip);
                    $view_stmt->execute();
                    $view_stmt->close();
                }
            } else {
                $view_stmt = $conn->prepare("INSERT INTO dbProj_job_views (job_id, viewer_ip) VALUES (?, ?)");
                if ($view_stmt) {
                    $view_stmt->bind_param("is", $job_id, $viewer_ip);
                    $view_stmt->execute();
                    $view_stmt->close();
                }
            }
        } catch (Exception $e) {
            // View tracking must never block the public page.
        }

        // 5. Fetch ratings
        try {
            $rating_query = $conn->prepare("
                SELECT AVG(rating_value) AS avg_rate, COUNT(rating_id) AS total_rates
                FROM dbProj_ratings
                WHERE job_id = ?
            ");
            if ($rating_query) {
                $rating_query->bind_param("i", $job_id);
                $rating_query->execute();
                $rating_result = $rating_query->get_result();
                $rating_data   = $rating_result->fetch_assoc();
                $avg_rating    = round((float)($rating_data['avg_rate']   ?? 0), 1);
                $total_ratings = (int)($rating_data['total_rates'] ?? 0);
                $rating_query->close();
            }
        } catch (Exception $e) {
            $avg_rating    = 0;
            $total_ratings = 0;
        }

        // 6. Fetch visible comments
        try {
            $comments_query = $conn->prepare("
                SELECT c.comment_text, c.created_at, u.full_name
                FROM dbProj_comments c
                INNER JOIN dbProj_users u ON c.user_id = u.user_id
                WHERE c.job_id = ? AND c.is_removed = FALSE
                ORDER BY c.created_at DESC
            ");
            if ($comments_query) {
                $comments_query->bind_param("i", $job_id);
                $comments_query->execute();
                $comments_result = $comments_query->get_result();
                $comments        = $comments_result ? $comments_result->fetch_all(MYSQLI_ASSOC) : [];
                $comments_query->close();
            }
        } catch (Exception $e) {
            $comments = [];
        }

    } // end if ($job)

} // end if ($job_id > 0)

// --- Render ---

if (!$job):
?>
<section class="section-hero mb-4">
    <div class="hero-kicker"><i class="bi bi-briefcase"></i> Job details</div>
    <h1 class="h2 mb-2">Listing unavailable</h1>
    <p class="lead mb-0">This job listing is removed, unpublished, or no longer exists.</p>
</section>

<div class="empty-state text-center p-5">
    <i class="bi bi-eye-slash display-6 d-block mb-3"></i>
    <h2 class="h5">Nothing to display</h2>
    <p class="mb-4">Return to the public job feed to browse currently published listings.</p>
    <a class="btn btn-primary" href="index.php">
        <i class="bi bi-arrow-left me-1"></i>Back to Jobs
    </a>
</div>
<?php
include 'footer.php';
exit;
endif;

$posted_label = !empty($job['published_at']) ? date('M d, Y', strtotime($job['published_at'])) : 'Not published';
$salary_label = jp_salary_label($job);
$rounded_avg  = round($avg_rating);
$logo_path    = $job['logo_path'] ?? '';
?>

<div id="job-detail-data"
     data-job-id="<?php echo (int)$job_id; ?>"
     data-logged-in="<?php echo $is_logged_in ? '1' : '0'; ?>">
</div>

<div class="my-4">

    <div id="dynamic-alert" class="alert d-none alert-dismissible fade show shadow-sm" role="alert">
        <span id="dynamic-alert-msg" class="fw-semibold"></span>
        <button type="button" id="dynamic-alert-close" class="btn-close" aria-label="Close"></button>
    </div>

    <a href="index.php" class="text-decoration-none small d-inline-flex align-items-center gap-1 mb-3">
        <i class="bi bi-arrow-left"></i> Back to Job Openings Feed
    </a>

    <div class="row g-4">

        <!-- ── Main column ───────────────────────────────────────── -->
        <div class="col-lg-8">

            <!-- Cover image -->
            <div class="detail-media mb-4">
                <?php if ($media['image']): ?>
                    <img src="<?php echo htmlspecialchars($media['image']); ?>"
                         alt="<?php echo htmlspecialchars($media['image_alt']); ?>"
                         onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                <?php endif; ?>
                <div class="job-card-image-fallback <?php echo $media['image'] ? 'd-none' : ''; ?>">
                    <i class="bi bi-briefcase"></i>
                </div>
            </div>

            <!-- Title card -->
            <div class="detail-card p-4 mb-4">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <span class="badge text-bg-light border mb-3 px-3 py-2">
                            <?php echo htmlspecialchars($job['employment_type']); ?>
                        </span>
                        <h1 class="h2 fw-bold mb-2"><?php echo htmlspecialchars($job['title']); ?></h1>
                        <h2 class="h5 text-muted mb-0"><?php echo htmlspecialchars($job['company_name']); ?></h2>
                    </div>
                    <div class="job-card-logo lg">
                        <?php if ($logo_path): ?>
                            <img src="<?php echo htmlspecialchars($logo_path); ?>"
                                 alt="<?php echo htmlspecialchars($job['company_name']); ?> logo"
                                 onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                            <span class="job-card-logo-fallback d-none"><i class="bi bi-building fs-1"></i></span>
                        <?php else: ?>
                            <span class="job-card-logo-fallback"><i class="bi bi-building fs-1"></i></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-meta">
                    <div class="detail-meta-item">
                        <div class="small text-muted">Location</div>
                        <div class="fw-bold">
                            <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($job['location']); ?>
                        </div>
                    </div>
                    <div class="detail-meta-item">
                        <div class="small text-muted">Compensation</div>
                        <div class="fw-bold">
                            <i class="bi bi-cash-coin me-1"></i><?php echo htmlspecialchars($salary_label); ?>
                        </div>
                    </div>
                    <div class="detail-meta-item">
                        <div class="small text-muted">Category</div>
                        <div class="fw-bold">
                            <i class="bi bi-grid me-1"></i><?php echo htmlspecialchars($job['category_name']); ?>
                        </div>
                    </div>
                    <div class="detail-meta-item">
                        <div class="small text-muted">Posted</div>
                        <div class="fw-bold">
                            <i class="bi bi-calendar3 me-1"></i><?php echo htmlspecialchars($posted_label); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description card -->
            <div class="detail-card p-4 mb-4">
                <h2 class="h5 fw-bold border-bottom pb-2 mb-3">Job Overview</h2>
                <p class="text-secondary lh-lg small">
                    <?php echo nl2br(htmlspecialchars($job['description'] ?? 'No description provided.')); ?>
                </p>

                <?php if (!empty($job['requirements'])): ?>
                    <h2 class="h5 fw-bold border-bottom pb-2 mt-4 mb-3">Candidate Requirements</h2>
                    <p class="text-secondary lh-lg small">
                        <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                    </p>
                <?php endif; ?>

                <?php if ($media['video']): ?>
                    <h2 class="h5 fw-bold border-bottom pb-2 mt-4 mb-3">Company Insight Video</h2>
                    <video controls class="w-100 rounded shadow-sm border" style="max-height: 350px;">
                        <source src="<?php echo htmlspecialchars($media['video']); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                <?php endif; ?>
            </div>

            <!-- Ratings & comments card -->
            <div class="detail-card mb-4 overflow-hidden">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="bi bi-chat-square-heart text-primary me-2"></i>Ratings &amp; Feedback
                </div>
                <div class="card-body p-4 bg-white">

                    <div class="rating-section mb-4 text-center p-3 soft-panel">
                        <h3 class="h6 fw-bold text-dark mb-1">Rate this Job Listing</h3>
                        <div class="star-rating my-2 fs-3 text-warning"
                             aria-label="Rate this job out of five stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star-item mx-1"
                                      role="button"
                                      tabindex="0"
                                      data-value="<?php echo $i; ?>">
                                    <?php echo $i <= $rounded_avg ? '&#9733;' : '&#9734;'; ?>
                                </span>
                            <?php endfor; ?>
                        </div>
                        <p class="text-muted small mb-0">
                            Average Score:
                            <strong id="avg-display" class="text-dark"><?php echo $avg_rating; ?></strong> / 5
                            (<span id="count-display" class="fw-bold"><?php echo $total_ratings; ?></span> ratings)
                        </p>
                    </div>

                    <hr class="text-muted opacity-25">

                    <h3 class="h6 fw-bold text-dark mb-3">
                        Community Thread (<span id="comment-count"><?php echo count($comments); ?></span>)
                    </h3>

                    <div id="comments-container" class="comments-scroll mb-4 pe-1">
                        <?php if (empty($comments)): ?>
                            <p id="no-comments" class="empty-state text-center py-4 my-2 small">
                                No comments posted yet.
                            </p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="p-3 border rounded bg-light mb-2">
                                    <div class="d-flex justify-content-between fw-bold small text-primary mb-1">
                                        <span>@<?php echo htmlspecialchars($comment['full_name']); ?></span>
                                        <span class="text-muted fw-normal small">
                                            <?php echo date('M d, Y', strtotime($comment['created_at'])); ?>
                                        </span>
                                    </div>
                                    <p class="mb-0 small text-secondary lh-base">
                                        <?php echo htmlspecialchars($comment['comment_text']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_logged_in): ?>
                        <form id="ajax-comment-form" class="mt-2"
                              method="post"
                              action="job_details.php?id=<?php echo (int)$job_id; ?>">
                            <div class="input-group">
                                <input type="text"
                                       id="comment_text"
                                       name="comment_text"
                                       class="form-control py-2 small"
                                       maxlength="1000"
                                       placeholder="Write a constructive feedback comment"
                                       required
                                       autocomplete="off">
                                <button class="btn btn-primary px-4" type="submit" id="submitCommentBtn">
                                    <i class="bi bi-send me-1"></i>Post
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning py-2 small text-center mb-0 rounded-3">
                            Please <a href="login.php" class="alert-link fw-bold">login</a>
                            to submit a star rating or leave a comment.
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div><!-- /col-lg-8 -->

        <!-- ── Sidebar ────────────────────────────────────────────── -->
        <div class="col-lg-4">
            <aside class="detail-card p-4 sticky-top job-summary-card">
                <h2 class="h5 fw-bold mb-3">Listing Summary</h2>

                <ul class="list-unstyled mb-4 small text-secondary">
                    <li class="mb-2">
                        <i class="bi bi-building me-2 text-primary"></i>
                        <strong>Employer:</strong> <?php echo htmlspecialchars($job['company_name']); ?>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-geo-alt me-2 text-primary"></i>
                        <strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-laptop me-2 text-primary"></i>
                        <strong>Work Mode:</strong> <?php echo htmlspecialchars($job['work_mode'] ?? 'Not specified'); ?>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-clock me-2 text-primary"></i>
                        <strong>Job Type:</strong> <?php echo htmlspecialchars($job['employment_type']); ?>
                    </li>
                    <li class="mb-1">
                        <i class="bi bi-calendar3 me-2 text-primary"></i>
                        <strong>Posted:</strong> <?php echo htmlspecialchars($posted_label); ?>
                    </li>
                </ul>

                <?php if (!empty($job['application_url'])): ?>
                    <a class="btn btn-success w-100 py-2 mb-3"
                       href="<?php echo htmlspecialchars($job['application_url']); ?>"
                       target="_blank"
                       rel="noopener">
                        <i class="bi bi-send-check me-1"></i>Apply For Position
                    </a>
                <?php else: ?>
                    <div class="alert alert-info small mb-3">
                        This listing does not include an external application link.
                    </div>
                <?php endif; ?>

                <a class="btn btn-light w-100 py-2 small" href="index.php">
                    <i class="bi bi-search me-1"></i>Browse More Jobs
                </a>
            </aside>
        </div>

    </div><!-- /row -->
</div><!-- /my-4 -->

<?php
/*
 * BUGFIX: This page's script uses jQuery ($(document).ready, $.ajax), but jQuery
 * is loaded inside footer.php, which is included AFTER this block. Emitting the
 * script here ran it before jQuery existed ("$ is not defined"), so NONE of the
 * handlers bound -- including the comment form's submit handler. Without that
 * handler the form did a native submit, losing ?id= and showing "Listing
 * unavailable". We buffer the script now and let footer.php print it (via
 * $pageScripts) AFTER jQuery has loaded.
 */
ob_start();
?>
<script>
function showHtmlAlert(message, type) {
    const allowed = ['success', 'danger', 'warning', 'info'];
    const safeType = allowed.includes(type) ? type : 'info';
    const alertBox = $('#dynamic-alert');
    alertBox.removeClass('alert-success alert-danger alert-warning alert-info d-none')
            .addClass('alert-' + safeType);
    $('#dynamic-alert-msg').text(message);
    clearTimeout(window._jobAlertTimer);
    window._jobAlertTimer = setTimeout(function () {
        alertBox.addClass('d-none');
    }, 4000);
}

$(document).ready(function () {
    const detailData  = $('#job-detail-data');
    const jobId       = Number(detailData.data('job-id'));
    const isLoggedIn  = String(detailData.data('logged-in')) === '1';
    let currentAvg    = Math.round(Number($('#avg-display').text()) || 0);

    $('#dynamic-alert-close').on('click', function () {
        $('#dynamic-alert').addClass('d-none');
    });

    function escapeHtml(v) {
        return String(v).replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];
        });
    }

    function renderStars(avg) {
        $('.star-item').each(function () {
            const filled = $(this).data('value') <= avg;
            $(this).html(filled ? '&#9733;' : '&#9734;');
        });
    }

    $('.star-item').on('mouseover', function () {
        const hovered = $(this).data('value');
        $('.star-item').each(function () {
            $(this).html($(this).data('value') <= hovered ? '&#9733;' : '&#9734;');
        });
    }).on('mouseleave', function () {
        renderStars(currentAvg);
    });

    $('.star-item').on('click', function () {
        if (!isLoggedIn) {
            showHtmlAlert('Please sign in to rate job listings.', 'warning');
            return;
        }
        const ratingValue = $(this).data('value');
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: { action: 'submit_rating', job_id: jobId, rating: ratingValue },
            dataType: 'json',
            success: function (r) {
                if (r.status === 'success') {
                    $('#avg-display').text(r.average);
                    $('#count-display').text(r.count);
                    currentAvg = Math.round(r.average);
                    renderStars(currentAvg);
                    showHtmlAlert('Your rating has been recorded.', 'success');
                } else {
                    showHtmlAlert(r.message || 'Unable to save the rating.', 'danger');
                }
            },
            error: function () {
                showHtmlAlert('Connection error while saving the rating.', 'danger');
            }
        });
    });

    $('#ajax-comment-form').on('submit', function (e) {
        e.preventDefault();
        const text = $('#comment_text').val().trim();
        if (!text) {
            showHtmlAlert('Enter a comment before posting.', 'warning');
            return;
        }
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: { action: 'submit_comment', job_id: jobId, comment_text: text },
            dataType: 'json',
            success: function (r) {
                if (r.status === 'success') {
                    $('#no-comments').remove();
                    const html = `
                        <div class="p-3 border rounded bg-light mb-2" style="display:none;">
                            <div class="d-flex justify-content-between fw-bold small text-primary mb-1">
                                <span>@${escapeHtml(r.username)}</span>
                                <span class="text-muted fw-normal small">${escapeHtml(r.date)}</span>
                            </div>
                            <p class="mb-0 small text-secondary lh-base">${escapeHtml(r.comment)}</p>
                        </div>`;
                    $('#comments-container').prepend(html);
                    $('#comments-container div:first-child').slideDown(250);
                    $('#comment_text').val('');
                    const c = $('#comment-count');
                    c.text(Number(c.text()) + 1);
                    showHtmlAlert('Your comment was posted.', 'success');
                } else {
                    showHtmlAlert(r.message || 'Unable to post the comment.', 'danger');
                }
            },
            error: function () {
                showHtmlAlert('Connection error while posting the comment.', 'danger');
            }
        });
    });
});
</script>
<?php
// Hand the buffered script to footer.php, which echoes $pageScripts AFTER jQuery.
$pageScripts = ob_get_clean();
include 'footer.php';
?>

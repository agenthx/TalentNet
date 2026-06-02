<?php
session_start();
require_once 'db.php'; 

header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    $timeout = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        echo json_encode(['status' => 'error', 'message' => 'Your session expired. Please log in again.']);
        exit;
    }

    $_SESSION['last_activity'] = time();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'] ?? null; 

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to perform this action.']);
    exit;
}

// Ratings Handler
if ($action === 'submit_rating') {
    $job_id = intval($_POST['job_id'] ?? 0);
    $rating = min(5, max(1, intval($_POST['rating'] ?? 0)));

    if ($job_id < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid job listing.']);
        exit;
    }

    try {
        $job_stmt = $conn->prepare("SELECT job_id FROM dbProj_job_listings WHERE job_id = ? AND status = 'published' LIMIT 1");
        if (!$job_stmt) throw new Exception("Database error");

        $job_stmt->bind_param("i", $job_id);
        $job_stmt->execute();
        $job_result = $job_stmt->get_result();
        if (!$job_result->fetch_assoc()) {
            echo json_encode(['status' => 'error', 'message' => 'This job listing is not available for rating.']);
            exit;
        }

        // Check if the user already rated this job
        $check_stmt = $conn->prepare("SELECT rating_id FROM dbProj_ratings WHERE job_id = ? AND user_id = ?");
        if (!$check_stmt) throw new Exception("Database error");
        
        $check_stmt->bind_param("ii", $job_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->fetch_assoc()) {
            // Update existing rating
            $stmt = $conn->prepare("UPDATE dbProj_ratings SET rating_value = ? WHERE job_id = ? AND user_id = ?");
            $stmt->bind_param("iii", $rating, $job_id, $user_id);
            $stmt->execute();
        } else {
            // Insert new rating
            $stmt = $conn->prepare("INSERT INTO dbProj_ratings (job_id, user_id, rating_value) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $job_id, $user_id, $rating);
            $stmt->execute();
        }

        // Calculate the new average and total count
        $avg_stmt = $conn->prepare("SELECT AVG(rating_value) as average, COUNT(rating_id) as count FROM dbProj_ratings WHERE job_id = ?");
        $avg_stmt->bind_param("i", $job_id);
        $avg_stmt->execute();
        
        $avg_result = $avg_stmt->get_result();
        $stats = $avg_result->fetch_assoc();

        echo json_encode([
            'status' => 'success', 
            'message' => 'Rating saved!', 
            'average' => round($stats['average'], 1),
            'count' => $stats['count']
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
    }
    exit;
}


// Comments Handler
if ($action === 'submit_comment') {
    $job_id = intval($_POST['job_id'] ?? 0);
    $comment_text = trim($_POST['comment_text'] ?? '');

    if ($job_id < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid job listing.']);
        exit;
    }

    if (empty($comment_text)) {
        echo json_encode(['status' => 'error', 'message' => 'Comment cannot be empty.']);
        exit;
    }

    if (strlen($comment_text) > 1000) {
        echo json_encode(['status' => 'error', 'message' => 'Comment must be 1000 characters or fewer.']);
        exit;
    }

    try {
        $job_stmt = $conn->prepare("SELECT job_id FROM dbProj_job_listings WHERE job_id = ? AND status = 'published' LIMIT 1");
        if (!$job_stmt) throw new Exception("Database error");

        $job_stmt->bind_param("i", $job_id);
        $job_stmt->execute();
        $job_result = $job_stmt->get_result();
        if (!$job_result->fetch_assoc()) {
            echo json_encode(['status' => 'error', 'message' => 'This job listing is not available for comments.']);
            exit;
        }

        // Insert the new comment
        $stmt = $conn->prepare("INSERT INTO dbProj_comments (job_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
        if (!$stmt) throw new Exception("Database error");
        
        // "iis" = integer (job_id), integer (user_id), string (comment_text)
        $stmt->bind_param("iis", $job_id, $user_id, $comment_text);
        $stmt->execute();

        // Fetch the username to return to the frontend for immediate display
        $user_stmt = $conn->prepare("SELECT full_name FROM dbProj_users WHERE user_id = ?");
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        
        $user_result = $user_stmt->get_result();
        $user = $user_result->fetch_assoc();

        echo json_encode([
            'status' => 'success',
            'username' => $user['full_name'],
            'comment' => $comment_text,
            'date' => 'Just now'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save comment.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown AJAX action.']);

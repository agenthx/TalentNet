<?php
session_start();
require_once 'db.php'; 

header('Content-Type: application/json');

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


// ratingsd
if ($action === 'submit_rating') {
    $job_id = intval($_POST['job_id']);
    $rating = min(5, max(1, intval($_POST['rating'])));

    try {
        $check_stmt = $pdo->prepare("SELECT rating_id FROM dbProj_ratings WHERE job_id = ? AND user_id = ?");
        $check_stmt->execute([$job_id, $user_id]);
        
        if ($check_stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE dbProj_ratings SET rating_value = ? WHERE job_id = ? AND user_id = ?");
            $stmt->execute([$rating, $job_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO dbProj_ratings (job_id, user_id, rating_value) VALUES (?, ?, ?)");
            $stmt->execute([$job_id, $user_id, $rating]);
        }

        $avg_stmt = $pdo->prepare("SELECT AVG(rating_value) as average, COUNT(rating_id) as count FROM dbProj_ratings WHERE job_id = ?");
        $avg_stmt->execute([$job_id]);
        $stats = $avg_stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success', 
            'message' => 'Rating saved!', 
            'average' => round($stats['average'], 1),
            'count' => $stats['count']
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
    }
    exit;
}


// comments
if ($action === 'submit_comment') {
    $job_id = intval($_POST['job_id']);
    $comment_text = trim($_POST['comment_text']);

    if (empty($comment_text)) {
        echo json_encode(['status' => 'error', 'message' => 'Comment cannot be empty.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO dbProj_comments (job_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$job_id, $user_id, $comment_text]);

        
        $user_stmt = $pdo->prepare("SELECT full_name FROM dbProj_users WHERE user_id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'username' => htmlspecialchars($user['full_name']),
            'comment' => htmlspecialchars($comment_text),
            'date' => 'Just now'
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save comment.']);
    }
    exit;
}
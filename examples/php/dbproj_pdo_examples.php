<?php

declare(strict_types=1);

/*
 * Prepared statement examples for the IT8415 Job Portal database.
 * These snippets are intended to be copied into the NetBeans PHP project.
 */

function dbProjPdo(string $dbUser = 'dbProj_app_viewer'): PDO
{
    $host = getenv('DBPROJ_DB_HOST') ?: '127.0.0.1';
    $port = getenv('DBPROJ_DB_PORT') ?: '3306';
    $database = getenv('DBPROJ_DB_NAME') ?: 'dbProj_job_portal';

    $passwords = [
        'dbProj_app_auth' => getenv('DBPROJ_AUTH_PASSWORD') ?: 'ChangeMe_Auth123!',
        'dbProj_app_viewer' => getenv('DBPROJ_VIEWER_PASSWORD') ?: 'ChangeMe_Viewer123!',
        'dbProj_app_creator' => getenv('DBPROJ_CREATOR_PASSWORD') ?: 'ChangeMe_Creator123!',
        'dbProj_app_admin' => getenv('DBPROJ_ADMIN_PASSWORD') ?: 'ChangeMe_Admin123!',
    ];

    if (!array_key_exists($dbUser, $passwords)) {
        throw new InvalidArgumentException('Unknown database role account.');
    }

    return new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $dbUser,
        $passwords[$dbUser],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function dbProjFindUserForLogin(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare(
        'SELECT u.user_id, u.role_id, r.role_name, u.full_name, u.email, u.password_hash
         FROM dbProj_users u
         INNER JOIN dbProj_roles r ON r.role_id = u.role_id
         WHERE u.email = :email AND u.is_active = TRUE
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);

    $user = $stmt->fetch();
    return $user === false ? null : $user;
}

function dbProjSearchJobs(
    PDO $pdo,
    string $term,
    ?string $fromDate = null,
    ?string $toDate = null,
    ?int $creatorUserId = null,
    int $limitRows = 10
): array {
    $limitRows = max(1, min($limitRows, 50));

    $stmt = $pdo->prepare(
        'SELECT
            j.job_id,
            j.title,
            j.short_description,
            j.location,
            j.employment_type,
            j.work_mode,
            j.published_at,
            c.category_name,
            e.company_name,
            MATCH(j.title, j.description) AGAINST (:term_score IN NATURAL LANGUAGE MODE) AS relevance,
            ROUND(AVG(r.rating_value), 2) AS average_rating,
            COUNT(DISTINCT r.rating_id) AS rating_count
         FROM dbProj_job_listings j
         INNER JOIN dbProj_job_categories c ON c.category_id = j.category_id
         INNER JOIN dbProj_employers e ON e.employer_id = j.employer_id
         LEFT JOIN dbProj_ratings r ON r.job_id = j.job_id
         WHERE j.status = "published"
           AND (:term_filter = "" OR MATCH(j.title, j.description) AGAINST (:term_where IN NATURAL LANGUAGE MODE))
           AND (:from_date IS NULL OR DATE(j.published_at) >= :from_date_value)
           AND (:to_date IS NULL OR DATE(j.published_at) <= :to_date_value)
           AND (:creator_user_id IS NULL OR j.created_by_user_id = :creator_user_id_value)
         GROUP BY
            j.job_id,
            j.title,
            j.short_description,
            j.location,
            j.employment_type,
            j.work_mode,
            j.published_at,
            c.category_name,
            e.company_name,
            relevance
         ORDER BY
            CASE WHEN :term_sort = "" THEN 0 ELSE relevance END DESC,
            average_rating DESC,
            j.published_at DESC
         LIMIT :limit_rows'
    );

    $stmt->bindValue('term_score', $term);
    $stmt->bindValue('term_filter', $term);
    $stmt->bindValue('term_where', $term);
    $stmt->bindValue('from_date', $fromDate);
    $stmt->bindValue('from_date_value', $fromDate);
    $stmt->bindValue('to_date', $toDate);
    $stmt->bindValue('to_date_value', $toDate);
    $stmt->bindValue('creator_user_id', $creatorUserId, $creatorUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue('creator_user_id_value', $creatorUserId, $creatorUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue('term_sort', $term);
    $stmt->bindValue('limit_rows', $limitRows, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function dbProjFetchTopRatedJobs(
    PDO $pdo,
    ?string $startDate = null,
    ?string $endDate = null,
    ?int $categoryId = null,
    int $limitRows = 10
): array {
    $stmt = $pdo->prepare(
        'CALL dbProj_get_top_rated_jobs(:start_date, :end_date, :category_id, :limit_rows)'
    );
    $stmt->bindValue('start_date', $startDate);
    $stmt->bindValue('end_date', $endDate);
    $stmt->bindValue('category_id', $categoryId, $categoryId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue('limit_rows', max(1, min($limitRows, 50)), PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function dbProjAddComment(PDO $pdo, int $jobId, int $userId, string $commentText): bool
{
    $stmt = $pdo->prepare(
        'INSERT INTO dbProj_comments (job_id, user_id, comment_text)
         VALUES (:job_id, :user_id, :comment_text)'
    );

    return $stmt->execute([
        'job_id' => $jobId,
        'user_id' => $userId,
        'comment_text' => trim($commentText),
    ]);
}

function dbProjRateJob(PDO $pdo, int $jobId, int $userId, int $ratingValue): bool
{
    if ($ratingValue < 1 || $ratingValue > 5) {
        throw new InvalidArgumentException('Rating must be between 1 and 5.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO dbProj_ratings (job_id, user_id, rating_value)
         VALUES (:job_id, :user_id, :rating_value)
         ON DUPLICATE KEY UPDATE
            rating_value = VALUES(rating_value),
            updated_at = CURRENT_TIMESTAMP'
    );

    return $stmt->execute([
        'job_id' => $jobId,
        'user_id' => $userId,
        'rating_value' => $ratingValue,
    ]);
}

function dbProjAdminRemoveComment(PDO $pdo, int $commentId, int $adminUserId, string $reason): bool
{
    $stmt = $pdo->prepare(
        'UPDATE dbProj_comments
         SET is_removed = TRUE,
             removed_by_user_id = :admin_user_id,
             removed_reason = :removed_reason
         WHERE comment_id = :comment_id'
    );

    return $stmt->execute([
        'admin_user_id' => $adminUserId,
        'removed_reason' => trim($reason),
        'comment_id' => $commentId,
    ]);
}

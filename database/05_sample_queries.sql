-- IT8415 Job Portal sample queries for testing and report screenshots

USE dbProj_job_portal;

-- Full-text search on title and description.
SET @search_phrase = 'database reporting';

SELECT
  j.job_id,
  j.title,
  c.category_name,
  e.company_name,
  MATCH(j.title, j.description) AGAINST (@search_phrase IN NATURAL LANGUAGE MODE) AS relevance,
  ROUND(AVG(r.rating_value), 2) AS average_rating,
  COUNT(DISTINCT r.rating_id) AS rating_count
FROM dbProj_job_listings j
INNER JOIN dbProj_job_categories c ON c.category_id = j.category_id
INNER JOIN dbProj_employers e ON e.employer_id = j.employer_id
LEFT JOIN dbProj_ratings r ON r.job_id = j.job_id
WHERE j.status = 'published'
  AND MATCH(j.title, j.description) AGAINST (@search_phrase IN NATURAL LANGUAGE MODE)
GROUP BY
  j.job_id,
  j.title,
  c.category_name,
  e.company_name,
  relevance
ORDER BY relevance DESC, average_rating DESC;

-- Stored procedure: top-rated jobs within a date range.
CALL dbProj_get_top_rated_jobs('2026-05-01', '2026-05-31', NULL, 10);

-- Stored procedure: content created by a specific user.
CALL dbProj_get_jobs_by_creator(2);

-- Report query: number of published jobs per category.
SELECT
  c.category_name,
  COUNT(j.job_id) AS published_job_count
FROM dbProj_job_categories c
LEFT JOIN dbProj_job_listings j
  ON j.category_id = c.category_id
  AND j.status = 'published'
GROUP BY c.category_id, c.category_name
ORDER BY c.category_name;

-- Report query: recent comments visible to viewers.
SELECT
  cm.comment_id,
  j.title,
  u.full_name AS commenter,
  cm.comment_text,
  cm.created_at
FROM dbProj_comments cm
INNER JOIN dbProj_job_listings j ON j.job_id = cm.job_id
INNER JOIN dbProj_users u ON u.user_id = cm.user_id
WHERE cm.is_removed = FALSE
ORDER BY cm.created_at DESC;

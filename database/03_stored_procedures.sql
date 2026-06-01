-- IT8415 Job Portal stored procedures

-- USE dbProj_job_portal;

DELIMITER $$

DROP PROCEDURE IF EXISTS dbProj_get_top_rated_jobs$$
CREATE PROCEDURE dbProj_get_top_rated_jobs (
  IN p_start_date DATE,
  IN p_end_date DATE,
  IN p_category_id INT UNSIGNED,
  IN p_limit_rows INT
)
BEGIN
  DECLARE v_limit_rows INT DEFAULT 10;

  SET v_limit_rows = CASE
    WHEN p_limit_rows IS NULL OR p_limit_rows < 1 THEN 10
    WHEN p_limit_rows > 50 THEN 50
    ELSE p_limit_rows
  END;

  SELECT
    j.job_id,
    j.title,
    c.category_name,
    e.company_name,
    j.location,
    j.work_mode,
    ROUND(AVG(
      CASE
        WHEN (p_start_date IS NULL OR DATE(r.created_at) >= p_start_date)
          AND (p_end_date IS NULL OR DATE(r.created_at) <= p_end_date)
        THEN r.rating_value
        ELSE NULL
      END
    ), 2) AS average_rating,
    COUNT(DISTINCT
      CASE
        WHEN (p_start_date IS NULL OR DATE(r.created_at) >= p_start_date)
          AND (p_end_date IS NULL OR DATE(r.created_at) <= p_end_date)
        THEN r.rating_id
        ELSE NULL
      END
    ) AS rating_count,
    COUNT(DISTINCT
      CASE
        WHEN (p_start_date IS NULL OR DATE(v.viewed_at) >= p_start_date)
          AND (p_end_date IS NULL OR DATE(v.viewed_at) <= p_end_date)
        THEN v.view_id
        ELSE NULL
      END
    ) AS view_count,
    j.published_at
  FROM dbProj_job_listings j
  INNER JOIN dbProj_job_categories c ON c.category_id = j.category_id
  INNER JOIN dbProj_employers e ON e.employer_id = j.employer_id
  LEFT JOIN dbProj_ratings r ON r.job_id = j.job_id
  LEFT JOIN dbProj_job_views v ON v.job_id = j.job_id
  WHERE j.status = 'published'
    AND (p_category_id IS NULL OR j.category_id = p_category_id)
  GROUP BY
    j.job_id,
    j.title,
    c.category_name,
    e.company_name,
    j.location,
    j.work_mode,
    j.published_at
  HAVING rating_count > 0
  ORDER BY average_rating DESC, rating_count DESC, view_count DESC, j.published_at DESC
  LIMIT v_limit_rows;
END$$

DROP PROCEDURE IF EXISTS dbProj_get_jobs_by_creator$$
CREATE PROCEDURE dbProj_get_jobs_by_creator (
  IN p_creator_user_id INT UNSIGNED
)
BEGIN
  SELECT
    j.job_id,
    j.title,
    j.status,
    c.category_name,
    e.company_name,
    j.published_at,
    ROUND(AVG(r.rating_value), 2) AS average_rating,
    COUNT(DISTINCT r.rating_id) AS rating_count,
    COUNT(DISTINCT cm.comment_id) AS comment_count
  FROM dbProj_job_listings j
  INNER JOIN dbProj_job_categories c ON c.category_id = j.category_id
  INNER JOIN dbProj_employers e ON e.employer_id = j.employer_id
  LEFT JOIN dbProj_ratings r ON r.job_id = j.job_id
  LEFT JOIN dbProj_comments cm ON cm.job_id = j.job_id AND cm.is_removed = FALSE
  WHERE j.created_by_user_id = p_creator_user_id
  GROUP BY
    j.job_id,
    j.title,
    j.status,
    c.category_name,
    e.company_name,
    j.published_at
  ORDER BY j.created_at DESC;
END$$

DELIMITER ;

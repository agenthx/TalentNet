-- IT8415 Job Portal schema
-- Requirement coverage:
--   - Every table name uses the dbProj_ prefix.
--   - Users, employers, job listings, comments, ratings are included.
--   - FULLTEXT index is added on job title and description.

CREATE DATABASE IF NOT EXISTS dbProj_job_portal
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE dbProj_job_portal;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS dbProj_job_views;
DROP TABLE IF EXISTS dbProj_ratings;
DROP TABLE IF EXISTS dbProj_comments;
DROP TABLE IF EXISTS dbProj_job_media;
DROP TABLE IF EXISTS dbProj_job_listings;
DROP TABLE IF EXISTS dbProj_employers;
DROP TABLE IF EXISTS dbProj_users;
DROP TABLE IF EXISTS dbProj_job_categories;
DROP TABLE IF EXISTS dbProj_roles;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE dbProj_roles (
  role_id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_name VARCHAR(30) NOT NULL,
  role_description VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (role_id),
  UNIQUE KEY dbProj_uq_roles_name (role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dbProj_users (
  user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id TINYINT UNSIGNED NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY dbProj_uq_users_email (email),
  KEY dbProj_idx_users_role (role_id),
  CONSTRAINT dbProj_fk_users_role
    FOREIGN KEY (role_id) REFERENCES dbProj_roles (role_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dbProj_employers (
  employer_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_user_id INT UNSIGNED NOT NULL,
  company_name VARCHAR(160) NOT NULL,
  company_website VARCHAR(255) NULL,
  company_description TEXT NOT NULL,
  logo_path VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (employer_id),
  UNIQUE KEY dbProj_uq_employers_company (company_name),
  KEY dbProj_idx_employers_owner (owner_user_id),
  CONSTRAINT dbProj_fk_employers_owner
    FOREIGN KEY (owner_user_id) REFERENCES dbProj_users (user_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dbProj_job_categories (
  category_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_name VARCHAR(100) NOT NULL,
  category_slug VARCHAR(100) NOT NULL,
  category_description VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (category_id),
  UNIQUE KEY dbProj_uq_categories_name (category_name),
  UNIQUE KEY dbProj_uq_categories_slug (category_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dbProj_job_listings (
  job_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  employer_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  created_by_user_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  short_description VARCHAR(500) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(140) NOT NULL,
  employment_type ENUM('Full-time', 'Part-time', 'Contract', 'Internship') NOT NULL,
  work_mode ENUM('On-site', 'Hybrid', 'Remote') NOT NULL,
  salary_min DECIMAL(10, 2) NULL,
  salary_max DECIMAL(10, 2) NULL,
  currency CHAR(3) NOT NULL DEFAULT 'OMR',
  application_url VARCHAR(255) NULL,
  status ENUM('draft', 'published', 'closed', 'removed') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (job_id),
  KEY dbProj_idx_jobs_employer (employer_id),
  KEY dbProj_idx_jobs_category_status (category_id, status, published_at),
  KEY dbProj_idx_jobs_creator (created_by_user_id),
  KEY dbProj_idx_jobs_published (published_at),
  FULLTEXT KEY dbProj_ft_jobs_title_description (title, description),
  CONSTRAINT dbProj_fk_jobs_employer
    FOREIGN KEY (employer_id) REFERENCES dbProj_employers (employer_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT dbProj_fk_jobs_category
    FOREIGN KEY (category_id) REFERENCES dbProj_job_categories (category_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT dbProj_fk_jobs_creator
    FOREIGN KEY (created_by_user_id) REFERENCES dbProj_users (user_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT dbProj_chk_jobs_salary_range
    CHECK (salary_min IS NULL OR salary_max IS NULL OR salary_min <= salary_max)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dbProj_job_media (
  media_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id INT UNSIGNED NOT NULL,
  media_type ENUM('image', 'video', 'audio', 'document') NOT NULL DEFAULT 'image',
  file_path VARCHAR(255) NOT NULL,
  alt_text VARCHAR(180) NULL,
  is_primary BOOLEAN NOT NULL DEFAULT FALSE,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (media_id),
  KEY dbProj_idx_job_media_job_primary (job_id, is_primary),
  CONSTRAINT dbProj_fk_job_media_job
    FOREIGN KEY (job_id) REFERENCES dbProj_job_listings (job_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dbProj_comments (
  comment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  comment_text VARCHAR(1000) NOT NULL,
  is_removed BOOLEAN NOT NULL DEFAULT FALSE,
  removed_by_user_id INT UNSIGNED NULL,
  removed_reason VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (comment_id),
  KEY dbProj_idx_comments_job_created (job_id, created_at),
  KEY dbProj_idx_comments_user (user_id),
  KEY dbProj_idx_comments_removed_by (removed_by_user_id),
  CONSTRAINT dbProj_fk_comments_job
    FOREIGN KEY (job_id) REFERENCES dbProj_job_listings (job_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT dbProj_fk_comments_user
    FOREIGN KEY (user_id) REFERENCES dbProj_users (user_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT dbProj_fk_comments_removed_by
    FOREIGN KEY (removed_by_user_id) REFERENCES dbProj_users (user_id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dbProj_ratings (
  rating_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  rating_value TINYINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (rating_id),
  UNIQUE KEY dbProj_uq_ratings_job_user (job_id, user_id),
  KEY dbProj_idx_ratings_job_value (job_id, rating_value),
  KEY dbProj_idx_ratings_user (user_id),
  CONSTRAINT dbProj_fk_ratings_job
    FOREIGN KEY (job_id) REFERENCES dbProj_job_listings (job_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT dbProj_fk_ratings_user
    FOREIGN KEY (user_id) REFERENCES dbProj_users (user_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT dbProj_chk_rating_value
    CHECK (rating_value BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dbProj_job_views (
  view_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id INT UNSIGNED NOT NULL,
  viewer_user_id INT UNSIGNED NULL,
  viewer_ip VARCHAR(45) NULL,
  viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (view_id),
  KEY dbProj_idx_views_job_date (job_id, viewed_at),
  KEY dbProj_idx_views_user (viewer_user_id),
  CONSTRAINT dbProj_fk_views_job
    FOREIGN KEY (job_id) REFERENCES dbProj_job_listings (job_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT dbProj_fk_views_user
    FOREIGN KEY (viewer_user_id) REFERENCES dbProj_users (user_id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

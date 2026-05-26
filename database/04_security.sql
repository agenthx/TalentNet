-- IT8415 Job Portal database security
-- Run this as a MySQL admin user, such as the XAMPP root account.
-- Replace the ChangeMe_* passwords before deployment or live demonstration.

USE dbProj_job_portal;

CREATE USER IF NOT EXISTS 'dbProj_app_auth'@'localhost'
  IDENTIFIED BY 'ChangeMe_Auth123!';
CREATE USER IF NOT EXISTS 'dbProj_app_viewer'@'localhost'
  IDENTIFIED BY 'ChangeMe_Viewer123!';
CREATE USER IF NOT EXISTS 'dbProj_app_creator'@'localhost'
  IDENTIFIED BY 'ChangeMe_Creator123!';
CREATE USER IF NOT EXISTS 'dbProj_app_admin'@'localhost'
  IDENTIFIED BY 'ChangeMe_Admin123!';

-- Authentication account: only enough access for signup/login/password checks.
GRANT SELECT (user_id, role_id, full_name, email, password_hash, is_active)
  ON dbProj_job_portal.dbProj_users
  TO 'dbProj_app_auth'@'localhost';
GRANT INSERT (role_id, full_name, email, password_hash, phone)
  ON dbProj_job_portal.dbProj_users
  TO 'dbProj_app_auth'@'localhost';
GRANT UPDATE (password_hash, updated_at)
  ON dbProj_job_portal.dbProj_users
  TO 'dbProj_app_auth'@'localhost';
GRANT SELECT
  ON dbProj_job_portal.dbProj_roles
  TO 'dbProj_app_auth'@'localhost';

-- Viewer account: browse/search published data, add views, comments, and ratings.
GRANT SELECT
  ON dbProj_job_portal.dbProj_roles
  TO 'dbProj_app_viewer'@'localhost';
GRANT SELECT (user_id, role_id, full_name, email, is_active, created_at)
  ON dbProj_job_portal.dbProj_users
  TO 'dbProj_app_viewer'@'localhost';
GRANT SELECT
  ON dbProj_job_portal.dbProj_employers
  TO 'dbProj_app_viewer'@'localhost';
GRANT SELECT
  ON dbProj_job_portal.dbProj_job_categories
  TO 'dbProj_app_viewer'@'localhost';
GRANT SELECT
  ON dbProj_job_portal.dbProj_job_listings
  TO 'dbProj_app_viewer'@'localhost';
GRANT SELECT
  ON dbProj_job_portal.dbProj_job_media
  TO 'dbProj_app_viewer'@'localhost';
GRANT SELECT, INSERT
  ON dbProj_job_portal.dbProj_comments
  TO 'dbProj_app_viewer'@'localhost';
GRANT SELECT, INSERT, UPDATE
  ON dbProj_job_portal.dbProj_ratings
  TO 'dbProj_app_viewer'@'localhost';
GRANT INSERT
  ON dbProj_job_portal.dbProj_job_views
  TO 'dbProj_app_viewer'@'localhost';
GRANT EXECUTE
  ON PROCEDURE dbProj_job_portal.dbProj_get_top_rated_jobs
  TO 'dbProj_app_viewer'@'localhost';

-- Creator account: viewer permissions plus controlled content management.
GRANT SELECT
  ON dbProj_job_portal.dbProj_roles
  TO 'dbProj_app_creator'@'localhost';
GRANT SELECT (user_id, role_id, full_name, email, phone, is_active, created_at, updated_at)
  ON dbProj_job_portal.dbProj_users
  TO 'dbProj_app_creator'@'localhost';
GRANT SELECT, INSERT, UPDATE
  ON dbProj_job_portal.dbProj_employers
  TO 'dbProj_app_creator'@'localhost';
GRANT SELECT
  ON dbProj_job_portal.dbProj_job_categories
  TO 'dbProj_app_creator'@'localhost';
GRANT SELECT, INSERT, UPDATE
  ON dbProj_job_portal.dbProj_job_listings
  TO 'dbProj_app_creator'@'localhost';
GRANT SELECT, INSERT, UPDATE
  ON dbProj_job_portal.dbProj_job_media
  TO 'dbProj_app_creator'@'localhost';
GRANT SELECT, INSERT
  ON dbProj_job_portal.dbProj_comments
  TO 'dbProj_app_creator'@'localhost';
GRANT SELECT, INSERT, UPDATE
  ON dbProj_job_portal.dbProj_ratings
  TO 'dbProj_app_creator'@'localhost';
GRANT SELECT, INSERT
  ON dbProj_job_portal.dbProj_job_views
  TO 'dbProj_app_creator'@'localhost';
GRANT EXECUTE
  ON PROCEDURE dbProj_job_portal.dbProj_get_top_rated_jobs
  TO 'dbProj_app_creator'@'localhost';
GRANT EXECUTE
  ON PROCEDURE dbProj_job_portal.dbProj_get_jobs_by_creator
  TO 'dbProj_app_creator'@'localhost';

-- Admin account: manage users, content, moderation, and reporting.
GRANT SELECT, INSERT, UPDATE, DELETE
  ON dbProj_job_portal.dbProj_roles
  TO 'dbProj_app_admin'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE
  ON dbProj_job_portal.dbProj_users
  TO 'dbProj_app_admin'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE
  ON dbProj_job_portal.dbProj_employers
  TO 'dbProj_app_admin'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE
  ON dbProj_job_portal.dbProj_job_categories
  TO 'dbProj_app_admin'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE
  ON dbProj_job_portal.dbProj_job_listings
  TO 'dbProj_app_admin'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE
  ON dbProj_job_portal.dbProj_job_media
  TO 'dbProj_app_admin'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE
  ON dbProj_job_portal.dbProj_comments
  TO 'dbProj_app_admin'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE
  ON dbProj_job_portal.dbProj_ratings
  TO 'dbProj_app_admin'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE
  ON dbProj_job_portal.dbProj_job_views
  TO 'dbProj_app_admin'@'localhost';
GRANT EXECUTE
  ON PROCEDURE dbProj_job_portal.dbProj_get_top_rated_jobs
  TO 'dbProj_app_admin'@'localhost';
GRANT EXECUTE
  ON PROCEDURE dbProj_job_portal.dbProj_get_jobs_by_creator
  TO 'dbProj_app_admin'@'localhost';

FLUSH PRIVILEGES;

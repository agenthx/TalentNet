-- IT8415 Job Portal database security
-- Run as a MySQL administrator after importing:
--   01_schema.sql
--   03_stored_procedures.sql
--   02_seed_data.sql
--
-- IMPORTANT:
-- Replace dbProj_job_portal with the deployed database name before running
-- this script on the university server, for example db202302905.
-- Replace all ChangeMe_* passwords before demonstration or deployment.

CREATE USER IF NOT EXISTS 'dbProj_app_auth'@'localhost'
  IDENTIFIED BY 'ChangeMe_Auth123!';
CREATE USER IF NOT EXISTS 'dbProj_app_viewer'@'localhost'
  IDENTIFIED BY 'ChangeMe_Viewer123!';
CREATE USER IF NOT EXISTS 'dbProj_app_creator'@'localhost'
  IDENTIFIED BY 'ChangeMe_Creator123!';
CREATE USER IF NOT EXISTS 'dbProj_app_admin'@'localhost'
  IDENTIFIED BY 'ChangeMe_Admin123!';

-- Authentication account: only signup/login/password-check access.
GRANT SELECT (user_id, role_id, full_name, email, password_hash, is_active)
  ON dbProj_job_portal.dbProj_users
  TO 'dbProj_app_auth'@'localhost';
GRANT INSERT (role_id, full_name, email, password_hash, phone, is_active)
  ON dbProj_job_portal.dbProj_users
  TO 'dbProj_app_auth'@'localhost';
GRANT SELECT
  ON dbProj_job_portal.dbProj_roles
  TO 'dbProj_app_auth'@'localhost';

-- Viewer account: browse/search, record views, add comments, and rate jobs.
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
GRANT SELECT, INSERT
  ON dbProj_job_portal.dbProj_job_views
  TO 'dbProj_app_viewer'@'localhost';
GRANT EXECUTE
  ON PROCEDURE dbProj_job_portal.dbProj_get_top_rated_jobs
  TO 'dbProj_app_viewer'@'localhost';

-- Creator account: viewer permissions plus employer/job/media management.
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
GRANT EXECUTE
  ON PROCEDURE dbProj_job_portal.dbProj_get_jobs_by_employer
  TO 'dbProj_app_creator'@'localhost';

-- Admin account: complete management, moderation, and reporting access.
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
GRANT EXECUTE
  ON PROCEDURE dbProj_job_portal.dbProj_get_jobs_by_employer
  TO 'dbProj_app_admin'@'localhost';

FLUSH PRIVILEGES;

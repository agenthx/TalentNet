# IT8415 - TalentNet Job Portal

TalentNet is a PHP, MySQL, and Apache job portal for the IT8415 Database Programming 2 group project. It includes public job browsing/search, secure authentication, role-based administration, comments, ratings, reports, stored procedures, and role-level database security scripts.

## Tech Stack

- Frontend: HTML5, CSS3, Bootstrap 5, Bootstrap Icons, JavaScript, jQuery, AJAX
- Backend: PHP 8 with MySQLi prepared statements
- Database: MySQL / MariaDB, InnoDB tables
- Server: Apache / university remote web server

## Implemented Scope

- M1 Database: prefixed `dbProj_` tables, seed data, FULLTEXT search index, stored procedures, and database security grants.
- M2 Auth and Admin: registration, login, session expiry, role-based access, user activation/deactivation, role changes, job removal, comment hiding, and two admin reports.
- M3 Home and Search: category navigation, newest-first feed, job cards with media/logo area, pagination, FULLTEXT keyword search, employer-name search, date-range search, category filter, and popularity sorting.
- M4 Interactive content: AJAX/jQuery star ratings and asynchronous comments are present on job details.

## Database Initialization

Import the SQL files from the `database/` folder in this order:

1. `database/01_schema.sql` - creates all required `dbProj_` tables and indexes.
2. `database/03_stored_procedures.sql` - creates report/search helper procedures.
3. `database/02_seed_data.sql` - inserts roles, users, employers, categories, jobs, media records, comments, ratings, and views.
4. `database/04_security.sql` - optional/admin-level script for database users and role-level grants.

Before running `04_security.sql`, replace `dbProj_job_portal` with the deployed database name, for example `db202302905`, and replace every `ChangeMe_*` password.

## Database Security

The application code uses MySQLi prepared statements for user-controlled input. The `database/04_security.sql` script adds database-level separation for these accounts:

- `dbProj_app_auth` for signup/login checks.
- `dbProj_app_viewer` for browsing, comments, ratings, and views.
- `dbProj_app_creator` for employer/job/media management.
- `dbProj_app_admin` for administration, moderation, and reporting.

## Demo Accounts

All seeded accounts use this demo password:

```text
password
```

Useful seeded accounts:

- Admin: `admin@jobportal.local`
- Creator: `omar.creator@jobportal.local`
- Creator: `lina.creator@jobportal.local`
- Viewer: `sara.viewer@jobportal.local`
- Viewer: `khalid.viewer@jobportal.local`

## Remote Deployment Setup

1. Clone the repository and open it as a NetBeans PHP project.
2. Copy `db.php.template.php` to `db.php`.
3. Put the remote university database credentials into `db.php`.
4. Configure NetBeans upload:
   - Run As: Remote Web Site
   - Project URL: `http://20.74.143.233/~uStudentID/TalentNet`
   - Index File: `index.php`
   - Upload Directory: `/TalentNet`
   - Upload Files: On Run

`db.php` is intentionally ignored by Git because it contains local or remote credentials.

## Repository Structure

```text
database/
  01_schema.sql
  02_seed_data.sql
  03_stored_procedures.sql
  04_security.sql
assets/css/app.css
admin_dashboard.php
admin_reports.php
ajax_handler.php
auth_helper.php
categories.php
db.php.template.php
footer.php
header.php
index.php
job_details.php
login.php
logout.php
register.php
```

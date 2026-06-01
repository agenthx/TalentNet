# IT8415 - Job Portal Web Application

## Project Overview
This repository contains a data-driven Job Portal web application developed for IT8415 (Database Programming 2). The platform connects job seekers with employers, featuring secure authentication, full-text job searching, role-based access control, and a dynamic content management system. 

## Team Roles
This project was developed collaboratively by a four-person team:
* **M1 (Database Lead):** Database design (ERD), SQL schema creation, stored procedures, and test data seeding.
* **M2 (Auth & Admin):** Secure user registration (bcrypt), session management, and Administrator dashboards/reporting.
* **M3 (Home Page & Search):** Public UI shell (Bootstrap 5), paginated job feed, and dynamic FULLTEXT search engine.
* **M4 (Content & Testing):** Employer job posting panel, interactive comments/ratings, and comprehensive QA testing.

## Tech Stack
* **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript / jQuery, AJAX
* **Backend:** PHP 8 (using PDO Prepared Statements)
* **Database:** MySQL (InnoDB engine)
* **Server:** Apache (Remote University Server)

## Remote Deployment Setup (NetBeans)

To run this project on the remote university server using NetBeans, follow these steps exactly:

**1. Database Initialization**
Open the remote server's phpMyAdmin. Import the database files from the `database/` folder in the following strict order to respect structural constraints:
1. `database/01_schema.sql` (Creates the tables and relationships)
2. `database/03_stored_procedures.sql` (Adds required backend procedures and triggers)
3. `database/02_seed_data.sql` (Populates dummy users, categories, and jobs)

**2. Clone & Configure Workspace**
Clone the repository to your local machine and open the project folder in NetBeans.

**3. Set Up Database Credentials**
For security reasons, database credentials are not tracked in Git.
* Copy the contents of `db.php.template.php`.
* Create a new file named `db.php` in the same directory.
* Paste the template contents into `db.php` and fill in your remote university database username and password.

**4. NetBeans Run Configuration**
Configure NetBeans to automatically upload your files to the remote server via FTP. Right-click your project folder in NetBeans -> **Properties** -> **Run Configuration** and apply these exact settings:

* **Run As:** Remote Web Site (FTP, SFTP)
* **Project URL:** `http://20.74.143.233/~uStudentID/TalentNet` *(Example: If your student ID is 202304829, your URL is `http://20.74.143.233/~u202304829/TalentNet`)*
* **Index File:** `index.php`
* **Remote Connection:** `20.74.143.233` *(Click 'Manage...' to input your remote FTP username and password)*
* **Upload Directory:** `/TalentNet`
* **Upload Files:** On Run

## Repository Structure

```text
├── database/                 # SQL scripts for schema, procedures, and seeding
├── docs/                     # Project documentation (ERD, Test Plans, Checklists)
├── examples/                 # Code reference snippets for the team
├── .gitignore                # Specifies intentionally untracked files (e.g., db.php)
├── README.md                 # Project documentation and setup instructions
├── admin_dashboard.php       # Admin control panel interface
├── admin_reports.php         # Admin reporting logic and data views
├── ajax_handler.php          # Processes asynchronous Javascript/backend requests
├── auth_helper.php           # Session management and access control logic
├── categories.php            # Job category overview and sorting
├── db.php                    # Local/Remote PDO database connection string (Ignored)
├── db.php.template.php       # Template for secure database connection string
├── footer.php                # Global UI footer and script tags
├── header.php                # Global UI navigation and Bootstrap configuration
├── index.php                 # Dynamic Home Page and Search Engine
├── job_details.php           # Individual job posting view and comment layout
├── login.php                 # User authentication interface
├── logout.php                # Session termination logic
└── register.php              # New user account creation interface

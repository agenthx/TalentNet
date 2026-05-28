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
* **Server:** Apache (XAMPP environment)

## Local Development Setup (XAMPP)

To run this project locally, follow these steps exactly:

**1. File Placement**
Move the entire project folder into your XAMPP web directory (e.g., `C:\xampp\htdocs\job-portal`).

**2. Database Initialization**
Start the Apache and MySQL modules in the XAMPP Control Panel. Open your browser and navigate to `http://localhost/phpmyadmin`. Create a new database named `dbProj_job_portal`. 

**3. Import SQL Scripts**
Import the database files in the following strict order to respect foreign key constraints:
1. `database/01_schema.sql` (Creates the tables and relationships)
2. `database/02_seed_data.sql` (Populates dummy users, categories, and 15+ jobs)
3. `database/03_stored_procedures.sql` (Adds required backend procedures)

**4. Launch the Application**
Navigate to `http://localhost/job-portal/index.php` to view the application.

## Repository Structure

```text
├── database/                 # SQL scripts for schema, seeding, and security
├── docs/                     # Project documentation (ERD, Test Plans, Checklists)
├── examples/                 # Code reference snippets for the team
├── uploads/                  # Directory for user-uploaded media (logos, documents)
├── css/                      # Custom stylesheets
├── js/                       # Custom JavaScript and AJAX handlers
├── header.php                # Global UI navigation and Bootstrap configuration
├── footer.php                # Global UI footer and script tags
├── db.php                    # Secure PDO database connection string
├── index.php                 # Dynamic Home Page and Search Engine
├── categories.php            # Job category overview and sorting
├── job_details.php           # Individual job posting view and comment layout
└── README.md                 # Project documentation

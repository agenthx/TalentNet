-- IT8415 Job Portal seed data
-- Includes 15 published job listings: 5 per category.
-- Demo password for all seeded users: password

-- USE dbProj_job_portal;

SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM dbProj_job_views;
DELETE FROM dbProj_ratings;
DELETE FROM dbProj_comments;
DELETE FROM dbProj_job_media;
DELETE FROM dbProj_job_listings;
DELETE FROM dbProj_employers;
DELETE FROM dbProj_users;
DELETE FROM dbProj_job_categories;
DELETE FROM dbProj_roles;
ALTER TABLE dbProj_job_views AUTO_INCREMENT = 1;
ALTER TABLE dbProj_ratings AUTO_INCREMENT = 1;
ALTER TABLE dbProj_comments AUTO_INCREMENT = 1;
ALTER TABLE dbProj_job_media AUTO_INCREMENT = 1;
ALTER TABLE dbProj_job_listings AUTO_INCREMENT = 1;
ALTER TABLE dbProj_employers AUTO_INCREMENT = 1;
ALTER TABLE dbProj_users AUTO_INCREMENT = 1;
ALTER TABLE dbProj_job_categories AUTO_INCREMENT = 1;
ALTER TABLE dbProj_roles AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO dbProj_roles (role_id, role_name, role_description) VALUES
  (1, 'viewer', 'Can browse, search, view comments, add comments, and rate published jobs.'),
  (2, 'creator', 'Can create, edit, publish, and view own job listings.'),
  (3, 'admin', 'Can manage users, all job listings, comments, and reports.');

INSERT INTO dbProj_users
  (user_id, role_id, full_name, email, password_hash, phone, is_active)
VALUES
  (1, 3, 'Aisha Admin', 'admin@jobportal.local', '$2y$10$ftiFdWEOci9KJnCdtOWHUunnSXjEE8CB3lX15SnRHDazwBDPS/Zd.', '+96890000001', TRUE),
  (2, 2, 'Omar Employer', 'omar.creator@jobportal.local', '$2y$10$ftiFdWEOci9KJnCdtOWHUunnSXjEE8CB3lX15SnRHDazwBDPS/Zd.', '+96890000002', TRUE),
  (3, 2, 'Lina Hiring', 'lina.creator@jobportal.local', '$2y$10$ftiFdWEOci9KJnCdtOWHUunnSXjEE8CB3lX15SnRHDazwBDPS/Zd.', '+96890000003', TRUE),
  (4, 2, 'Farah Recruiter', 'farah.creator@jobportal.local', '$2y$10$ftiFdWEOci9KJnCdtOWHUunnSXjEE8CB3lX15SnRHDazwBDPS/Zd.', '+96890000004', TRUE),
  (5, 1, 'Sara Viewer', 'sara.viewer@jobportal.local', '$2y$10$ftiFdWEOci9KJnCdtOWHUunnSXjEE8CB3lX15SnRHDazwBDPS/Zd.', '+96890000005', TRUE),
  (6, 1, 'Khalid Viewer', 'khalid.viewer@jobportal.local', '$2y$10$ftiFdWEOci9KJnCdtOWHUunnSXjEE8CB3lX15SnRHDazwBDPS/Zd.', '+96890000006', TRUE),
  (7, 1, 'Noor Viewer', 'noor.viewer@jobportal.local', '$2y$10$ftiFdWEOci9KJnCdtOWHUunnSXjEE8CB3lX15SnRHDazwBDPS/Zd.', '+96890000007', TRUE),
  (8, 1, 'Youssef Viewer', 'youssef.viewer@jobportal.local', '$2y$10$ftiFdWEOci9KJnCdtOWHUunnSXjEE8CB3lX15SnRHDazwBDPS/Zd.', '+96890000008', TRUE),
  (9, 1, 'Mariam Viewer', 'mariam.viewer@jobportal.local', '$2y$10$ftiFdWEOci9KJnCdtOWHUunnSXjEE8CB3lX15SnRHDazwBDPS/Zd.', '+96890000009', TRUE);

INSERT INTO dbProj_job_categories
  (category_id, category_name, category_slug, category_description)
VALUES
  (1, 'Software Development', 'software-development', 'Programming, QA, DevOps, and web application roles.'),
  (2, 'Data & Analytics', 'data-analytics', 'Data analysis, reporting, database, and machine learning roles.'),
  (3, 'Design & Marketing', 'design-marketing', 'Product design, UX, content, SEO, and marketing roles.');

INSERT INTO dbProj_employers
  (employer_id, owner_user_id, company_name, company_website, company_description, logo_path)
VALUES
  (1, 2, 'Gulf Tech Solutions', 'https://example.com/gulf-tech', 'Software house building web systems for regional clients.', 'uploads/logos/gulf-tech.png'),
  (2, 3, 'Riyadh Data Lab', 'https://example.com/riyadh-data-lab', 'Analytics consultancy focused on dashboards and reporting.', 'uploads/logos/riyadh-data-lab.png'),
  (3, 4, 'Creative Souq', 'https://example.com/creative-souq', 'Design and digital marketing agency for local brands.', 'uploads/logos/creative-souq.png'),
  (4, 2, 'Future Finance Group', 'https://example.com/future-finance', 'Financial technology company modernizing customer platforms.', 'uploads/logos/future-finance.png'),
  (5, 3, 'HealthNet Arabia', 'https://example.com/healthnet-arabia', 'Healthcare technology provider for clinics and hospitals.', 'uploads/logos/healthnet-arabia.png');

INSERT INTO dbProj_job_listings
  (job_id, employer_id, category_id, created_by_user_id, title, short_description, description, location, employment_type, work_mode, salary_min, salary_max, currency, application_url, status, published_at)
VALUES
  (1, 1, 1, 2, 'Junior PHP Developer', 'Build and maintain PHP pages for a growing job portal team.', 'The Junior PHP Developer will work with PHP, MySQL, HTML, CSS, and JavaScript to build secure server-side pages, validate forms, and integrate prepared SQL statements.', 'Muscat, Oman', 'Full-time', 'Hybrid', 450.00, 700.00, 'OMR', 'https://example.com/jobs/junior-php-developer', 'published', '2026-05-01 09:00:00'),
  (2, 1, 1, 2, 'Backend Laravel Developer', 'Develop APIs and admin features for content-driven platforms.', 'The Backend Laravel Developer will design REST endpoints, implement authentication, write MySQL queries, and support reporting features for web applications.', 'Muscat, Oman', 'Full-time', 'On-site', 800.00, 1200.00, 'OMR', 'https://example.com/jobs/backend-laravel-developer', 'published', '2026-05-02 10:00:00'),
  (3, 4, 1, 2, 'Frontend React Developer', 'Create responsive screens for finance dashboards and user portals.', 'The Frontend React Developer will turn interface designs into accessible pages, connect with PHP APIs, and improve performance for authenticated users.', 'Riyadh, Saudi Arabia', 'Contract', 'Remote', 900.00, 1500.00, 'SAR', 'https://example.com/jobs/frontend-react-developer', 'published', '2026-05-03 11:00:00'),
  (4, 5, 1, 3, 'QA Automation Engineer', 'Test healthcare web workflows using automated and manual test plans.', 'The QA Automation Engineer will create test cases, automate browser checks, verify validation rules, and report issues before production releases.', 'Salalah, Oman', 'Full-time', 'Hybrid', 650.00, 950.00, 'OMR', 'https://example.com/jobs/qa-automation-engineer', 'published', '2026-05-04 12:00:00'),
  (5, 4, 1, 2, 'DevOps Support Engineer', 'Support deployment, backups, monitoring, and release routines.', 'The DevOps Support Engineer will manage Apache, MySQL backups, scheduled scripts, version control workflows, and server monitoring for web systems.', 'Dubai, UAE', 'Full-time', 'On-site', 900.00, 1300.00, 'AED', 'https://example.com/jobs/devops-support-engineer', 'published', '2026-05-05 13:00:00'),
  (6, 2, 2, 3, 'Data Analyst', 'Analyze job portal traffic, applications, and category performance.', 'The Data Analyst will prepare SQL queries, clean datasets, build dashboards, and communicate trends from job listing views, ratings, and applications.', 'Riyadh, Saudi Arabia', 'Full-time', 'Hybrid', 700.00, 1000.00, 'SAR', 'https://example.com/jobs/data-analyst', 'published', '2026-05-06 09:30:00'),
  (7, 2, 2, 3, 'Business Intelligence Developer', 'Build reporting dashboards for management and creator teams.', 'The Business Intelligence Developer will write optimized SQL, model metrics, create date-range reports, and visualize most popular content.', 'Muscat, Oman', 'Full-time', 'Remote', 750.00, 1100.00, 'OMR', 'https://example.com/jobs/business-intelligence-developer', 'published', '2026-05-07 10:30:00'),
  (8, 1, 2, 2, 'Database Administrator', 'Maintain MySQL databases, indexes, user accounts, and backups.', 'The Database Administrator will tune MySQL indexes, manage role-level database privileges, review stored procedures, and maintain reliable backups.', 'Muscat, Oman', 'Full-time', 'On-site', 850.00, 1250.00, 'OMR', 'https://example.com/jobs/database-administrator', 'published', '2026-05-08 11:30:00'),
  (9, 2, 2, 3, 'Machine Learning Intern', 'Assist with job recommendation experiments and text classification.', 'The Machine Learning Intern will prepare training datasets, test ranking models, document findings, and support search relevance experiments.', 'Riyadh, Saudi Arabia', 'Internship', 'Hybrid', 250.00, 400.00, 'SAR', 'https://example.com/jobs/machine-learning-intern', 'published', '2026-05-09 12:30:00'),
  (10, 5, 2, 3, 'Reporting Specialist', 'Prepare operational reports for healthcare recruitment workflows.', 'The Reporting Specialist will create SQL reports, validate source data, explain trends, and export weekly summaries for administrators.', 'Nizwa, Oman', 'Part-time', 'Remote', 300.00, 550.00, 'OMR', 'https://example.com/jobs/reporting-specialist', 'published', '2026-05-10 13:30:00'),
  (11, 3, 3, 4, 'UI UX Designer', 'Design clean, responsive screens for job search and creator panels.', 'The UI UX Designer will create wireframes, prototypes, responsive layouts, and usability improvements for search, comments, and rating workflows.', 'Muscat, Oman', 'Full-time', 'Hybrid', 600.00, 950.00, 'OMR', 'https://example.com/jobs/ui-ux-designer', 'published', '2026-05-11 09:15:00'),
  (12, 3, 3, 4, 'Digital Marketing Executive', 'Plan campaigns for employers and featured job categories.', 'The Digital Marketing Executive will manage campaign calendars, write channel copy, track engagement, and report conversion results.', 'Dubai, UAE', 'Full-time', 'On-site', 650.00, 1000.00, 'AED', 'https://example.com/jobs/digital-marketing-executive', 'published', '2026-05-12 10:15:00'),
  (13, 3, 3, 4, 'Content Creator', 'Produce job search guides, employer posts, and short videos.', 'The Content Creator will write articles, prepare social posts, edit short videos, and coordinate media uploads for published content.', 'Muscat, Oman', 'Part-time', 'Remote', 250.00, 450.00, 'OMR', 'https://example.com/jobs/content-creator', 'published', '2026-05-13 11:15:00'),
  (14, 3, 3, 4, 'SEO Specialist', 'Improve search visibility for category and employer pages.', 'The SEO Specialist will optimize metadata, conduct keyword research, review content structure, and monitor organic search performance.', 'Riyadh, Saudi Arabia', 'Contract', 'Remote', 500.00, 850.00, 'SAR', 'https://example.com/jobs/seo-specialist', 'published', '2026-05-14 12:15:00'),
  (15, 4, 3, 2, 'Product Designer', 'Shape job portal product flows from research through handoff.', 'The Product Designer will run discovery sessions, map user journeys, design prototypes, and collaborate with developers on polished product experiences.', 'Muscat, Oman', 'Full-time', 'Hybrid', 850.00, 1300.00, 'OMR', 'https://example.com/jobs/product-designer', 'published', '2026-05-15 13:15:00');

INSERT INTO dbProj_job_media
  (job_id, media_type, file_path, alt_text, is_primary)
VALUES
  (1, 'image', 'uploads/jobs/junior-php-developer.jpg', 'Developer working on PHP code', TRUE),
  (2, 'image', 'uploads/jobs/backend-laravel-developer.jpg', 'Backend developer workspace', TRUE),
  (3, 'image', 'uploads/jobs/frontend-react-developer.jpg', 'Responsive interface mockups', TRUE),
  (4, 'image', 'uploads/jobs/qa-automation-engineer.jpg', 'QA engineer testing application screens', TRUE),
  (5, 'image', 'uploads/jobs/devops-support-engineer.jpg', 'Server monitoring dashboard', TRUE),
  (6, 'image', 'uploads/jobs/data-analyst.jpg', 'Analyst reviewing charts', TRUE),
  (7, 'image', 'uploads/jobs/business-intelligence-developer.jpg', 'BI dashboard on monitor', TRUE),
  (8, 'image', 'uploads/jobs/database-administrator.jpg', 'Database server dashboard', TRUE),
  (9, 'image', 'uploads/jobs/machine-learning-intern.jpg', 'Machine learning notebook and charts', TRUE),
  (10, 'image', 'uploads/jobs/reporting-specialist.jpg', 'Weekly report spreadsheet', TRUE),
  (11, 'image', 'uploads/jobs/ui-ux-designer.jpg', 'UX wireframes and prototype', TRUE),
  (12, 'image', 'uploads/jobs/digital-marketing-executive.jpg', 'Marketing campaign board', TRUE),
  (13, 'image', 'uploads/jobs/content-creator.jpg', 'Content creator recording short video', TRUE),
  (14, 'image', 'uploads/jobs/seo-specialist.jpg', 'SEO analytics report', TRUE),
  (15, 'image', 'uploads/jobs/product-designer.jpg', 'Product design handoff screen', TRUE),
  (13, 'video', 'uploads/jobs/content-creator-intro.mp4', 'Short introduction video for content role', FALSE),
  (15, 'document', 'uploads/jobs/product-designer-brief.pdf', 'Portfolio brief for product designer applicants', FALSE);

INSERT INTO dbProj_comments
  (job_id, user_id, comment_text, is_removed, removed_by_user_id, removed_reason)
VALUES
  (1, 5, 'Good entry-level role for someone with PHP and MySQL basics.', FALSE, NULL, NULL),
  (2, 6, 'The API and reporting responsibilities are clearly explained.', FALSE, NULL, NULL),
  (3, 7, 'Remote contract work is useful for experienced frontend developers.', FALSE, NULL, NULL),
  (4, 8, 'The testing duties match what our course test plan requires.', FALSE, NULL, NULL),
  (5, 9, 'Nice to see backups and monitoring listed in the role.', FALSE, NULL, NULL),
  (6, 5, 'This looks suitable for SQL and dashboard practice.', FALSE, NULL, NULL),
  (7, 6, 'Date-range reports are important for admin features.', FALSE, NULL, NULL),
  (8, 7, 'Strong match for database programming students.', FALSE, NULL, NULL),
  (9, 8, 'The internship sounds practical for recommendation experiments.', FALSE, NULL, NULL),
  (10, 9, 'Part-time remote reporting could work well for students.', FALSE, NULL, NULL),
  (11, 5, 'The UX role includes the core portal screens.', FALSE, NULL, NULL),
  (12, 6, 'Campaign reporting is a useful marketing requirement.', FALSE, NULL, NULL),
  (13, 7, 'Good that media uploads are part of this role.', FALSE, NULL, NULL),
  (14, 8, 'SEO work connects well with search visibility.', FALSE, NULL, NULL),
  (15, 9, 'Product journeys and prototypes sound interesting.', FALSE, NULL, NULL),
  (11, 6, 'Removed demo comment for admin moderation testing.', TRUE, 1, 'Demonstrates admin comment removal.');

INSERT INTO dbProj_ratings
  (job_id, user_id, rating_value)
VALUES
  (1, 5, 5), (1, 6, 4), (1, 7, 4),
  (2, 5, 4), (2, 6, 5), (2, 8, 5),
  (3, 5, 4), (3, 7, 5), (3, 9, 4),
  (4, 6, 5), (4, 8, 4), (4, 9, 4),
  (5, 5, 3), (5, 7, 4), (5, 8, 4),
  (6, 5, 5), (6, 6, 5), (6, 9, 4),
  (7, 6, 4), (7, 7, 5), (7, 8, 5),
  (8, 5, 5), (8, 7, 5), (8, 9, 5),
  (9, 6, 4), (9, 8, 4), (9, 9, 5),
  (10, 5, 4), (10, 7, 4), (10, 8, 3),
  (11, 5, 5), (11, 6, 4), (11, 7, 5),
  (12, 6, 4), (12, 8, 4), (12, 9, 4),
  (13, 5, 4), (13, 7, 5), (13, 9, 5),
  (14, 6, 3), (14, 8, 4), (14, 9, 4),
  (15, 5, 5), (15, 6, 5), (15, 8, 5);

INSERT INTO dbProj_job_views
  (job_id, viewer_user_id, viewer_ip, viewed_at)
VALUES
  (1, 5, '127.0.0.1', '2026-05-16 09:00:00'), (1, 6, '127.0.0.1', '2026-05-16 09:05:00'), (1, 7, '127.0.0.1', '2026-05-16 09:10:00'),
  (2, 5, '127.0.0.1', '2026-05-16 10:00:00'), (2, 6, '127.0.0.1', '2026-05-16 10:05:00'), (2, 8, '127.0.0.1', '2026-05-16 10:10:00'), (2, 9, '127.0.0.1', '2026-05-16 10:15:00'),
  (3, 7, '127.0.0.1', '2026-05-17 09:00:00'), (3, 8, '127.0.0.1', '2026-05-17 09:05:00'),
  (4, 5, '127.0.0.1', '2026-05-17 10:00:00'), (4, 6, '127.0.0.1', '2026-05-17 10:05:00'), (4, 7, '127.0.0.1', '2026-05-17 10:10:00'),
  (5, 8, '127.0.0.1', '2026-05-18 09:00:00'), (5, 9, '127.0.0.1', '2026-05-18 09:05:00'),
  (6, 5, '127.0.0.1', '2026-05-18 10:00:00'), (6, 6, '127.0.0.1', '2026-05-18 10:05:00'), (6, 7, '127.0.0.1', '2026-05-18 10:10:00'), (6, 8, '127.0.0.1', '2026-05-18 10:15:00'),
  (7, 5, '127.0.0.1', '2026-05-19 09:00:00'), (7, 7, '127.0.0.1', '2026-05-19 09:05:00'), (7, 9, '127.0.0.1', '2026-05-19 09:10:00'),
  (8, 5, '127.0.0.1', '2026-05-19 10:00:00'), (8, 6, '127.0.0.1', '2026-05-19 10:05:00'), (8, 7, '127.0.0.1', '2026-05-19 10:10:00'), (8, 8, '127.0.0.1', '2026-05-19 10:15:00'), (8, 9, '127.0.0.1', '2026-05-19 10:20:00'),
  (9, 6, '127.0.0.1', '2026-05-20 09:00:00'), (9, 8, '127.0.0.1', '2026-05-20 09:05:00'),
  (10, 5, '127.0.0.1', '2026-05-20 10:00:00'), (10, 9, '127.0.0.1', '2026-05-20 10:05:00'),
  (11, 5, '127.0.0.1', '2026-05-21 09:00:00'), (11, 6, '127.0.0.1', '2026-05-21 09:05:00'), (11, 7, '127.0.0.1', '2026-05-21 09:10:00'),
  (12, 6, '127.0.0.1', '2026-05-21 10:00:00'), (12, 8, '127.0.0.1', '2026-05-21 10:05:00'),
  (13, 5, '127.0.0.1', '2026-05-22 09:00:00'), (13, 7, '127.0.0.1', '2026-05-22 09:05:00'), (13, 9, '127.0.0.1', '2026-05-22 09:10:00'),
  (14, 6, '127.0.0.1', '2026-05-22 10:00:00'), (14, 8, '127.0.0.1', '2026-05-22 10:05:00'),
  (15, 5, '127.0.0.1', '2026-05-23 09:00:00'), (15, 6, '127.0.0.1', '2026-05-23 09:05:00'), (15, 7, '127.0.0.1', '2026-05-23 09:10:00'), (15, 8, '127.0.0.1', '2026-05-23 09:15:00');

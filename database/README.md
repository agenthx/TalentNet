# Job Portal Database Scripts

These scripts satisfy the database portion of the IT8415 job portal project.

Verified with MariaDB 10.4.32, the same major MariaDB version commonly bundled with XAMPP.

## Import Order

Run the files in this order:

1. `00_create_database.sql`
2. `01_schema.sql`
3. `02_seed_data.sql`
4. `03_stored_procedures.sql`
5. `04_security.sql`
6. `05_sample_queries.sql` for testing only

In XAMPP, start MySQL from the control panel, then import the scripts through phpMyAdmin or run them with:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root < "database\00_create_database.sql"
& "C:\xampp\mysql\bin\mysql.exe" -u root dbProj_job_portal < "database\01_schema.sql"
& "C:\xampp\mysql\bin\mysql.exe" -u root dbProj_job_portal < "database\02_seed_data.sql"
& "C:\xampp\mysql\bin\mysql.exe" -u root dbProj_job_portal < "database\03_stored_procedures.sql"
& "C:\xampp\mysql\bin\mysql.exe" -u root dbProj_job_portal < "database\04_security.sql"
```

## What Is Included

- 9 prefixed tables: roles, users, employers, categories, job listings, media, comments, ratings, and views.
- FULLTEXT index: `dbProj_ft_jobs_title_description` on `dbProj_job_listings(title, description)`.
- Stored procedures:
  - `dbProj_get_top_rated_jobs`
  - `dbProj_get_jobs_by_creator`
- Role-level DB accounts:
  - `dbProj_app_viewer`
  - `dbProj_app_creator`
  - `dbProj_app_admin`
  - `dbProj_app_auth` for login/signup checks
- Seed data:
  - 3 roles
  - 9 users
  - 5 employers
  - 3 categories
  - 15 published jobs, with 5 jobs in each category
  - media, comments, ratings, and views

The demo password for all seeded application users is `password`. Database account passwords are in `04_security.sql` and should be changed before a live deployment.

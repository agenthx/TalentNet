# IT8415 Job Portal Database Contribution

This repository contains the database contribution for the IT8415 job portal project.

## Main Files

- `docs/ERD.md` - Mermaid ERD for the report.
- `docs/database-requirements-checklist.md` - Mapping from project requirements to delivered files.
- `database/01_schema.sql` - All `dbProj_` tables, keys, constraints, and the FULLTEXT search index.
- `database/02_seed_data.sql` - Demo data with 15 published jobs, 5 per category.
- `database/03_stored_procedures.sql` - Stored procedures for top-rated jobs and creator reports.
- `database/04_security.sql` - Role-level MySQL accounts and least-privilege grants.
- `database/05_sample_queries.sql` - Queries for screenshots/testing.
- `examples/php/dbproj_pdo_examples.php` - Prepared statement examples for NetBeans/PHP.

Read `database/README.md` for the XAMPP import order.

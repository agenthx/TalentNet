# Job Portal ERD

This ERD is designed for the IT8415 job portal contribution. All tables use the required `dbProj_` prefix.

Screenshot-ready SVG version: [`erd-job-portal.svg`](erd-job-portal.svg)

```mermaid
erDiagram
    dbProj_roles ||--o{ dbProj_users : assigns
    dbProj_users ||--o{ dbProj_employers : owns
    dbProj_users ||--o{ dbProj_job_listings : creates
    dbProj_employers ||--o{ dbProj_job_listings : posts
    dbProj_job_categories ||--o{ dbProj_job_listings : groups
    dbProj_job_listings ||--o{ dbProj_job_media : has
    dbProj_job_listings ||--o{ dbProj_comments : receives
    dbProj_users ||--o{ dbProj_comments : writes
    dbProj_users ||--o{ dbProj_comments : moderates
    dbProj_job_listings ||--o{ dbProj_ratings : receives
    dbProj_users ||--o{ dbProj_ratings : gives
    dbProj_job_listings ||--o{ dbProj_job_views : records
    dbProj_users ||--o{ dbProj_job_views : views

    dbProj_roles {
        TINYINT role_id PK
        VARCHAR role_name UK
        VARCHAR role_description
        TIMESTAMP created_at
    }

    dbProj_users {
        INT user_id PK
        TINYINT role_id FK
        VARCHAR full_name
        VARCHAR email UK
        VARCHAR password_hash
        VARCHAR phone
        BOOLEAN is_active
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    dbProj_employers {
        INT employer_id PK
        INT owner_user_id FK
        VARCHAR company_name UK
        VARCHAR company_website
        TEXT company_description
        VARCHAR logo_path
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    dbProj_job_categories {
        INT category_id PK
        VARCHAR category_name UK
        VARCHAR category_slug UK
        VARCHAR category_description
        TIMESTAMP created_at
    }

    dbProj_job_listings {
        INT job_id PK
        INT employer_id FK
        INT category_id FK
        INT created_by_user_id FK
        VARCHAR title
        VARCHAR short_description
        TEXT description
        VARCHAR location
        ENUM employment_type
        ENUM work_mode
        DECIMAL salary_min
        DECIMAL salary_max
        CHAR currency
        VARCHAR application_url
        ENUM status
        DATETIME published_at
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    dbProj_job_media {
        INT media_id PK
        INT job_id FK
        ENUM media_type
        VARCHAR file_path
        VARCHAR alt_text
        BOOLEAN is_primary
        TIMESTAMP uploaded_at
    }

    dbProj_comments {
        INT comment_id PK
        INT job_id FK
        INT user_id FK
        VARCHAR comment_text
        BOOLEAN is_removed
        INT removed_by_user_id FK
        VARCHAR removed_reason
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    dbProj_ratings {
        INT rating_id PK
        INT job_id FK
        INT user_id FK
        TINYINT rating_value
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    dbProj_job_views {
        INT view_id PK
        INT job_id FK
        INT viewer_user_id FK
        VARCHAR viewer_ip
        TIMESTAMP viewed_at
    }
```

## Relationship Notes

- `dbProj_roles` supports the required viewer, creator, and administrator roles.
- `dbProj_users.password_hash` stores encrypted password hashes, not plain text passwords.
- `dbProj_job_listings` belongs to one employer, one category, and one creator user.
- `dbProj_comments` supports admin moderation through `is_removed`, `removed_by_user_id`, and `removed_reason`.
- `dbProj_ratings` uses a unique `(job_id, user_id)` rule so one user can rate the same job only once.
- `dbProj_job_views` supports popularity reports by view count.
- `dbProj_job_listings` has a FULLTEXT index on `title` and `description` for engine-based search.

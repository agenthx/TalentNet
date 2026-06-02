-- Fix seeded demo user passwords without resetting the rest of the database.
-- Demo password for these accounts: password

UPDATE dbProj_users
SET password_hash = '$2y$10$ftiFdWEOci9KJnCdtOWHUunnSXjEE8CB3lX15SnRHDazwBDPS/Zd.',
    updated_at = CURRENT_TIMESTAMP
WHERE email IN (
  'admin@jobportal.local',
  'omar.creator@jobportal.local',
  'lina.creator@jobportal.local',
  'farah.creator@jobportal.local',
  'sara.viewer@jobportal.local',
  'khalid.viewer@jobportal.local',
  'noor.viewer@jobportal.local',
  'youssef.viewer@jobportal.local',
  'mariam.viewer@jobportal.local'
);

DELETE FROM dbProj_users
WHERE email = 'codex-test-20260602154702@jobportal.local';

SELECT email, CHAR_LENGTH(password_hash) AS password_hash_length, is_active
FROM dbProj_users
WHERE email IN (
  'admin@jobportal.local',
  'omar.creator@jobportal.local',
  'lina.creator@jobportal.local',
  'farah.creator@jobportal.local',
  'sara.viewer@jobportal.local',
  'khalid.viewer@jobportal.local',
  'noor.viewer@jobportal.local',
  'youssef.viewer@jobportal.local',
  'mariam.viewer@jobportal.local'
);

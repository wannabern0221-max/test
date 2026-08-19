-- NEXT LEADER custom schema reference
-- IMPORTANT: This is documentation, not the preferred installer.
-- Replace {{PREFIX}} with your actual Gnuboard table prefix (normally g5_).
-- Canonical install path: /plugin/nextleader/install.php
-- No DROP/TRUNCATE statements are included.

CREATE TABLE IF NOT EXISTS `{{PREFIX}}nl_profile` (
  mb_id varchar(20) NOT NULL,
  school varchar(120) NOT NULL DEFAULT '',
  cohort varchar(50) NOT NULL DEFAULT '',
  department enum('policy_office','div1','div2') NOT NULL DEFAULT 'policy_office',
  role_code varchar(50) NOT NULL DEFAULT 'leader',
  requested_role varchar(50) NOT NULL DEFAULT 'leader',
  approval_status enum('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
  base_mb_level tinyint unsigned NULL,
  managed_mb_level tinyint unsigned NULL,
  approval_note varchar(255) NOT NULL DEFAULT '',
  approved_by varchar(20) NOT NULL DEFAULT '',
  approved_at datetime NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (mb_id), KEY approval_status (approval_status), KEY department (department), KEY role_code (role_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}nl_permission` (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  mb_id varchar(20) NOT NULL,
  permission_code varchar(80) NOT NULL,
  effect enum('allow','deny') NOT NULL,
  granted_by varchar(20) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id), UNIQUE KEY uniq_member_permission (mb_id, permission_code), KEY mb_id (mb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}nl_notification` (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  mb_id varchar(20) NOT NULL,
  type varchar(50) NOT NULL DEFAULT 'system',
  title varchar(255) NOT NULL,
  message text NOT NULL,
  target_url varchar(500) NOT NULL DEFAULT '',
  is_read tinyint(1) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  read_at datetime NULL,
  PRIMARY KEY (id), KEY member_read (mb_id,is_read), KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}nl_schedule` (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  details text NOT NULL,
  scope enum('common','div1','div2') NOT NULL DEFAULT 'common',
  visibility enum('public','leaders') NOT NULL DEFAULT 'leaders',
  starts_at datetime NOT NULL,
  ends_at datetime NULL,
  location varchar(255) NOT NULL DEFAULT '',
  created_by varchar(20) NOT NULL,
  updated_by varchar(20) NOT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id), KEY scope_start (scope,starts_at), KEY visibility_start (visibility,starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}nl_unavailable` (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  mb_id varchar(20) NOT NULL,
  scope enum('common','div1','div2') NOT NULL,
  unavailable_date date NOT NULL,
  note varchar(255) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id), UNIQUE KEY uniq_member_date (mb_id,unavailable_date), KEY scope_date (scope,unavailable_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}nl_glossary` (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  term varchar(150) NOT NULL,
  category varchar(100) NOT NULL DEFAULT '',
  summary text NOT NULL,
  detail mediumtext NOT NULL,
  source_title varchar(255) NOT NULL DEFAULT '',
  source_url varchar(1000) NOT NULL DEFAULT '',
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id), UNIQUE KEY term (term), KEY category (category), KEY is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}nl_quiz` (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  source_id varchar(30) NOT NULL DEFAULT '',
  difficulty varchar(30) NOT NULL DEFAULT '',
  category varchar(100) NOT NULL DEFAULT '',
  question text NOT NULL,
  choices_json text NOT NULL,
  answer_index tinyint unsigned NOT NULL,
  explanation text NOT NULL,
  source_title varchar(255) NOT NULL DEFAULT '',
  source_url varchar(1000) NOT NULL DEFAULT '',
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id), UNIQUE KEY source_id (source_id), KEY active_category (is_active,category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}nl_anonymous_post` (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  author_mb_id varchar(20) NOT NULL,
  title varchar(255) NOT NULL,
  content mediumtext NOT NULL,
  status enum('active','hidden','deleted') NOT NULL DEFAULT 'active',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id), KEY status_created (status,created_at), KEY author (author_mb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}nl_anonymous_comment` (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  post_id bigint unsigned NOT NULL,
  author_mb_id varchar(20) NOT NULL,
  content text NOT NULL,
  status enum('active','hidden','deleted') NOT NULL DEFAULT 'active',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id), KEY post_created (post_id,created_at), KEY author (author_mb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}nl_audit_log` (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  actor_mb_id varchar(20) NOT NULL DEFAULT '',
  action varchar(100) NOT NULL,
  target_type varchar(50) NOT NULL DEFAULT '',
  target_id varchar(100) NOT NULL DEFAULT '',
  detail_json mediumtext NOT NULL,
  ip_address varchar(100) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY (id), KEY actor_created (actor_mb_id,created_at), KEY action_created (action,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}nl_file` (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  owner_mb_id varchar(20) NOT NULL,
  label varchar(255) NOT NULL,
  original_name varchar(255) NOT NULL,
  stored_name varchar(255) NOT NULL,
  mime_type varchar(100) NOT NULL DEFAULT 'application/octet-stream',
  size_bytes bigint unsigned NOT NULL DEFAULT 0,
  audience enum('leaders','executives') NOT NULL DEFAULT 'leaders',
  download_allowed tinyint(1) NOT NULL DEFAULT 1,
  status enum('active','deleting','deleted','trash_pending') NOT NULL DEFAULT 'active',
  delete_reason varchar(100) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  deleted_at datetime NULL,
  PRIMARY KEY (id), UNIQUE KEY stored_name (stored_name), KEY status_created (status,created_at), KEY owner_created (owner_mb_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{PREFIX}}nl_news` (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  title varchar(500) NOT NULL,
  source_name varchar(150) NOT NULL DEFAULT '',
  source_url varchar(1200) NOT NULL,
  published_at datetime NULL,
  category varchar(100) NOT NULL DEFAULT '',
  content_type varchar(100) NOT NULL DEFAULT '',
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id), UNIQUE KEY source_url (source_url(255)), KEY active_published (is_active,published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

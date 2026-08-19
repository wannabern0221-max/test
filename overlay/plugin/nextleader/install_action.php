<?php
include_once './_common.php';
if ($is_admin !== 'super') alert('최고관리자만 설치할 수 있습니다.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') alert('잘못된 요청입니다.');
nl_verify_csrf();

function nl_install_query($sql) {
    if (!sql_query($sql, false)) {
        throw new RuntimeException('DB 설치 중 SQL 실행에 실패했습니다.');
    }
}

function nl_install_preflight_collisions() {
    global $g5;
    $group = sql_fetch("SELECT gr_id,gr_subject FROM {$g5['group_table']} WHERE gr_id='".NL_BOARD_GROUP."' LIMIT 1", false);
    if (!empty($group['gr_id']) && trim((string)($group['gr_subject'] ?? '')) !== 'NEXT LEADER') {
        throw new RuntimeException('게시판 그룹 ID 충돌: '.NL_BOARD_GROUP.' (기존 그룹을 덮어쓰지 않습니다.)');
    }
    foreach (nl_allowed_boards() as $bo_table) {
        $boEsc = nl_sql_escape($bo_table);
        $row = sql_fetch("SELECT bo_table,gr_id FROM {$g5['board_table']} WHERE bo_table='{$boEsc}' LIMIT 1", false);
        if (!empty($row['bo_table']) && ($row['gr_id'] ?? '') !== NL_BOARD_GROUP) {
            throw new RuntimeException('게시판 ID 충돌: '.$bo_table.' (기존 게시판을 덮어쓰지 않습니다.)');
        }
    }
}

function nl_install_create_tables() {
    $p = nl_table('profile');
    $perm = nl_table('permission');
    $noti = nl_table('notification');
    $schedule = nl_table('schedule');
    $unavail = nl_table('unavailable');
    $glossary = nl_table('glossary');
    $quiz = nl_table('quiz');
    $ap = nl_table('anonymous_post');
    $ac = nl_table('anonymous_comment');
    $audit = nl_table('audit_log');
    $file = nl_table('file');
    $news = nl_table('news');

    $sqls = array(
"CREATE TABLE IF NOT EXISTS {$p} (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS {$perm} (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  mb_id varchar(20) NOT NULL,
  permission_code varchar(80) NOT NULL,
  effect enum('allow','deny') NOT NULL,
  granted_by varchar(20) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id), UNIQUE KEY uniq_member_permission (mb_id, permission_code), KEY mb_id (mb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS {$noti} (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS {$schedule} (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS {$unavail} (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  mb_id varchar(20) NOT NULL,
  scope enum('common','div1','div2') NOT NULL,
  unavailable_date date NOT NULL,
  note varchar(255) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id), UNIQUE KEY uniq_member_date (mb_id,unavailable_date), KEY scope_date (scope,unavailable_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS {$glossary} (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS {$quiz} (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS {$ap} (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  author_mb_id varchar(20) NOT NULL,
  title varchar(255) NOT NULL,
  content mediumtext NOT NULL,
  status enum('active','hidden','deleted') NOT NULL DEFAULT 'active',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id), KEY status_created (status,created_at), KEY author (author_mb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS {$ac} (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  post_id bigint unsigned NOT NULL,
  author_mb_id varchar(20) NOT NULL,
  content text NOT NULL,
  status enum('active','hidden','deleted') NOT NULL DEFAULT 'active',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id), KEY post_created (post_id,created_at), KEY author (author_mb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS {$audit} (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  actor_mb_id varchar(20) NOT NULL DEFAULT '',
  action varchar(100) NOT NULL,
  target_type varchar(50) NOT NULL DEFAULT '',
  target_id varchar(100) NOT NULL DEFAULT '',
  detail_json mediumtext NOT NULL,
  ip_address varchar(100) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY (id), KEY actor_created (actor_mb_id,created_at), KEY action_created (action,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS {$file} (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS {$news} (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    foreach ($sqls as $sql) nl_install_query($sql);
}

function nl_install_upgrade_profile_level_tracking() {
    $table = nl_table('profile');

    $base = sql_fetch("SHOW COLUMNS FROM {$table} LIKE 'base_mb_level'", false);
    if (empty($base['Field'])) {
        nl_install_query("ALTER TABLE {$table} ADD base_mb_level tinyint unsigned NULL AFTER approval_status");
    }

    $managed = sql_fetch("SHOW COLUMNS FROM {$table} LIKE 'managed_mb_level'", false);
    if (empty($managed['Field'])) {
        nl_install_query("ALTER TABLE {$table} ADD managed_mb_level tinyint unsigned NULL AFTER base_mb_level");
    }
}

function nl_install_board($bo_table, $subject, $order) {
    global $g5;
    $bo_table = preg_replace('/[^A-Za-z0-9_]/','',$bo_table);
    $row = sql_fetch("SELECT bo_table, gr_id, bo_subject FROM {$g5['board_table']} WHERE bo_table='".nl_sql_escape($bo_table)."' LIMIT 1", false);
    if (!empty($row['bo_table']) && ($row['gr_id'] ?? '') !== NL_BOARD_GROUP) {
        throw new RuntimeException('게시판 ID 충돌: '.$bo_table.' (기존 게시판을 덮어쓰지 않습니다.)');
    }
    if (empty($row['bo_table'])) {
        $subjectEsc = nl_sql_escape($subject);
        nl_install_query("INSERT INTO {$g5['board_table']} SET bo_table='{$bo_table}', gr_id='".NL_BOARD_GROUP."', bo_subject='{$subjectEsc}', bo_mobile_subject='{$subjectEsc}', bo_device='both', bo_skin='nextleader', bo_mobile_skin='nextleader', bo_list_level=3, bo_read_level=3, bo_write_level=3, bo_reply_level=3, bo_comment_level=10, bo_upload_level=3, bo_download_level=3, bo_html_level=3, bo_link_level=3, bo_use_dhtml_editor=1, bo_use_search=0, bo_use_rss_view=0, bo_order=".(int)$order.", bo_page_rows=15, bo_mobile_page_rows=15, bo_upload_count=5, bo_upload_size=52428800");
    } else {
        sql_query("UPDATE {$g5['board_table']} SET gr_id='".NL_BOARD_GROUP."', bo_skin='nextleader', bo_mobile_skin='nextleader', bo_device='both', bo_list_level=3, bo_read_level=3, bo_write_level=3, bo_reply_level=3, bo_comment_level=10, bo_upload_level=3, bo_download_level=3, bo_html_level=3, bo_link_level=3, bo_use_dhtml_editor=1, bo_use_search=0, bo_use_rss_view=0, bo_upload_count=5, bo_upload_size=52428800 WHERE bo_table='{$bo_table}'", false);
    }
    $writeTable = $g5['write_prefix'].$bo_table;
    if (!nl_table_exists($writeTable)) {
        $sqlFile = G5_ADMIN_PATH.'/sql_write.sql';
        if (!is_file($sqlFile)) throw new RuntimeException('그누보드 adm/sql_write.sql을 찾을 수 없습니다.');
        $sql = implode("\n", file($sqlFile));
        $sql = preg_replace(array('/__TABLE_NAME__/', '/;/'), array($writeTable, ''), $sql);
        nl_install_query($sql);
    }
    $dir = G5_DATA_PATH.'/file/'.$bo_table;
    if (!is_dir($dir)) @mkdir($dir, G5_DIR_PERMISSION, true);
}

function nl_install_seed_json() {
    $now = nl_now();
    $glossaryFile = NL_PLUGIN_PATH.'/data/policy-glossary.json';
    if (is_file($glossaryFile)) {
        $data = json_decode(file_get_contents($glossaryFile), true);
        foreach (($data['items'] ?? array()) as $item) {
            $term = nl_sql_escape(substr((string)($item['term'] ?? ''),0,150));
            if ($term === '') continue;
            $cat = nl_sql_escape(substr((string)($item['category'] ?? ''),0,100));
            $sum = nl_sql_escape((string)($item['summary'] ?? ''));
            $det = nl_sql_escape((string)($item['detail'] ?? ''));
            $st = nl_sql_escape(substr((string)($item['source']['title'] ?? ''),0,255));
            $suRaw = nl_safe_external_url($item['source']['url'] ?? '');
            $su = nl_sql_escape($suRaw);
            sql_query("INSERT INTO ".nl_table('glossary')." (term,category,summary,detail,source_title,source_url,is_active,created_at,updated_at) VALUES ('{$term}','{$cat}','{$sum}','{$det}','{$st}','{$su}',1,'{$now}','{$now}') ON DUPLICATE KEY UPDATE category=VALUES(category),summary=VALUES(summary),detail=VALUES(detail),source_title=VALUES(source_title),source_url=VALUES(source_url),updated_at=VALUES(updated_at)", false);
        }
    }
    $quizFile = NL_PLUGIN_PATH.'/data/policy-quiz.json';
    if (is_file($quizFile)) {
        $data = json_decode(file_get_contents($quizFile), true);
        foreach (($data['items'] ?? array()) as $item) {
            $sid = nl_sql_escape(substr((string)($item['id'] ?? ''),0,30));
            if ($sid === '') continue;
            $dif = nl_sql_escape(substr((string)($item['difficulty'] ?? ''),0,30));
            $cat = nl_sql_escape(substr((string)($item['category'] ?? ''),0,100));
            $q = nl_sql_escape((string)($item['question'] ?? ''));
            $choices = nl_sql_escape(json_encode($item['choices'] ?? array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $answer = max(0,(int)($item['answer'] ?? 0));
            $exp = nl_sql_escape((string)($item['explanation'] ?? ''));
            $st = nl_sql_escape(substr((string)($item['source']['title'] ?? ''),0,255));
            $su = nl_sql_escape(nl_safe_external_url($item['source']['url'] ?? ''));
            sql_query("INSERT INTO ".nl_table('quiz')." (source_id,difficulty,category,question,choices_json,answer_index,explanation,source_title,source_url,is_active,created_at,updated_at) VALUES ('{$sid}','{$dif}','{$cat}','{$q}','{$choices}',{$answer},'{$exp}','{$st}','{$su}',1,'{$now}','{$now}') ON DUPLICATE KEY UPDATE difficulty=VALUES(difficulty),category=VALUES(category),question=VALUES(question),choices_json=VALUES(choices_json),answer_index=VALUES(answer_index),explanation=VALUES(explanation),source_title=VALUES(source_title),source_url=VALUES(source_url),updated_at=VALUES(updated_at)", false);
        }
    }
    $newsFile = NL_PLUGIN_PATH.'/data/external-news.json';
    if (is_file($newsFile)) {
        $data = json_decode(file_get_contents($newsFile), true);
        foreach (($data['items'] ?? array()) as $item) {
            $urlRaw = nl_safe_external_url($item['link'] ?? '');
            if (!$urlRaw) continue;
            $title = nl_sql_escape(substr((string)($item['title'] ?? ''),0,500));
            $source = nl_sql_escape(substr((string)($item['source'] ?? ''),0,150));
            $url = nl_sql_escape($urlRaw);
            $cat = nl_sql_escape(substr((string)($item['category'] ?? ''),0,100));
            $type = nl_sql_escape(substr((string)($item['contentType'] ?? ''),0,100));
            $ts = strtotime((string)($item['publishedAt'] ?? ''));
            $published = $ts ? "'".date('Y-m-d H:i:s',$ts)."'" : 'NULL';
            sql_query("INSERT INTO ".nl_table('news')." (title,source_name,source_url,published_at,category,content_type,is_active,created_at,updated_at) VALUES ('{$title}','{$source}','{$url}',{$published},'{$cat}','{$type}',1,'{$now}','{$now}') ON DUPLICATE KEY UPDATE title=VALUES(title),source_name=VALUES(source_name),published_at=VALUES(published_at),category=VALUES(category),content_type=VALUES(content_type),updated_at=VALUES(updated_at)", false);
        }
    }
}

try {
    nl_install_preflight_collisions();
    nl_install_create_tables();
    nl_install_upgrade_profile_level_tracking();
    nl_profile_level_tracking_supported(true);
    global $g5, $member;
    $group = sql_fetch("SELECT gr_id FROM {$g5['group_table']} WHERE gr_id='".NL_BOARD_GROUP."' LIMIT 1", false);
    if (empty($group['gr_id'])) nl_install_query("INSERT INTO {$g5['group_table']} (gr_id,gr_subject,gr_device,gr_admin,gr_use_access,gr_order) VALUES ('".NL_BOARD_GROUP."','NEXT LEADER','both','',0,10)");

    nl_install_board(NL_BOARD_NOTICE, '공지사항', 10);
    nl_install_board(NL_BOARD_CARDS, '카드뉴스', 20);
    nl_install_board(NL_BOARD_POLICY, '정책 콘텐츠', 30);
    nl_install_board(NL_BOARD_ACTIVITY, '사업자료', 40);

    nl_storage_prepare();
    $role = nl_post_str('installer_role','policy_director');
    if (!array_key_exists($role, nl_role_labels())) $role = 'policy_director';
    $dept = nl_post_str('installer_department','policy_office');
    if (!array_key_exists($dept, nl_department_labels())) $dept = 'policy_office';
    $mb = nl_sql_escape($member['mb_id']);
    $roleEsc = nl_sql_escape($role);
    $deptEsc = nl_sql_escape($dept);
    $now = nl_now();
    $currentLevel = max(1, min(10, (int)($member['mb_level'] ?? 10)));
    sql_query("INSERT INTO ".nl_table('profile')." (mb_id,department,role_code,requested_role,approval_status,base_mb_level,managed_mb_level,approved_by,approved_at,created_at,updated_at) VALUES ('{$mb}','{$deptEsc}','{$roleEsc}','{$roleEsc}','approved',{$currentLevel},{$currentLevel},'{$mb}','{$now}','{$now}','{$now}') ON DUPLICATE KEY UPDATE department=VALUES(department),role_code=VALUES(role_code),approval_status='approved',base_mb_level=COALESCE(base_mb_level,{$currentLevel}),managed_mb_level=COALESCE(managed_mb_level,{$currentLevel}),approved_by='{$mb}',approved_at='{$now}',updated_at='{$now}'", false);

    nl_sync_member_level($member['mb_id']);

    if (isset($_POST['seed_data'])) nl_install_seed_json();
    if (isset($_POST['set_theme'])) {
        sql_query("UPDATE {$g5['config_table']} SET cf_theme='nextleader'", false);
    }
    nl_audit('install','system','nextleader',array('version'=>NL_VERSION,'theme'=>isset($_POST['set_theme']),'seed'=>isset($_POST['seed_data'])));
    goto_url(nl_url('dashboard.php'));
} catch (Throwable $e) {
    alert('설치를 완료하지 못했습니다: '.$e->getMessage(), nl_url('install.php'));
}

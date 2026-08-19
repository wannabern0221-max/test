<?php
if (!defined('_GNUBOARD_')) exit;

function nl_notify($mb_id, $type, $title, $message = '', $url = '') {
    if (!$mb_id || !nl_table_exists(nl_table('notification'))) return false;
    $t = nl_table('notification');
    $mb_id = nl_sql_escape($mb_id);
    $type = nl_sql_escape(substr($type, 0, 50));
    $title = nl_sql_escape(substr($title, 0, 255));
    $message = nl_sql_escape($message);
    $url = nl_sql_escape(substr(nl_safe_internal_url($url), 0, 500));
    $now = nl_now();
    return (bool)sql_query("INSERT INTO {$t} (mb_id,type,title,message,target_url,is_read,created_at) VALUES ('{$mb_id}','{$type}','{$title}','{$message}','{$url}',0,'{$now}')", false);
}
function nl_notify_permission_holders($permission, $title, $message = '', $url = '') {
    if (!nl_installed()) return;
    $t = nl_table('profile');
    $result = sql_query("SELECT mb_id FROM {$t} WHERE approval_status='approved'", false);
    if (!$result) return;
    while ($row = sql_fetch_array($result)) {
        if (nl_can($permission, $row['mb_id'])) nl_notify($row['mb_id'], 'workflow', $title, $message, $url);
    }
}
function nl_audit($action, $target_type = '', $target_id = '', $detail = array()) {
    global $member;
    if (!nl_table_exists(nl_table('audit_log'))) return false;
    $t = nl_table('audit_log');
    $actor = nl_sql_escape($member['mb_id'] ?? 'system');
    $action = nl_sql_escape(substr($action, 0, 100));
    $target_type = nl_sql_escape(substr($target_type, 0, 50));
    $target_id = nl_sql_escape(substr((string)$target_id, 0, 100));
    $json = nl_sql_escape(json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $ip = nl_sql_escape($_SERVER['REMOTE_ADDR'] ?? '');
    $now = nl_now();
    return (bool)sql_query("INSERT INTO {$t} (actor_mb_id,action,target_type,target_id,detail_json,ip_address,created_at) VALUES ('{$actor}','{$action}','{$target_type}','{$target_id}','{$json}','{$ip}','{$now}')", false);
}
function nl_unread_notification_count($mb_id = '') {
    global $member;
    if (!$mb_id) $mb_id = $member['mb_id'] ?? '';
    if (!$mb_id || !nl_table_exists(nl_table('notification'))) return 0;
    $id = nl_sql_escape($mb_id);
    $row = sql_fetch("SELECT COUNT(*) cnt FROM ".nl_table('notification')." WHERE mb_id='{$id}' AND is_read=0", false);
    return (int)($row['cnt'] ?? 0);
}
function nl_unread_memo_count($mb_id = '') {
    global $member, $g5;
    if (!$mb_id) $mb_id = $member['mb_id'] ?? '';
    if (!$mb_id) return 0;
    if (function_exists('get_memo_not_read')) return (int)get_memo_not_read($mb_id);
    if (empty($g5['memo_table'])) return 0;
    $id = nl_sql_escape($mb_id);
    $row = sql_fetch("SELECT COUNT(*) cnt FROM {$g5['memo_table']} WHERE me_recv_mb_id='{$id}' AND me_type='recv' AND (me_read_datetime='0000-00-00 00:00:00' OR me_read_datetime IS NULL)", false);
    return (int)($row['cnt'] ?? 0);
}
function nl_allowed_boards() {
    return array(NL_BOARD_NOTICE, NL_BOARD_CARDS, NL_BOARD_POLICY, NL_BOARD_ACTIVITY);
}
function nl_board_table($bo_table) {
    global $g5;
    if (!in_array($bo_table, nl_allowed_boards(), true)) return '';
    return $g5['write_prefix'].$bo_table;
}
function nl_content_permission_for_board($bo_table) {
    if ($bo_table === NL_BOARD_NOTICE) return 'content_write_notice';
    if ($bo_table === NL_BOARD_CARDS) return 'content_write_card';
    if ($bo_table === NL_BOARD_POLICY) return 'content_write_policy';
    if ($bo_table === NL_BOARD_ACTIVITY) return 'content_write_policy';
    return '';
}
function nl_can_write_board($bo_table) {
    if (!nl_is_approved()) return false;
    return in_array($bo_table, nl_allowed_boards(), true);
}
function nl_can_view_unpublished($row, $bo_table = '') {
    global $member, $is_admin;
    if (($row['wr_10'] ?? '') === 'published') return true;
    if (!$member['mb_id']) return false;
    if ($is_admin === 'super' || nl_can('content_approve')) return true;
    return ($row['mb_id'] ?? '') === $member['mb_id'];
}
function nl_latest_posts($bo_table, $limit = 5) {
    $table = nl_board_table($bo_table);
    if (!$table || !nl_table_exists($table)) return array();
    $limit = max(1, min(20, (int)$limit));
    $rows = array();
    $result = sql_query("SELECT wr_id, wr_subject, wr_datetime, wr_hit FROM {$table} WHERE wr_is_comment=0 AND wr_10='published' ORDER BY wr_num, wr_reply LIMIT {$limit}", false);
    if ($result) while ($r = sql_fetch_array($result)) $rows[] = $r;
    return $rows;
}
function nl_status_badge($status) {
    $labels = array(
        'pending'=>'승인 대기',
        'approved'=>'승인',
        'rejected'=>'반려',
        'suspended'=>'정지',
        'published'=>'게시',
        'draft'=>'초안',
        'hidden'=>'숨김'
    );
    $key = array_key_exists($status, $labels) ? $status : 'unknown';
    $label = $labels[$status] ?? (string)$status;
    return '<span class="nl-badge nl-badge--'.$key.'">'.nl_h($label).'</span>';
}

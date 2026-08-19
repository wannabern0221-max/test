<?php
if (!defined('_GNUBOARD_')) exit;

function nl_role_labels() {
    return array(
        'president'=>'회장',
        'political_vice_president'=>'정무부회장',
        'leader'=>'리더',
        'team_leader'=>'팀장',
        'deputy_manager'=>'차장',
        'section_manager'=>'과장',
        'department_manager'=>'부장',
        'policy_general_manager'=>'정책총괄부장',
        'senior_manager_div1'=>'정책1부 수석부장',
        'senior_manager_div2'=>'정책2부 수석부장',
        'policy_director'=>'정책국장',
        'external_admin'=>'관리자'
    );
}
function nl_department_labels() {
    return array('policy_office'=>'정책국', 'div1'=>'정책1부', 'div2'=>'정책2부');
}
function nl_permission_labels() {
    return array(
        'member_approve'=>'가입 승인',
        'role_manage'=>'직책 관리',
        'permission_grant'=>'기능 권한 관리',
        'content_write_notice'=>'공지사항 작성',
        'content_write_card'=>'카드뉴스 작성',
        'content_write_policy'=>'정책 콘텐츠 작성',
        'content_approve'=>'게시 승인',
        'news_manage'=>'외부 뉴스 관리',
        'board_moderate'=>'익명 소통방 관리',
        'anonymous_identity_reveal'=>'익명 작성자 확인',
        'schedule_manage_common'=>'정책국 공통 일정 관리',
        'schedule_manage_div1'=>'정책1부 일정 관리',
        'schedule_manage_div2'=>'정책2부 일정 관리',
        'file_manage'=>'파일 관리',
        'system_manage'=>'시스템 관리'
    );
}
function nl_role_label($role) {
    $labels = nl_role_labels();
    return isset($labels[$role]) ? $labels[$role] : ($role ?: '리더');
}
function nl_department_label($department) {
    $labels = nl_department_labels();
    return isset($labels[$department]) ? $labels[$department] : ($department ?: '정책국');
}
function nl_default_permissions($role) {
    $all = array_keys(nl_permission_labels());
    $map = array(
        'president'=>array('content_write_notice','content_write_card','content_write_policy','content_approve','news_manage','board_moderate','schedule_manage_common','schedule_manage_div1','schedule_manage_div2'),
        'political_vice_president'=>array('content_write_notice','content_write_card','content_write_policy','content_approve','news_manage','board_moderate','schedule_manage_common','schedule_manage_div1','schedule_manage_div2'),
        'policy_director'=>$all,
        'senior_manager_div1'=>array('member_approve','role_manage','permission_grant','content_write_notice','content_write_card','content_write_policy','content_approve','board_moderate','schedule_manage_div1'),
        'senior_manager_div2'=>array('member_approve','role_manage','permission_grant','content_write_notice','content_write_card','content_write_policy','content_approve','board_moderate','schedule_manage_div2'),
        'policy_general_manager'=>array('permission_grant','content_write_notice','content_write_card','content_write_policy','content_approve','news_manage','board_moderate','schedule_manage_common'),
        'department_manager'=>array(),
        'deputy_manager'=>array(),
        'section_manager'=>array(),
        'team_leader'=>array(),
        'leader'=>array(),
        'external_admin'=>array('system_manage')
    );
    return isset($map[$role]) ? $map[$role] : array();
}
function nl_profile_level_tracking_supported($refresh = false) {
    static $supported = null;
    if ($refresh) $supported = null;
    if ($supported !== null) return $supported;
    if (!nl_installed()) return false;

    $table = nl_table('profile');
    $base = sql_fetch("SHOW COLUMNS FROM {$table} LIKE 'base_mb_level'", false);
    $managed = sql_fetch("SHOW COLUMNS FROM {$table} LIKE 'managed_mb_level'", false);
    $supported = !empty($base['Field']) && !empty($managed['Field']);
    return $supported;
}
function nl_ensure_profile($mb_id) {
    global $g5;
    if (!$mb_id || !nl_installed()) return false;

    $id = nl_sql_escape($mb_id);
    $memberRow = sql_fetch("SELECT mb_id, mb_level FROM {$g5['member_table']} WHERE mb_id='{$id}' LIMIT 1", false);
    if (empty($memberRow['mb_id'])) return false;

    $table = nl_table('profile');
    $row = sql_fetch("SELECT mb_id FROM {$table} WHERE mb_id='{$id}' LIMIT 1", false);
    if (!empty($row['mb_id'])) return true;

    $now = nl_now();
    $level = max(1, min(10, (int)($memberRow['mb_level'] ?? 2)));
    if (nl_profile_level_tracking_supported()) {
        $ok = sql_query("INSERT INTO {$table} (mb_id, department, role_code, approval_status, base_mb_level, managed_mb_level, created_at, updated_at) VALUES ('{$id}', 'policy_office', 'leader', 'pending', {$level}, {$level}, '{$now}', '{$now}')", false);
    } else {
        $ok = sql_query("INSERT INTO {$table} (mb_id, department, role_code, approval_status, created_at, updated_at) VALUES ('{$id}', 'policy_office', 'leader', 'pending', '{$now}', '{$now}')", false);
    }
    return (bool)$ok;
}
function nl_profile($mb_id = '') {
    global $member, $is_admin;
    if (!$mb_id) $mb_id = isset($member['mb_id']) ? $member['mb_id'] : '';
    if (!$mb_id || !nl_installed()) return array();
    if (!nl_ensure_profile($mb_id)) return array();

    $id = nl_sql_escape($mb_id);
    $row = sql_fetch("SELECT * FROM ".nl_table('profile')." WHERE mb_id='{$id}' LIMIT 1", false);
    if (!is_array($row)) $row = array();
    if ($is_admin === 'super' && $mb_id === ($member['mb_id'] ?? '')) {
        $row['approval_status'] = 'approved';
        $row['role_code'] = $row['role_code'] ?: 'external_admin';
    }
    return $row;
}
function nl_is_approved($mb_id = '') {
    global $member, $is_admin;
    if (!$mb_id && $is_admin === 'super') return true;
    if (!$mb_id) $mb_id = isset($member['mb_id']) ? $member['mb_id'] : '';
    if (!$mb_id) return false;
    if ($is_admin === 'super' && $mb_id === $member['mb_id']) return true;
    $p = nl_profile($mb_id);
    return isset($p['approval_status']) && $p['approval_status'] === 'approved';
}
function nl_effective_permissions($mb_id = '') {
    global $member, $is_admin;
    if (!$mb_id) $mb_id = isset($member['mb_id']) ? $member['mb_id'] : '';
    if (!$mb_id) return array();
    if ($is_admin === 'super' && $mb_id === $member['mb_id']) return array_keys(nl_permission_labels());
    if (!nl_is_approved($mb_id)) return array();
    $profile = nl_profile($mb_id);
    $perms = array_fill_keys(nl_default_permissions(isset($profile['role_code']) ? $profile['role_code'] : ''), true);
    $table = nl_table('permission');
    if (nl_table_exists($table)) {
        $id = nl_sql_escape($mb_id);
        $result = sql_query("SELECT permission_code, effect FROM {$table} WHERE mb_id='{$id}'", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                if ($row['effect'] === 'deny') unset($perms[$row['permission_code']]);
                elseif ($row['effect'] === 'allow') $perms[$row['permission_code']] = true;
            }
        }
    }
    return array_keys($perms);
}
function nl_can($permission, $mb_id = '') {
    return in_array($permission, nl_effective_permissions($mb_id), true);
}
function nl_require_member() {
    global $is_member;
    if (!$is_member) alert('로그인이 필요합니다.', G5_BBS_URL.'/login.php?url='.urlencode($_SERVER['REQUEST_URI']));
}
function nl_require_approved() {
    nl_require_member();
    if (!nl_is_approved()) alert('승인된 리더만 이용할 수 있습니다.', nl_url('profile.php'));
}
function nl_require_permission($permission) {
    nl_require_approved();
    if (!nl_can($permission)) alert('이 기능을 사용할 권한이 없습니다.');
}
function nl_is_executive_role($role) {
    return in_array($role, array('president','political_vice_president','policy_director','senior_manager_div1','senior_manager_div2','policy_general_manager'), true);
}
function nl_member_level_for_profile($profile) {
    if (empty($profile) || ($profile['approval_status'] ?? '') !== 'approved') return 2;
    $role = $profile['role_code'] ?? 'leader';
    if ($role === 'policy_director') return 6;
    if (nl_is_executive_role($role) || $role === 'external_admin') return 5;
    if (in_array($role, array('department_manager','section_manager','deputy_manager','team_leader'), true)) return 4;
    return 3;
}
function nl_sync_member_level($mb_id) {
    global $g5;
    if (!$mb_id || !nl_installed()) return;

    $id = nl_sql_escape($mb_id);
    $memberRow = sql_fetch("SELECT mb_level FROM {$g5['member_table']} WHERE mb_id='{$id}' LIMIT 1", false);
    if (!isset($memberRow['mb_level'])) return;

    $profile = nl_profile($mb_id);
    if (empty($profile)) return;

    $current = max(1, min(10, (int)$memberRow['mb_level']));
    $required = (($profile['approval_status'] ?? '') === 'approved') ? nl_member_level_for_profile($profile) : 0;

    if (!nl_profile_level_tracking_supported()) {
        if ($required > $current) {
            sql_query("UPDATE {$g5['member_table']} SET mb_level=".(int)$required." WHERE mb_id='{$id}'", false);
        }
        return;
    }

    $base = isset($profile['base_mb_level']) ? (int)$profile['base_mb_level'] : 0;
    $managed = isset($profile['managed_mb_level']) ? (int)$profile['managed_mb_level'] : 0;

    if ($base < 1 || $managed < 1) {
        $base = $current;
        $managed = $current;
    } elseif ($current !== $managed) {
        $base = $current;
    }

    $desired = max($base, $required);
    $desired = max(1, min(10, $desired));
    if ($desired !== $current) {
        sql_query("UPDATE {$g5['member_table']} SET mb_level={$desired} WHERE mb_id='{$id}'", false);
    }

    $table = nl_table('profile');
    sql_query("UPDATE {$table} SET base_mb_level={$base}, managed_mb_level={$desired}, updated_at='".nl_now()."' WHERE mb_id='{$id}'", false);
}
function nl_allowed_roles_for_actor($mb_id = '') {
    global $member, $is_admin;
    if (!$mb_id) $mb_id = $member['mb_id'] ?? '';
    $all = array_keys(nl_role_labels());
    if ($is_admin === 'super' && $mb_id === ($member['mb_id'] ?? '')) return $all;
    $p = nl_profile($mb_id);
    $role = $p['role_code'] ?? '';
    if ($role === 'policy_director' || nl_can('system_manage', $mb_id)) return $all;
    if (in_array($role, array('senior_manager_div1','senior_manager_div2'), true)) {
        return array('leader','team_leader','deputy_manager','section_manager','department_manager');
    }
    return array('leader','team_leader','deputy_manager','section_manager','department_manager');
}
function nl_role_department_valid($role, $department) {
    if ($role === 'senior_manager_div1') return $department === 'div1';
    if ($role === 'senior_manager_div2') return $department === 'div2';
    if (in_array($role, array('policy_general_manager','policy_director','president','political_vice_president','external_admin'), true)) return $department === 'policy_office';
    return in_array($department, array('policy_office','div1','div2'), true);
}
function nl_can_grant_permission($permission, $mb_id = '') {
    global $member, $is_admin;
    if (!$mb_id) $mb_id = $member['mb_id'] ?? '';
    if ($is_admin === 'super' && $mb_id === ($member['mb_id'] ?? '')) return true;
    $role = nl_profile($mb_id)['role_code'] ?? '';
    if (in_array($permission, array('system_manage','anonymous_identity_reveal','file_manage'), true)) {
        return $role === 'policy_director';
    }
    return nl_can('permission_grant', $mb_id);
}
function nl_manageable_departments($mb_id = '') {
    global $member, $is_admin;
    if (!$mb_id) $mb_id = $member['mb_id'] ?? '';
    if ($is_admin === 'super' && $mb_id === ($member['mb_id'] ?? '')) return array('policy_office','div1','div2');
    $p = nl_profile($mb_id);
    $role = $p['role_code'] ?? '';
    if ($role === 'policy_director' || nl_can('system_manage', $mb_id)) return array('policy_office','div1','div2');
    if ($role === 'senior_manager_div1') return array('div1');
    if ($role === 'senior_manager_div2') return array('div2');
    if ($role === 'policy_general_manager') return array('policy_office');
    return array();
}
function nl_can_manage_target($target_mb_id, $actor_mb_id = '') {
    global $member, $is_admin;
    if (!$actor_mb_id) $actor_mb_id = $member['mb_id'] ?? '';
    if (!$actor_mb_id || !$target_mb_id || $target_mb_id === $actor_mb_id) return false;
    $target = nl_profile($target_mb_id);
    if (empty($target)) return false;
    if (!in_array($target['department'] ?? '', nl_manageable_departments($actor_mb_id), true)) return false;

    // 최고관리자와 정책국장은 전체 직책을 관리할 수 있다. 그 외 관리자는
    // 자신에게 허용된 직책 범위보다 높은/동급 관리직을 건드리지 못한다.
    if ($is_admin === 'super' && $actor_mb_id === ($member['mb_id'] ?? '')) return true;
    $actor = nl_profile($actor_mb_id);
    if (($actor['role_code'] ?? '') === 'policy_director') return true;
    return in_array($target['role_code'] ?? 'leader', nl_allowed_roles_for_actor($actor_mb_id), true);
}
function nl_schedule_scopes_for_member($mb_id = '') {
    global $member, $is_admin;
    if (!$mb_id) $mb_id = $member['mb_id'] ?? '';
    if (!$mb_id || !nl_is_approved($mb_id)) return array();
    if ($is_admin === 'super' && $mb_id === ($member['mb_id'] ?? '')) return array('common','div1','div2');
    $p = nl_profile($mb_id);
    if (($p['role_code'] ?? '') === 'policy_director') return array('common','div1','div2');
    switch ($p['department'] ?? 'policy_office') {
        case 'div1': return array('common','div1');
        case 'div2': return array('common','div2');
        default: return array('common');
    }
}
function nl_unavailable_scope_for_member($mb_id = '') {
    global $member;
    if (!$mb_id) $mb_id = $member['mb_id'] ?? '';
    $p = nl_profile($mb_id);
    $dept = $p['department'] ?? 'policy_office';
    return $dept === 'div1' ? 'div1' : ($dept === 'div2' ? 'div2' : 'common');
}
function nl_can_manage_schedule_scope($scope, $mb_id = '') {
    $map = array('common'=>'schedule_manage_common','div1'=>'schedule_manage_div1','div2'=>'schedule_manage_div2');
    return isset($map[$scope]) && nl_can($map[$scope], $mb_id);
}

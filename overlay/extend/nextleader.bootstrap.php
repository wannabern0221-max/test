<?php
if (!defined('_GNUBOARD_')) exit;

if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    if (!defined('NL_RUNTIME_COMPATIBLE')) define('NL_RUNTIME_COMPATIBLE', false);
    // Keep the selected theme renderable on an unsupported runtime; plugin pages stop in _common.php.
    if (!function_exists('nl_url')) { function nl_url($path = '') { return G5_URL.'/'; } }
    if (!function_exists('nl_board_url')) { function nl_board_url($bo_table) { return G5_URL.'/'; } }
    if (!function_exists('nl_public_board_url')) { function nl_public_board_url($bo_table) { return G5_URL.'/'; } }
    if (!function_exists('nl_public_article_url')) { function nl_public_article_url($bo_table, $wr_id) { return G5_URL.'/'; } }
    if (!function_exists('nl_h')) { function nl_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); } }
    if (!function_exists('nl_is_approved')) { function nl_is_approved($mb_id = '') { return false; } }
    return;
}
if (!defined('NL_RUNTIME_COMPATIBLE')) define('NL_RUNTIME_COMPATIBLE', true);

if (!defined('NL_VERSION')) define('NL_VERSION', '1.1.0');
if (!defined('NL_PLUGIN_PATH')) define('NL_PLUGIN_PATH', G5_PLUGIN_PATH.'/nextleader');
if (!defined('NL_PLUGIN_URL')) define('NL_PLUGIN_URL', G5_PLUGIN_URL.'/nextleader');

$nl_lib = NL_PLUGIN_PATH.'/lib.php';
if (is_file($nl_lib)) {
    include_once $nl_lib;
}

/** NEXT LEADER integration hooks loaded through Gnuboard /extend. */
if (function_exists('add_event') && function_exists('nl_installed')) {
    add_event('register_form_update_after', 'nl_hook_register_profile', 8, 2);

    add_event('common_header', 'nl_hook_guard_core_board', 1, 0);

    add_event('common_header', 'nl_hook_guard_core_memo', 2, 0);

    add_event('memo_form_update_before', 'nl_hook_guard_memo_recipients', 1, 1);
}

if (!function_exists('nl_hook_register_profile')) {
    function nl_hook_register_profile($mb_id, $w) {
        if ($w !== '' || !$mb_id || !nl_installed()) return;
        nl_ensure_profile($mb_id);
        nl_sync_member_level($mb_id);
    }
}


if (!function_exists('nl_hook_guard_core_board')) {
    function nl_hook_guard_core_board() {
        global $bo_table, $is_admin, $write;

        if (!nl_installed() || $is_admin === 'super') return;

        $boardId = isset($bo_table) ? preg_replace('/[^A-Za-z0-9_]/', '', (string)$bo_table) : '';
        if (!$boardId && isset($_REQUEST['bo_table']) && !is_array($_REQUEST['bo_table'])) {
            $boardId = preg_replace('/[^A-Za-z0-9_]/', '', (string)$_REQUEST['bo_table']);
        }
        if (!$boardId || !in_array($boardId, nl_allowed_boards(), true)) return;

        $script = isset($_SERVER['SCRIPT_NAME']) ? basename((string)$_SERVER['SCRIPT_NAME']) : '';
        $coreBoardScripts = array(
            'board.php','write.php','write_update.php','delete.php','delete_all.php',
            'download.php','good.php','link.php','move.php','move_update.php',
            'password.php','password_check.php','scrap_popin.php','scrap_popin_update.php'
        );
        if (!in_array($script, $coreBoardScripts, true)) return;

        nl_require_approved();

        if (!empty($write['wr_id']) && !nl_can_view_unpublished($write, $boardId)) {
            alert('게시 승인 전 콘텐츠는 작성자와 승인 권한자만 접근할 수 있습니다.', nl_public_board_url($boardId));
        }
    }
}

if (!function_exists('nl_hook_guard_core_memo')) {
    function nl_hook_guard_core_memo() {
        global $is_member, $is_admin;

        if (!nl_installed() || !$is_member || $is_admin === 'super') return;

        $script = isset($_SERVER['SCRIPT_NAME']) ? basename((string)$_SERVER['SCRIPT_NAME']) : '';
        if (!preg_match('/^memo(?:_[A-Za-z0-9_-]+)?\.php$/', $script)) return;

        if (!nl_is_approved()) {
            alert('쪽지는 가입 승인이 완료된 리더만 이용할 수 있습니다.', nl_url('profile.php'));
        }

        if ($script === 'memo_form.php' && !empty($_REQUEST['me_recv_mb_id'])) {
            $target = preg_replace('/[^A-Za-z0-9_]/', '', (string)$_REQUEST['me_recv_mb_id']);
            if ($target && !nl_is_approved($target)) {
                alert_close('승인된 리더에게만 쪽지를 보낼 수 있습니다.');
            }
        }
    }
}

if (!function_exists('nl_hook_guard_memo_recipients')) {
    function nl_hook_guard_memo_recipients($recv_list) {
        global $is_admin;

        if (!nl_installed() || $is_admin === 'super') return;
        nl_require_approved();

        foreach ((array)$recv_list as $raw_id) {
            $target = preg_replace('/[^A-Za-z0-9_]/', '', (string)$raw_id);
            if (!$target || !nl_is_approved($target)) {
                alert('승인된 리더에게만 쪽지를 보낼 수 있습니다.');
            }
        }
    }
}

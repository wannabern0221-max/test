<?php
if (!defined('_GNUBOARD_')) exit;

function nl_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function nl_e($value) {
    return nl_h($value);
}
function nl_sql_escape($value) {
    $value = (string)$value;
    return function_exists('sql_real_escape_string') ? sql_real_escape_string($value) : addslashes($value);
}
function nl_now() {
    return date('Y-m-d H:i:s');
}
function nl_table($name) {
    static $allowed = array(
        'profile','permission','notification','schedule','unavailable','glossary','quiz',
        'anonymous_post','anonymous_comment','audit_log','file','news'
    );
    if (!in_array($name, $allowed, true)) return '';
    return G5_TABLE_PREFIX.'nl_'.$name;
}
function nl_table_exists($table) {
    if (!$table || !preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;
    $esc = nl_sql_escape($table);
    $row = sql_fetch("SHOW TABLES LIKE '{$esc}'", false);
    return is_array($row) && count($row) > 0;
}
function nl_installed() {
    return nl_table_exists(nl_table('profile'));
}
function nl_url($path = '') {
    return NL_PLUGIN_URL.($path ? '/'.ltrim($path, '/') : '');
}
function nl_board_url($bo_table) {
    return G5_BBS_URL.'/board.php?bo_table='.rawurlencode($bo_table);
}
function nl_public_board_url($bo_table) {
    if (!in_array($bo_table, nl_allowed_boards(), true)) return G5_URL.'/';
    return nl_url('content.php?bo='.rawurlencode($bo_table));
}
function nl_public_article_url($bo_table, $wr_id) {
    if (!in_array($bo_table, nl_allowed_boards(), true)) return G5_URL.'/';
    return nl_url('article.php?bo='.rawurlencode($bo_table).'&wr_id='.(int)$wr_id);
}
function nl_enqueue_assets() {
    if (function_exists('add_stylesheet')) {
        add_stylesheet('<link rel="stylesheet" href="'.nl_h(NL_PLUGIN_URL.'/assets/app.css?v='.NL_VERSION).'">', 5);
    }
    if (function_exists('add_javascript')) {
        add_javascript('<script src="'.nl_h(NL_PLUGIN_URL.'/assets/app.js?v='.NL_VERSION).'"></script>', 5);
    }
}
function nl_csrf_token() {
    if (empty($_SESSION['nl_csrf_token']) || !is_string($_SESSION['nl_csrf_token'])) {
        $_SESSION['nl_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['nl_csrf_token'];
}
function nl_csrf_field() {
    return '<input type="hidden" name="nl_csrf" value="'.nl_h(nl_csrf_token()).'">';
}
function nl_verify_csrf($abort = true) {
    $sent = isset($_POST['nl_csrf']) ? (string)$_POST['nl_csrf'] : '';
    $ok = isset($_SESSION['nl_csrf_token']) && is_string($_SESSION['nl_csrf_token']) && hash_equals($_SESSION['nl_csrf_token'], $sent);
    if (!$ok && $abort) alert('요청 검증에 실패했습니다. 페이지를 새로고침한 뒤 다시 시도해 주세요.');
    return $ok;
}
function nl_post_str($key, $default = '') {
    if (!isset($_POST[$key]) || is_array($_POST[$key])) return $default;
    return trim((string)$_POST[$key]);
}
function nl_get_str($key, $default = '') {
    if (!isset($_GET[$key]) || is_array($_GET[$key])) return $default;
    return trim((string)$_GET[$key]);
}
function nl_safe_internal_url($url) {
    $url = trim((string)$url);
    if ($url === '') return '';
    if (preg_match('/[\x00-\x1F\x7F]/', $url) || strpos($url, '\\') !== false) return '';
    // Root-relative links are allowed, protocol-relative links are not.
    if (substr($url, 0, 1) === '/' && substr($url, 0, 2) !== '//') return $url;

    $parts = @parse_url($url);
    if (!$parts || !empty($parts['user']) || !empty($parts['pass'])) return '';
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    if (!in_array($scheme, array('http', 'https'), true) || empty($parts['host'])) return '';

    $base = @parse_url(G5_URL ?: ('http://'.($_SERVER['HTTP_HOST'] ?? 'localhost')));
    if (!$base || empty($base['host'])) return '';
    $baseScheme = strtolower((string)($base['scheme'] ?? 'http'));
    if ($scheme !== $baseScheme || strcasecmp($base['host'], $parts['host']) !== 0) return '';

    $basePort = isset($base['port']) ? (int)$base['port'] : ($baseScheme === 'https' ? 443 : 80);
    $urlPort = isset($parts['port']) ? (int)$parts['port'] : ($scheme === 'https' ? 443 : 80);
    return $basePort === $urlPort ? $url : '';
}
function nl_safe_external_url($url) {
    $url = trim((string)$url);
    if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url) || strpos($url, '\\') !== false) return '';
    $parts = @parse_url($url);
    if (!$parts || !empty($parts['user']) || !empty($parts['pass'])) return '';
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    if (!in_array($scheme, array('http', 'https'), true) || empty($parts['host'])) return '';
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}
function nl_redirect_local($url, $fallback = '') {
    if (!$fallback) $fallback = nl_url('dashboard.php');
    $url = trim((string)$url);

    if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url) || strpos($url, '\\') !== false) {
        goto_url($fallback);
    }
    if (substr($url, 0, 2) === '//' || substr($url, 0, 1) !== '/') {
        goto_url($fallback);
    }

    $parts = parse_url($url);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
        goto_url($fallback);
    }
    goto_url($url);
}

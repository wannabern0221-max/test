<?php
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    @header('Content-Type: text/html; charset=utf-8');
    die('NEXT LEADER 확장은 PHP 8.2 이상이 필요합니다. 현재 PHP: '.htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8'));
}
$nl_root = dirname(__DIR__, 2);
include_once $nl_root.'/common.php';
if (!defined('NL_PLUGIN_PATH')) {
    define('NL_PLUGIN_PATH', __DIR__);
    define('NL_PLUGIN_URL', G5_PLUGIN_URL.'/nextleader');
    include_once __DIR__.'/lib.php';
}

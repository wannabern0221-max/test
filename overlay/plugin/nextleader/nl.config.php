<?php
if (!defined('_GNUBOARD_')) exit;

// 파일 저장공간 운영 기본값입니다.
define('NL_FILE_MAX_BYTES', 50 * 1024 * 1024);                 // 50 MiB
define('NL_STORAGE_CLEANUP_TRIGGER', 9000000000);              // 9,000,000,000 B
define('NL_STORAGE_CLEANUP_TARGET', 7500000000);               // 7,500,000,000 B
define('NL_STORAGE_DIR_NAME', 'nextleader-files');

define('NL_BOARD_GROUP', 'nextleader');
define('NL_BOARD_NOTICE', 'nl_notice');
define('NL_BOARD_CARDS', 'nl_cards');
define('NL_BOARD_POLICY', 'nl_policy');
define('NL_BOARD_ACTIVITY', 'nl_activity');

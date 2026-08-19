<?php
include_once './_common.php';
$bo=preg_replace('/[^A-Za-z0-9_]/','',nl_get_str('bo'));
$wrId=(int)nl_get_str('wr_id','0');
$no=(int)nl_get_str('no','-1');
if(!in_array($bo,nl_allowed_boards(),true)||$wrId<1||$no<0) { http_response_code(404); exit; }
$table=nl_board_table($bo);
$post=sql_fetch("SELECT wr_id FROM {$table} WHERE wr_id={$wrId} AND wr_is_comment=0 AND wr_10='published' LIMIT 1",false);
if(empty($post['wr_id'])) { http_response_code(404); exit; }
$boEsc=nl_sql_escape($bo);
$file=sql_fetch("SELECT bf_source,bf_file,bf_filesize FROM {$g5['board_file_table']} WHERE bo_table='{$boEsc}' AND wr_id={$wrId} AND bf_no={$no} LIMIT 1",false);
if(empty($file['bf_file'])) { http_response_code(404); exit; }
$stored=basename($file['bf_file']);
$path=G5_DATA_PATH.'/file/'.$bo.'/'.$stored;
if(!is_file($path)||!is_readable($path)){ http_response_code(404); exit; }
$name=str_replace(array("\r","\n",'"'),'',stripslashes($file['bf_source'] ?: $stored));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Content-Type: application/octet-stream');
header('Content-Length: '.filesize($path));
header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode($name));
readfile($path);
exit;

<?php
if (!defined('_GNUBOARD_')) exit;
if (!in_array($bo_table, nl_allowed_boards(), true)) return;
if (!nl_can_write_board($bo_table)) alert('콘텐츠 작성 권한이 없습니다.');
$action = isset($_POST['nl_publish_action']) && !is_array($_POST['nl_publish_action']) ? (string)$_POST['nl_publish_action'] : 'request';
$status = ($action==='publish' && nl_can('content_approve')) ? 'published' : 'pending';
$table = nl_board_table($bo_table);
$wrId = (int)$wr_id;
if($wrId<1 || !$table) return;
$current = sql_fetch("SELECT mb_id,wr_subject FROM {$table} WHERE wr_id={$wrId} AND wr_is_comment=0 LIMIT 1", false);
$statusEsc = nl_sql_escape($status);
sql_query("UPDATE {$table} SET wr_10='{$statusEsc}', wr_9='' WHERE wr_id={$wrId} AND wr_is_comment=0", false);
$url = nl_board_url($bo_table).'&wr_id='.$wrId;
if($status==='pending'){
    nl_notify_permission_holders('content_approve','콘텐츠 승인 요청','['.$board['bo_subject'].'] '.($current['wr_subject'] ?? ''), nl_url('content-manager.php'));
    nl_audit('content_submit','board_post',$bo_table.':'.$wrId,array('status'=>'pending','subject'=>$current['wr_subject'] ?? ''));
}else{
    if(!empty($current['mb_id']) && $current['mb_id']!==($member['mb_id'] ?? '')) nl_notify($current['mb_id'],'content_published','콘텐츠가 게시되었습니다.',$current['wr_subject'] ?? '',nl_public_article_url($bo_table,$wrId));
    nl_audit('content_publish','board_post',$bo_table.':'.$wrId,array('status'=>'published','subject'=>$current['wr_subject'] ?? ''));
}
delete_cache_latest($bo_table);

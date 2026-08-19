<?php
include_once './_common.php';
nl_require_approved();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    nl_verify_csrf();
    $id = nl_sql_escape($member['mb_id']); $now=nl_now();
    sql_query("UPDATE ".nl_table('notification')." SET is_read=1, read_at=COALESCE(read_at,'{$now}') WHERE mb_id='{$id}' AND is_read=0", false);
    goto_url(nl_url('notifications.php'));
}
nl_enqueue_assets();
$id = nl_sql_escape($member['mb_id']);
$result = sql_query("SELECT * FROM ".nl_table('notification')." WHERE mb_id='{$id}' ORDER BY id DESC LIMIT 100", false);
$rows=array(); if($result) while($r=sql_fetch_array($result)) $rows[]=$r;
$g5['title']='알림'; include_once G5_PATH.'/head.php';
?>
<div class="nl-page nl-narrow"><div class="nl-page-head"><p class="nl-kicker">시스템 알림</p><h1>알림</h1><p>가입 승인, 콘텐츠 승인, 일정 변경 등 시스템 이벤트를 쪽지와 분리해 보여줍니다.</p></div>
<div class="nl-actions nl-actions--bottom"><form method="post"><?php echo nl_csrf_field(); ?><button class="nl-btn" type="submit">모두 읽음 처리</button></form><a class="nl-btn" href="<?php echo G5_BBS_URL; ?>/memo.php">쪽지함</a></div>
<ul class="nl-list"><?php if(!$rows){ ?><li><div class="nl-empty">알림이 없습니다.</div></li><?php } foreach($rows as $r){ $target=nl_safe_internal_url($r['target_url'] ?? ''); ?><li><?php if($target){ ?><a href="<?php echo nl_h($target); ?>"><?php } else { ?><div class="nl-notification-static"><?php } ?><div class="nl-profile-line"><strong><?php echo nl_h($r['title']); ?></strong><?php if(!$r['is_read']) echo '<span class="nl-badge nl-badge--pending">새 알림</span>'; ?></div><small><?php echo nl_h($r['created_at']); ?></small><p><?php echo nl_h($r['message']); ?></p><?php if($target){ ?></a><?php } else { ?></div><?php } ?></li><?php } ?></ul></div>
<?php include_once G5_PATH.'/tail.php'; ?>

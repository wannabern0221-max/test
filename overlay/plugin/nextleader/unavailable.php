<?php
include_once './_common.php'; nl_require_approved();
$scope=nl_unavailable_scope_for_member();
if($_SERVER['REQUEST_METHOD']==='POST'){
 nl_verify_csrf(); $action=nl_post_str('action'); $t=nl_table('unavailable'); $mb=nl_sql_escape($member['mb_id']);
 if($action==='add'){
   $date=nl_post_str('date'); $note=substr(nl_post_str('note'),0,255);
   $dt=DateTime::createFromFormat('Y-m-d',$date); if(!$dt||$dt->format('Y-m-d')!==$date) alert('날짜를 확인해 주세요.');
   $scopeE=nl_sql_escape($scope);$noteE=nl_sql_escape($note);$now=nl_now();
   sql_query("INSERT INTO {$t} (mb_id,scope,unavailable_date,note,created_at,updated_at) VALUES ('{$mb}','{$scopeE}','{$date}','{$noteE}','{$now}','{$now}') ON DUPLICATE KEY UPDATE scope=VALUES(scope),note=VALUES(note),updated_at=VALUES(updated_at)",false);
   nl_audit('unavailable_save','unavailable',$date,array('scope'=>$scope)); goto_url(nl_url('unavailable.php'));
 }
 if($action==='delete'){
   $id=(int)nl_post_str('id','0'); sql_query("DELETE FROM {$t} WHERE id={$id} AND mb_id='{$mb}'",false); nl_audit('unavailable_delete','unavailable',$id); goto_url(nl_url('unavailable.php'));
 }
}
$mb=nl_sql_escape($member['mb_id']);$res=sql_query("SELECT * FROM ".nl_table('unavailable')." WHERE mb_id='{$mb}' AND unavailable_date>=CURDATE() ORDER BY unavailable_date",false);$mine=array();if($res)while($r=sql_fetch_array($res))$mine[]=$r;
$canScope=nl_can_manage_schedule_scope($scope);$team=array();if($canScope){$scopeE=nl_sql_escape($scope);$res=sql_query("SELECT u.*,m.mb_name,m.mb_nick,p.role_code FROM ".nl_table('unavailable')." u LEFT JOIN {$g5['member_table']} m ON m.mb_id=u.mb_id LEFT JOIN ".nl_table('profile')." p ON p.mb_id=u.mb_id WHERE u.scope='{$scopeE}' AND u.unavailable_date>=CURDATE() ORDER BY u.unavailable_date,m.mb_name",false);if($res)while($r=sql_fetch_array($res))$team[]=$r;}
nl_enqueue_assets();$g5['title']='불가일';include_once G5_PATH.'/head.php';
?>
<div class="nl-page"><div class="nl-page-head"><p class="nl-kicker">일정 관리</p><h1>불가일 등록</h1><p>내 소속 범위(<?php echo nl_h($scope==='common'?'정책국':($scope==='div1'?'정책1부':'정책2부')); ?>)에 대해서만 불가일을 등록합니다.</p></div><div class="nl-tabs"><a href="<?php echo nl_url('schedule.php?mode=leader'); ?>">내부 일정</a><a class="is-active" href="<?php echo nl_url('unavailable.php'); ?>">불가일</a></div>
<div class="nl-grid nl-grid--2"><form class="nl-panel nl-form" method="post"><?php echo nl_csrf_field(); ?><input type="hidden" name="action" value="add"><h2>불가일 추가</h2><div class="nl-field"><label>날짜</label><input type="date" name="date" min="<?php echo date('Y-m-d'); ?>" required></div><div class="nl-field"><label>메모 (선택)</label><input type="text" name="note" maxlength="255" placeholder="예: 실습"></div><button class="nl-btn nl-btn--primary" type="submit">저장</button></form><section class="nl-panel"><h2>내 불가일</h2><?php if(!$mine)echo '<div class="nl-empty">등록된 불가일이 없습니다.</div>';?><ul class="nl-list"><?php foreach($mine as $r){ ?><li><div class="nl-profile-line"><strong><?php echo nl_h($r['unavailable_date']); ?></strong><span><?php echo nl_h($r['note']); ?></span></div><form method="post" class="nl-actions nl-actions--compact"><?php echo nl_csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"><button class="nl-btn nl-btn--small nl-btn--danger" type="submit">삭제</button></form></li><?php } ?></ul></section></div>
<?php if($canScope){ ?><section class="nl-section"><div class="nl-section-head"><div><h2>소속 불가일 현황</h2><p>일정 관리 권한이 있어 같은 범위의 등록 현황을 볼 수 있습니다.</p></div></div><div class="nl-table-wrap"><table class="nl-table"><thead><tr><th>날짜</th><th>이름</th><th>직책</th><th>메모</th></tr></thead><tbody><?php foreach($team as $r){ ?><tr><td><?php echo nl_h($r['unavailable_date']); ?></td><td><?php echo nl_h($r['mb_name'] ?: $r['mb_nick']); ?></td><td><?php echo nl_h(nl_role_label($r['role_code'])); ?></td><td><?php echo nl_h($r['note']); ?></td></tr><?php } if(!$team){ ?><tr><td colspan="4">등록된 불가일이 없습니다.</td></tr><?php } ?></tbody></table></div></section><?php } ?></div>
<?php include_once G5_PATH.'/tail.php'; ?>

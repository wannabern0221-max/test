<?php
include_once './_common.php';
if (!nl_installed()) alert('NEXT LEADER 확장 기능이 설치되지 않았습니다.');
$leaderMode = nl_get_str('mode') === 'leader';
if ($leaderMode) nl_require_approved();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    nl_require_approved();
    nl_verify_csrf();
    $action = nl_post_str('action');
    if ($action === 'create') {
        $scope = nl_post_str('scope','common');
        if (!in_array($scope,array('common','div1','div2'),true) || !nl_can_manage_schedule_scope($scope)) alert('해당 범위의 일정을 등록할 권한이 없습니다.');
        $title = substr(nl_post_str('title'),0,255);
        $details = nl_post_str('details');
        $location = substr(nl_post_str('location'),0,255);
        $visibility = nl_post_str('visibility','leaders') === 'public' ? 'public' : 'leaders';
        $startRaw = nl_post_str('starts_at'); $endRaw = nl_post_str('ends_at');
        $startTs = strtotime($startRaw); $endTs = $endRaw ? strtotime($endRaw) : false;
        if (!$title || !$startTs) alert('일정 제목과 시작 시각을 확인해 주세요.');
        if ($endTs && $endTs < $startTs) alert('종료 시각은 시작 시각보다 빠를 수 없습니다.');
        $t=nl_table('schedule'); $now=nl_now(); $mb=nl_sql_escape($member['mb_id']);
        $titleE=nl_sql_escape($title);$detailsE=nl_sql_escape($details);$locE=nl_sql_escape($location);$scopeE=nl_sql_escape($scope);$visE=nl_sql_escape($visibility);
        $start=date('Y-m-d H:i:s',$startTs); $end=$endTs?"'".date('Y-m-d H:i:s',$endTs)."'":'NULL';
        sql_query("INSERT INTO {$t} (title,details,scope,visibility,starts_at,ends_at,location,created_by,updated_by,created_at,updated_at) VALUES ('{$titleE}','{$detailsE}','{$scopeE}','{$visE}','{$start}',{$end},'{$locE}','{$mb}','{$mb}','{$now}','{$now}')", false);
        $r=sql_fetch("SELECT LAST_INSERT_ID() id",false); $sid=(int)($r['id']??0);
        nl_audit('schedule_create','schedule',$sid,array('scope'=>$scope,'visibility'=>$visibility));
        $profiles=sql_query("SELECT mb_id,department FROM ".nl_table('profile')." WHERE approval_status='approved'",false);
        if($profiles) while($p=sql_fetch_array($profiles)){
            $scopes=nl_schedule_scopes_for_member($p['mb_id']);
            if(in_array($scope,$scopes,true) && $p['mb_id']!==$member['mb_id']) nl_notify($p['mb_id'],'schedule','일정이 등록되었습니다.',$title,nl_url('schedule.php?mode=leader'));
        }
        goto_url(nl_url('schedule.php?mode=leader'));
    }
    if ($action === 'delete') {
        $id=(int)nl_post_str('id','0'); $row=sql_fetch("SELECT * FROM ".nl_table('schedule')." WHERE id={$id} LIMIT 1",false);
        if(!$row || !nl_can_manage_schedule_scope($row['scope'])) alert('이 일정을 삭제할 권한이 없습니다.');
        sql_query("DELETE FROM ".nl_table('schedule')." WHERE id={$id}",false);
        nl_audit('schedule_delete','schedule',$id,array('title'=>$row['title'],'scope'=>$row['scope']));
        goto_url(nl_url('schedule.php?mode=leader'));
    }
}

$conditions=array();
if ($leaderMode) {
    $scopes=nl_schedule_scopes_for_member();
    $quoted=array(); foreach($scopes as $s) $quoted[]="'".nl_sql_escape($s)."'";
    $conditions[]='scope IN ('.implode(',',$quoted).')';
} else {
    $conditions[]="visibility='public'";
}
$from = date('Y-m-d 00:00:00', strtotime('-30 days'));
$conditions[]="starts_at >= '".nl_sql_escape($from)."'";
$sql="SELECT * FROM ".nl_table('schedule')." WHERE ".implode(' AND ',$conditions)." ORDER BY starts_at ASC LIMIT 200";
$res=sql_query($sql,false);$rows=array();if($res)while($r=sql_fetch_array($res))$rows[]=$r;
$canAny = $leaderMode && (nl_can('schedule_manage_common')||nl_can('schedule_manage_div1')||nl_can('schedule_manage_div2'));
nl_enqueue_assets(); $g5['title']=$leaderMode?'내부 일정':'정책 일정'; include_once G5_PATH.'/head.php';
?>
<div class="nl-page"><div class="nl-page-head"><p class="nl-kicker">일정</p><h1><?php echo $leaderMode?'정책국 내부 일정':'정책 일정'; ?></h1><p><?php echo $leaderMode?'소속 범위에 맞는 정책국 일정을 확인합니다.':'공개된 정책국 주요 일정을 확인합니다.'; ?></p></div>
<?php if($leaderMode){ ?><div class="nl-tabs"><a class="is-active" href="<?php echo nl_url('schedule.php?mode=leader'); ?>">내부 일정</a><a href="<?php echo nl_url('unavailable.php'); ?>">불가일</a><a href="<?php echo nl_url('schedule.php'); ?>">공개 일정</a></div><?php } ?>
<?php if($canAny){ ?><details class="nl-panel nl-panel--spaced"><summary><strong>일정 등록</strong></summary><form method="post" class="nl-form nl-form--nested"><?php echo nl_csrf_field(); ?><input type="hidden" name="action" value="create"><div class="nl-field"><label>일정 제목</label><input name="title" type="text" required maxlength="255"></div><div class="nl-field-row nl-field-row--3"><div class="nl-field"><label>범위</label><select name="scope"><?php foreach(array('common'=>'정책국 공통','div1'=>'정책1부','div2'=>'정책2부') as $s=>$l) if(nl_can_manage_schedule_scope($s)) echo '<option value="'.nl_h($s).'">'.nl_h($l).'</option>'; ?></select></div><div class="nl-field"><label>공개 범위</label><select name="visibility"><option value="leaders">리더 전용</option><option value="public">전체 공개</option></select></div><div class="nl-field"><label>장소</label><input name="location" type="text" maxlength="255"></div></div><div class="nl-field-row"><div class="nl-field"><label>시작</label><input name="starts_at" type="datetime-local" required></div><div class="nl-field"><label>종료</label><input name="ends_at" type="datetime-local"></div></div><div class="nl-field"><label>상세</label><textarea name="details"></textarea></div><button class="nl-btn nl-btn--primary" type="submit">일정 등록</button></form></details><?php } ?>
<div class="nl-schedule-list"><?php if(!$rows){ ?><div class="nl-empty">표시할 일정이 없습니다.</div><?php } foreach($rows as $r){ ?><article class="nl-schedule-item"><time datetime="<?php echo nl_h($r['starts_at']); ?>"><?php echo nl_h(date('m.d ('.array('일','월','화','수','목','금','토')[date('w',strtotime($r['starts_at']))].')',strtotime($r['starts_at']))); ?><br><small><?php echo nl_h(date('H:i',strtotime($r['starts_at']))); ?></small></time><div><div class="nl-profile-line"><h3><?php echo nl_h($r['title']); ?></h3><span class="nl-badge"><?php echo nl_h($r['scope']==='common'?'공통':($r['scope']==='div1'?'정책1부':'정책2부')); ?></span><?php if($r['visibility']==='public') echo '<span class="nl-badge nl-badge--approved">공개</span>'; ?></div><?php if($r['location']){ ?><p><?php echo nl_h($r['location']); ?></p><?php } ?><?php if($r['details']){ ?><p><?php echo nl_h($r['details']); ?></p><?php } ?></div><?php if($leaderMode && nl_can_manage_schedule_scope($r['scope'])){ ?><form method="post"><?php echo nl_csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"><button class="nl-btn nl-btn--small nl-btn--danger" data-confirm="이 일정을 삭제할까요?" type="submit">삭제</button></form><?php } ?></article><?php } ?></div></div>
<?php include_once G5_PATH.'/tail.php'; ?>

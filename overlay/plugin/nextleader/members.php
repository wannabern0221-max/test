<?php
include_once './_common.php';
nl_require_approved();
nl_enqueue_assets();
$term = nl_get_str('q');
$where = "p.approval_status='approved'";
if ($term !== '') {
    $e = nl_sql_escape($term);
    $where .= " AND (m.mb_name LIKE '%{$e}%' OR m.mb_nick LIKE '%{$e}%' OR p.school LIKE '%{$e}%' OR p.role_code LIKE '%{$e}%')";
}
$sql = "SELECT p.mb_id,p.department,p.role_code,p.school,p.cohort,m.mb_name,m.mb_nick FROM ".nl_table('profile')." p JOIN {$g5['member_table']} m ON m.mb_id=p.mb_id WHERE {$where} ORDER BY FIELD(p.department,'policy_office','div1','div2'), m.mb_name, p.mb_id LIMIT 300";
$result = sql_query($sql, false);
$rows=array(); if ($result) while($r=sql_fetch_array($result)) $rows[]=$r;
$g5['title']='리더 찾기 · 쪽지 보내기';
include_once G5_PATH.'/head.php';
?>
<div class="nl-page">
  <div class="nl-page-head"><p class="nl-kicker">리더 연락</p><h1>리더 찾기</h1><p>승인된 리더를 찾아 그누보드 기본 쪽지 기능으로 1:1 메시지를 보낼 수 있습니다.</p></div>
  <form class="nl-searchbar" method="get"><input type="search" name="q" value="<?php echo nl_h($term); ?>" placeholder="이름·학교·직책 검색" aria-label="리더 검색"><button class="nl-btn" type="submit">검색</button><a class="nl-btn" href="<?php echo G5_BBS_URL; ?>/memo.php">쪽지함</a></form>
  <div class="nl-table-wrap"><table class="nl-table"><thead><tr><th>이름</th><th>소속</th><th>직책</th><th>학교/기수</th><th>쪽지</th></tr></thead><tbody>
  <?php if (!$rows) { ?><tr><td colspan="5"><div class="nl-empty">조건에 맞는 리더가 없습니다.</div></td></tr><?php } ?>
  <?php foreach($rows as $r){ $name=$r['mb_name'] ?: $r['mb_nick'] ?: $r['mb_id']; ?>
    <tr><td><strong><?php echo nl_h($name); ?></strong></td><td><?php echo nl_h(nl_department_label($r['department'])); ?></td><td><?php echo nl_h(nl_role_label($r['role_code'])); ?></td><td><?php echo nl_h(trim(($r['school']?:'').($r['cohort']?' · '.$r['cohort'].'기':''))); ?></td><td><?php if($r['mb_id']!==$member['mb_id']){ ?><a class="nl-btn nl-btn--small" href="<?php echo G5_BBS_URL; ?>/memo_form.php?me_recv_mb_id=<?php echo rawurlencode($r['mb_id']); ?>" onclick="window.open(this.href,'win_memo','left=50,top=50,width=616,height=500,scrollbars=1');return false;">쪽지 보내기</a><?php } else { ?><span class="nl-muted-text">나</span><?php } ?></td></tr>
  <?php } ?>
  </tbody></table></div>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>

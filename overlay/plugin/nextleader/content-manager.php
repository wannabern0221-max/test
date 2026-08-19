<?php
include_once './_common.php';
nl_require_permission('content_approve');
nl_enqueue_assets();

$boards = nl_allowed_boards();
$boardLabels = array(
    NL_BOARD_NOTICE => '공지사항',
    NL_BOARD_CARDS => '카드뉴스',
    NL_BOARD_POLICY => '정책 콘텐츠',
    NL_BOARD_ACTIVITY => '사업자료'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    nl_verify_csrf();
    $action = nl_post_str('action');
    $bo = preg_replace('/[^A-Za-z0-9_]/', '', nl_post_str('bo_table'));
    $wrId = (int)nl_post_str('wr_id', '0');
    if (!in_array($bo, $boards, true) || $wrId < 1) alert('잘못된 게시물입니다.');
    $table = nl_board_table($bo);
    $row = sql_fetch("SELECT wr_id, mb_id, wr_subject, wr_10 FROM {$table} WHERE wr_id={$wrId} AND wr_is_comment=0 LIMIT 1", false);
    if (empty($row['wr_id'])) alert('게시물을 찾을 수 없습니다.');

    if ($action === 'approve') {
        sql_query("UPDATE {$table} SET wr_10='published', wr_9='' WHERE wr_id={$wrId}", false);
        if (!empty($row['mb_id'])) nl_notify($row['mb_id'], 'content_approved', '콘텐츠가 게시되었습니다.', $row['wr_subject'], nl_public_article_url($bo,$wrId));
        nl_audit('content_approve', 'board_post', $bo.':'.$wrId, array('subject'=>$row['wr_subject']));
        delete_cache_latest($bo);
        alert('게시 승인했습니다.', nl_url('content-manager.php'));
    }

    if ($action === 'reject') {
        $note = nl_post_str('review_note');
        if ($note === '') alert('반려 사유를 입력해 주세요.');
        $noteEsc = nl_sql_escape(mb_substr($note, 0, 1000));
        sql_query("UPDATE {$table} SET wr_10='rejected', wr_9='{$noteEsc}' WHERE wr_id={$wrId}", false);
        if (!empty($row['mb_id'])) nl_notify($row['mb_id'], 'content_rejected', '콘텐츠가 반려되었습니다.', $note, G5_BBS_URL.'/write.php?w=u&bo_table='.rawurlencode($bo).'&wr_id='.$wrId);
        nl_audit('content_reject', 'board_post', $bo.':'.$wrId, array('subject'=>$row['wr_subject'],'note'=>$note));
        delete_cache_latest($bo);
        alert('반려 처리했습니다.', nl_url('content-manager.php'));
    }
    alert('지원하지 않는 작업입니다.');
}

$items = array();
foreach ($boards as $bo) {
    $table = nl_board_table($bo);
    if (!$table || !nl_table_exists($table)) continue;
    $result = sql_query("SELECT wr_id, mb_id, wr_subject, wr_datetime, wr_10, wr_9 FROM {$table} WHERE wr_is_comment=0 AND wr_10 IN ('pending','rejected') ORDER BY wr_datetime DESC LIMIT 100", false);
    if ($result) while ($row = sql_fetch_array($result)) {
        $row['_board'] = $bo;
        $items[] = $row;
    }
}
usort($items, function($a,$b){ return strcmp($b['wr_datetime'], $a['wr_datetime']); });

$g5['title'] = '콘텐츠 승인센터';
include_once G5_PATH.'/head.php';
?>
<div class="nl-page">
  <div class="nl-page-head"><p class="nl-kicker">콘텐츠 운영</p><h1>콘텐츠 승인센터</h1><p>승인된 모든 리더가 작성한 콘텐츠를 검토합니다. 게시 승인 전 글은 일반 공개 화면에 노출되지 않습니다.</p></div>
  <div class="nl-alert">승인하면 즉시 공개됩니다. 반려 시 사유가 작성자에게 시스템 알림으로 전달됩니다.</div>
  <section class="nl-section">
    <div class="nl-table-wrap"><table class="nl-table">
      <thead><tr><th>상태</th><th>유형</th><th>제목/작성자</th><th>작성일</th><th>검토</th></tr></thead>
      <tbody>
      <?php if (!$items) { ?><tr><td colspan="5"><div class="nl-empty">승인 대기 또는 반려된 콘텐츠가 없습니다.</div></td></tr><?php } ?>
      <?php foreach ($items as $row) { $bo=$row['_board']; ?>
        <tr>
          <td><?php echo nl_status_badge($row['wr_10']); ?></td>
          <td><?php echo nl_h($boardLabels[$bo] ?? $bo); ?></td>
          <td><a class="nl-source-link" href="<?php echo G5_BBS_URL.'/board.php?bo_table='.rawurlencode($bo).'&amp;wr_id='.(int)$row['wr_id']; ?>"><strong><?php echo nl_h($row['wr_subject']); ?></strong></a><br><small><?php echo nl_h($row['mb_id']); ?></small><?php if ($row['wr_9']) { ?><br><small>최근 반려 사유: <?php echo nl_h($row['wr_9']); ?></small><?php } ?></td>
          <td><?php echo nl_h(substr($row['wr_datetime'],0,16)); ?></td>
          <td>
            <form class="nl-inline-form" method="post" action="">
              <?php echo nl_csrf_field(); ?><input type="hidden" name="bo_table" value="<?php echo nl_h($bo); ?>"><input type="hidden" name="wr_id" value="<?php echo (int)$row['wr_id']; ?>">
              <button class="nl-btn nl-btn--small nl-btn--primary" type="submit" name="action" value="approve">게시 승인</button>
              <input type="text" name="review_note" maxlength="1000" placeholder="반려 사유" aria-label="반려 사유">
              <button class="nl-btn nl-btn--small" type="submit" name="action" value="reject">반려</button>
            </form>
          </td>
        </tr>
      <?php } ?>
      </tbody>
    </table></div>
  </section>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>

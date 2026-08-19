<?php
include_once './_common.php';

nl_require_approved();

$canManage = nl_can('file_manage');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    nl_verify_csrf();
    $action = nl_post_str('action');

    if ($action === 'upload') {
        if (!$canManage) alert('파일 관리 권한이 없습니다.');

        $audience = nl_post_str('audience', 'leaders');
        $downloadAllowed = (int)nl_post_str('download_allowed', '0') === 1;
        $label = nl_post_str('label');
        list($ok, $result) = nl_store_upload($_FILES['file'] ?? array(), $audience, $downloadAllowed, $label);
        if (!$ok) alert($result);
        goto_url(nl_url('files.php'));
    }

    if ($action === 'delete') {
        if (!$canManage) alert('파일 관리 권한이 없습니다.');

        $id = (int)nl_post_str('id', '0');
        $row = nl_file_row($id);
        if (!$row) alert('파일을 찾을 수 없습니다.');
        if (!nl_delete_file_record($row, 'manual')) alert('파일 삭제를 완료하지 못했습니다.');
        goto_url(nl_url('files.php'));
    }

    if ($action === 'cleanup') {
        if (!$canManage) alert('파일 관리 권한이 없습니다.');

        $result = nl_cleanup_storage(0);
        if (empty($result['ok'])) {
            alert('저장공간 자동 정리를 안전하게 완료하지 못했습니다. 잔여 임시파일과 파일 권한을 확인해 주세요.');
        }
        $message = $result['triggered']
            ? '용량 기준에 따라 자동 정리를 점검했습니다. 삭제된 활성 파일: '.(int)($result['deleted'] ?? 0).'개'
            : '현재 사용량은 자동 정리 기준 미만입니다.';
    }

    if ($action === 'purge_trash') {
        if (!$canManage) alert('파일 관리 권한이 없습니다.');

        $result = nl_purge_trash();
        $message = '잔여 임시 파일 '.(int)($result['deleted'] ?? 0).'개를 정리했습니다.';
        if (!empty($result['remaining'])) {
            $message .= ' 아직 제거되지 않은 파일 '.(int)$result['remaining'].'개가 남아 있습니다.';
        }
    }
}

$usage = nl_storage_bytes();
$percent = min(100, ($usage / NL_STORAGE_CLEANUP_TRIGGER) * 100);
$fileTable = nl_table('file');
$result = sql_query(
    "SELECT f.*,m.mb_name,m.mb_nick,p.role_code "
    ."FROM {$fileTable} f "
    ."LEFT JOIN {$g5['member_table']} m ON m.mb_id=f.owner_mb_id "
    ."LEFT JOIN ".nl_table('profile')." p ON p.mb_id=f.owner_mb_id "
    ."WHERE f.status='active' ORDER BY f.id DESC LIMIT 300",
    false
);
$rows = array();
if ($result) {
    while ($row = sql_fetch_array($result)) {
        if (nl_file_access_allowed($row)) $rows[] = $row;
    }
}
$trash = sql_fetch("SELECT COUNT(*) cnt FROM {$fileTable} WHERE status='trash_pending'", false);

nl_enqueue_assets();
$g5['title'] = '파일 관리';
include_once G5_PATH.'/head.php';
?>
<div class="nl-page">
  <div class="nl-page-head">
    <p class="nl-kicker">파일 운영</p>
    <h1>파일 관리</h1>
    <p>하나의 비공개 저장공간을 사용합니다. 9GB 기준에 도달하면 오래된 파일부터 정리해 7.5GB 수준으로 낮추는 운영 정책을 적용합니다.</p>
  </div>

  <?php if ($message) { ?><div class="nl-alert nl-alert--success nl-block-gap"><?php echo nl_h($message); ?></div><?php } ?>

  <section class="nl-panel">
    <div class="nl-section-head">
      <div><h2>저장공간</h2><p><?php echo nl_h(nl_human_bytes($usage)); ?> / 9 GB 정리 기준</p></div>
      <strong><?php echo number_format($percent, 1); ?>%</strong>
    </div>
    <progress class="nl-file-usage" max="100" value="<?php echo nl_h(number_format($percent, 2, '.', '')); ?>" aria-label="저장공간 사용률"><?php echo nl_h(number_format($percent, 1)); ?>%</progress>
    <p class="nl-note">실제 9GB 대량 삭제 시험은 이 패키지에서 자동 실행하지 않습니다. 운영 적용 전 별도 스테이징 검증이 필요합니다.</p>

    <?php if ((int)($trash['cnt'] ?? 0) > 0) { ?>
      <div class="nl-alert nl-alert--warning">삭제 후 디스크 정리가 끝나지 않은 파일 기록이 <?php echo (int)$trash['cnt']; ?>건 있습니다.</div>
    <?php } ?>

    <?php if ($canManage) { ?>
      <div class="nl-actions">
        <form method="post">
          <?php echo nl_csrf_field(); ?>
          <input type="hidden" name="action" value="cleanup">
          <button class="nl-btn nl-btn--small" type="submit">용량 기준 점검</button>
        </form>
        <form method="post">
          <?php echo nl_csrf_field(); ?>
          <input type="hidden" name="action" value="purge_trash">
          <button class="nl-btn nl-btn--small" type="submit">잔여 임시파일 정리</button>
        </form>
      </div>
    <?php } ?>
  </section>

  <?php if ($canManage) { ?>
    <details class="nl-panel">
      <summary><strong>파일 업로드</strong></summary>
      <form method="post" enctype="multipart/form-data" class="nl-form nl-form--nested">
        <?php echo nl_csrf_field(); ?>
        <input type="hidden" name="action" value="upload">

        <div class="nl-field">
          <label for="nl-file-label">표시 이름</label>
          <input id="nl-file-label" name="label" maxlength="255" placeholder="비워두면 원본 파일명 사용">
        </div>
        <div class="nl-field">
          <label for="nl-file-upload">파일 (최대 50MB)</label>
          <input id="nl-file-upload" type="file" name="file" required>
        </div>
        <div class="nl-field-row">
          <div class="nl-field">
            <label for="nl-file-audience">열람 대상</label>
            <select id="nl-file-audience" name="audience">
              <option value="leaders">승인된 리더</option>
              <option value="executives">임원</option>
            </select>
          </div>
          <div class="nl-field">
            <label for="nl-file-download">다운로드</label>
            <select id="nl-file-download" name="download_allowed">
              <option value="1">허용</option>
              <option value="0">다운로드 버튼 차단</option>
            </select>
          </div>
        </div>
        <div class="nl-alert">다운로드 차단은 별도 다운로드 동작을 막는 운영 기능입니다. 브라우저에 내용이 표시된 파일을 DRM처럼 완전히 저장 불가능하게 만들지는 않습니다.</div>
        <button class="nl-btn nl-btn--primary" type="submit">업로드</button>
      </form>
    </details>
  <?php } ?>

  <section class="nl-section">
    <div class="nl-section-head"><div><h2>파일</h2><p>내 권한으로 열람할 수 있는 파일만 표시됩니다.</p></div></div>

    <?php if (!$rows) { ?><div class="nl-empty">표시할 파일이 없습니다.</div><?php } ?>
    <?php foreach ($rows as $row) {
        $mime = $row['mime_type'] ?? '';
        $canPreview = strpos($mime, 'image/') === 0 || $mime === 'application/pdf';
    ?>
      <article class="nl-file-row">
        <div>
          <div class="nl-profile-line">
            <h3><?php echo nl_h($row['label']); ?></h3>
            <span class="nl-badge"><?php echo $row['audience'] === 'executives' ? '임원' : '리더'; ?></span>
            <?php if (!$row['download_allowed']) { ?><span class="nl-badge nl-badge--pending">다운로드 제한</span><?php } ?>
          </div>
          <p><?php echo nl_h($row['original_name']); ?> · <?php echo nl_h(nl_human_bytes($row['size_bytes'])); ?> · <?php echo nl_h($row['created_at']); ?> · 등록 <?php echo nl_h($row['mb_name'] ?: $row['mb_nick']); ?></p>
        </div>
        <div class="nl-actions">
          <?php if ($canPreview) { ?><a class="nl-btn nl-btn--small" target="_blank" rel="noopener" href="<?php echo nl_url('file-download.php?id='.(int)$row['id'].'&mode=view'); ?>">보기</a><?php } ?>
          <?php if ($row['download_allowed'] || nl_file_is_privileged($row)) { ?><a class="nl-btn nl-btn--small" href="<?php echo nl_url('file-download.php?id='.(int)$row['id'].'&mode=download'); ?>">다운로드</a><?php } ?>
          <?php if ($canManage) { ?>
            <form method="post">
              <?php echo nl_csrf_field(); ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
              <button class="nl-btn nl-btn--small nl-btn--danger" data-confirm="파일을 삭제할까요?" type="submit">삭제</button>
            </form>
          <?php } ?>
        </div>
      </article>
    <?php } ?>
  </section>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>

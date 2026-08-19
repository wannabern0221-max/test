<?php
include_once '../_common.php';

nl_require_approved();
if (!nl_can('system_manage') && $is_admin !== 'super') {
    alert('감사 로그 조회 권한이 없습니다.');
}

$action = substr(nl_get_str('action_filter'), 0, 100);
$where = '1=1';
if ($action) $where .= " AND action='".nl_sql_escape($action)."'";

$result = sql_query(
    "SELECT * FROM ".nl_table('audit_log')." WHERE {$where} ORDER BY id DESC LIMIT 500",
    false
);
$rows = array();
if ($result) {
    while ($row = sql_fetch_array($result)) $rows[] = $row;
}

$actionResult = sql_query("SELECT DISTINCT action FROM ".nl_table('audit_log')." ORDER BY action", false);
$actions = array();
if ($actionResult) {
    while ($row = sql_fetch_array($actionResult)) $actions[] = $row['action'];
}

nl_enqueue_assets();
$g5['title'] = '감사 로그';
include_once G5_PATH.'/head.php';
?>
<div class="nl-page">
  <div class="nl-page-head">
    <p class="nl-kicker">운영 기록</p>
    <h1>감사 로그</h1>
    <p>회원 상태·권한·파일·일정·익명 작성자 확인 등 중요한 운영 작업을 확인합니다.</p>
  </div>

  <div class="nl-admin-grid">
    <?php include __DIR__.'/_nav.php'; ?>
    <div>
      <form class="nl-panel nl-inline-form" method="get">
        <label class="nl-visually-hidden" for="nl-audit-action">작업 필터</label>
        <select id="nl-audit-action" name="action_filter">
          <option value="">전체 작업</option>
          <?php foreach ($actions as $item) { ?>
            <option value="<?php echo nl_h($item); ?>"<?php echo $action === $item ? ' selected' : ''; ?>><?php echo nl_h($item); ?></option>
          <?php } ?>
        </select>
        <button class="nl-btn nl-btn--small" type="submit">필터</button>
      </form>

      <div class="nl-table-wrap nl-table-wrap--spaced">
        <table class="nl-table">
          <thead>
            <tr><th>시각</th><th>작업자</th><th>작업</th><th>대상</th><th>상세</th><th>IP</th></tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row) { ?>
              <tr>
                <td><?php echo nl_h($row['created_at']); ?></td>
                <td><?php echo nl_h($row['actor_mb_id']); ?></td>
                <td><?php echo nl_h($row['action']); ?></td>
                <td><?php echo nl_h(trim($row['target_type'].' '.$row['target_id'])); ?></td>
                <td><small><?php echo nl_h(mb_strimwidth($row['detail_json'], 0, 180, '…', 'UTF-8')); ?></small></td>
                <td><small><?php echo nl_h($row['ip_address']); ?></small></td>
              </tr>
            <?php } ?>
            <?php if (!$rows) { ?><tr><td colspan="6">기록이 없습니다.</td></tr><?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>

<?php
include_once '../_common.php';

nl_require_permission('permission_grant');

$departments = nl_manageable_departments();
if (!$departments) alert('권한을 관리할 수 있는 소속 범위가 없습니다.');

$target = nl_get_str('mb_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    nl_verify_csrf();

    $target = nl_post_str('target_mb_id');
    if (!$target || !nl_can_manage_target($target)) {
        alert('관리 범위를 벗어난 회원입니다.');
    }

    $permissionTable = nl_table('permission');
    $targetEsc = nl_sql_escape($target);
    $actorEsc = nl_sql_escape($member['mb_id']);
    $now = nl_now();

    foreach (nl_permission_labels() as $code => $label) {
        if (!nl_can_grant_permission($code)) continue;

        $effect = isset($_POST['permission'][$code]) ? (string)$_POST['permission'][$code] : 'inherit';
        $codeEsc = nl_sql_escape($code);

        if ($effect === 'inherit') {
            sql_query("DELETE FROM {$permissionTable} WHERE mb_id='{$targetEsc}' AND permission_code='{$codeEsc}'", false);
            continue;
        }

        if (!in_array($effect, array('allow', 'deny'), true)) continue;

        $effectEsc = nl_sql_escape($effect);
        sql_query(
            "INSERT INTO {$permissionTable} (mb_id,permission_code,effect,granted_by,created_at,updated_at) "
            ."VALUES ('{$targetEsc}','{$codeEsc}','{$effectEsc}','{$actorEsc}','{$now}','{$now}') "
            ."ON DUPLICATE KEY UPDATE effect=VALUES(effect),granted_by=VALUES(granted_by),updated_at=VALUES(updated_at)",
            false
        );
    }

    nl_audit('permissions_update', 'member', $target);
    nl_notify(
        $target,
        'permission',
        '기능 권한이 변경되었습니다.',
        '리더 홈에서 현재 이용 가능한 기능을 확인해 주세요.',
        nl_url('dashboard.php')
    );
    goto_url(nl_url('admin/permissions.php?mb_id='.urlencode($target)));
}

$quotedDepartments = array();
foreach ($departments as $department) {
    $quotedDepartments[] = "'".nl_sql_escape($department)."'";
}

$memberResult = sql_query(
    "SELECT p.mb_id,p.department,p.role_code,p.approval_status,m.mb_name,m.mb_nick "
    ."FROM ".nl_table('profile')." p "
    ."LEFT JOIN {$g5['member_table']} m ON m.mb_id=p.mb_id "
    ."WHERE p.department IN (".implode(',', $quotedDepartments).") "
    ."AND p.approval_status='approved' "
    ."AND p.mb_id<>'".nl_sql_escape($member['mb_id'])."' "
    ."ORDER BY p.department,m.mb_name",
    false
);

$members = array();
if ($memberResult) {
    while ($row = sql_fetch_array($memberResult)) {
        if (nl_can_manage_target($row['mb_id'])) $members[] = $row;
    }
}

$targetRow = array();
$overrides = array();
if ($target && nl_can_manage_target($target)) {
    $targetRow = nl_profile($target);
    $overrideResult = sql_query(
        "SELECT permission_code,effect FROM ".nl_table('permission')." WHERE mb_id='".nl_sql_escape($target)."'",
        false
    );
    if ($overrideResult) {
        while ($row = sql_fetch_array($overrideResult)) {
            $overrides[$row['permission_code']] = $row['effect'];
        }
    }
}

nl_enqueue_assets();
$g5['title'] = '기능 권한 관리';
include_once G5_PATH.'/head.php';
?>
<div class="nl-page">
  <div class="nl-page-head">
    <p class="nl-kicker">권한 운영</p>
    <h1>기능 권한 관리</h1>
    <p>직책 기본 권한은 그대로 두고, 필요한 회원에게만 개인별 허용 또는 차단을 추가합니다.</p>
  </div>

  <div class="nl-admin-grid">
    <?php include __DIR__.'/_nav.php'; ?>
    <div>
      <form class="nl-panel nl-form" method="get">
        <div class="nl-field">
          <label for="nl-permission-member">회원 선택</label>
          <select id="nl-permission-member" name="mb_id" onchange="this.form.submit()">
            <option value="">선택하세요</option>
            <?php foreach ($members as $row) { ?>
              <option value="<?php echo nl_h($row['mb_id']); ?>"<?php echo $target === $row['mb_id'] ? ' selected' : ''; ?>>
                <?php echo nl_h(($row['mb_name'] ?: $row['mb_nick']).' · '.nl_department_label($row['department']).' · '.nl_role_label($row['role_code'])); ?>
              </option>
            <?php } ?>
          </select>
        </div>
      </form>

      <?php if ($targetRow) {
          $defaults = nl_default_permissions($targetRow['role_code']);
          $targetMember = get_member($target);
      ?>
        <form class="nl-panel nl-form" method="post">
          <?php echo nl_csrf_field(); ?>
          <input type="hidden" name="target_mb_id" value="<?php echo nl_h($target); ?>">
          <h2><?php echo nl_h(($targetMember['mb_name'] ?: $targetMember['mb_nick']).' · '.nl_role_label($targetRow['role_code'])); ?></h2>

          <div class="nl-table-wrap">
            <table class="nl-table">
              <thead>
                <tr><th>기능</th><th>직책 기본</th><th>개인 설정</th><th>최종</th></tr>
              </thead>
              <tbody>
                <?php foreach (nl_permission_labels() as $code => $label) {
                    $baseAllowed = in_array($code, $defaults, true);
                    $override = $overrides[$code] ?? 'inherit';
                    $effective = in_array($code, nl_effective_permissions($target), true);
                    $canGrant = nl_can_grant_permission($code);
                ?>
                  <tr>
                    <td><strong><?php echo nl_h($label); ?></strong><br><small><?php echo nl_h($code); ?></small></td>
                    <td><?php echo $baseAllowed ? '허용' : '없음'; ?></td>
                    <td>
                      <?php if ($canGrant) { ?>
                        <select name="permission[<?php echo nl_h($code); ?>]" aria-label="<?php echo nl_h($label); ?> 개인 설정">
                          <option value="inherit"<?php echo $override === 'inherit' ? ' selected' : ''; ?>>직책 기본</option>
                          <option value="allow"<?php echo $override === 'allow' ? ' selected' : ''; ?>>개별 허용</option>
                          <option value="deny"<?php echo $override === 'deny' ? ' selected' : ''; ?>>개별 차단</option>
                        </select>
                      <?php } else { ?>
                        <span class="nl-note">정책국장 전용 위임</span>
                      <?php } ?>
                    </td>
                    <td><?php echo $effective ? '<span class="nl-badge nl-badge--approved">허용</span>' : '<span class="nl-badge">차단</span>'; ?></td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          <button class="nl-btn nl-btn--primary" type="submit">권한 저장</button>
        </form>
      <?php } ?>
    </div>
  </div>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>

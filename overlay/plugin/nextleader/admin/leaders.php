<?php
include_once '../_common.php';

nl_require_approved();
if (!nl_can('member_approve') && !nl_can('role_manage')) {
    alert('회원 또는 직책 관리 권한이 없습니다.');
}

$departments = nl_manageable_departments();
if (!$departments) alert('관리 가능한 소속이 없습니다.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    nl_verify_csrf();

    $action = nl_post_str('action');
    $target = nl_post_str('target_mb_id');
    if (!$target || !nl_can_manage_target($target)) {
        alert('관리 범위를 벗어난 회원입니다.');
    }

    $targetProfile = nl_profile($target);
    $actorEsc = nl_sql_escape($member['mb_id']);
    $targetEsc = nl_sql_escape($target);
    $now = nl_now();

    if (in_array($action, array('approve', 'reject', 'suspend', 'restore'), true)) {
        if (!nl_can('member_approve')) alert('가입 승인 권한이 없습니다.');

        $statusMap = array(
            'approve' => 'approved',
            'reject' => 'rejected',
            'suspend' => 'suspended',
            'restore' => 'approved',
        );
        $status = $statusMap[$action];
        $note = substr(nl_post_str('approval_note'), 0, 255);
        $role = $targetProfile['role_code'] ?: ($targetProfile['requested_role'] ?: 'leader');

        if ($action === 'approve' && nl_can('role_manage')) {
            $candidate = nl_post_str('role_code', $role);
            if (
                in_array($candidate, nl_allowed_roles_for_actor(), true)
                && nl_role_department_valid($candidate, $targetProfile['department'])
            ) {
                $role = $candidate;
            }
        }

        $statusEsc = nl_sql_escape($status);
        $noteEsc = nl_sql_escape($note);
        $roleEsc = nl_sql_escape($role);
        $approvedAt = $status === 'approved' ? "'{$now}'" : 'approved_at';

        sql_query(
            "UPDATE ".nl_table('profile')." SET "
            ."approval_status='{$statusEsc}', role_code='{$roleEsc}', approval_note='{$noteEsc}', "
            ."approved_by='{$actorEsc}', approved_at={$approvedAt}, updated_at='{$now}' "
            ."WHERE mb_id='{$targetEsc}'",
            false
        );

        nl_sync_member_level($target);
        nl_notify(
            $target,
            'member',
            $status === 'approved'
                ? '리더 이용이 승인되었습니다.'
                : ($status === 'suspended' ? '리더 이용 권한이 정지되었습니다.' : '가입 승인 상태가 변경되었습니다.'),
            $note,
            nl_url('profile.php')
        );
        nl_audit('member_status_change', 'member', $target, array('status' => $status, 'role' => $role, 'note' => $note));
        goto_url(nl_url('admin/leaders.php'));
    }

    if ($action === 'role') {
        if (!nl_can('role_manage')) alert('직책 관리 권한이 없습니다.');

        $role = nl_post_str('role_code');
        if (!in_array($role, nl_allowed_roles_for_actor(), true)) alert('부여할 수 없는 직책입니다.');
        if (!nl_role_department_valid($role, $targetProfile['department'])) alert('해당 소속과 맞지 않는 직책입니다.');

        $roleEsc = nl_sql_escape($role);
        sql_query(
            "UPDATE ".nl_table('profile')." SET role_code='{$roleEsc}', updated_at='{$now}' WHERE mb_id='{$targetEsc}'",
            false
        );

        nl_sync_member_level($target);
        nl_notify($target, 'role', '직책이 변경되었습니다.', nl_role_label($role), nl_url('profile.php'));
        nl_audit('member_role_change', 'member', $target, array('role' => $role));
        goto_url(nl_url('admin/leaders.php'));
    }
}

$quotedDepartments = array();
foreach ($departments as $department) {
    $quotedDepartments[] = "'".nl_sql_escape($department)."'";
}

$sql = "SELECT p.*,m.mb_name,m.mb_nick,m.mb_email,m.mb_level "
    ."FROM ".nl_table('profile')." p "
    ."LEFT JOIN {$g5['member_table']} m ON m.mb_id=p.mb_id "
    ."WHERE p.department IN (".implode(',', $quotedDepartments).") "
    ."ORDER BY FIELD(p.approval_status,'pending','approved','suspended','rejected'), p.created_at DESC "
    ."LIMIT 500";
$result = sql_query($sql, false);
$rows = array();
if ($result) {
    while ($row = sql_fetch_array($result)) $rows[] = $row;
}

nl_enqueue_assets();
$g5['title'] = '회원·직책 관리';
include_once G5_PATH.'/head.php';
?>
<div class="nl-page">
  <div class="nl-page-head">
    <p class="nl-kicker">회원 운영</p>
    <h1>회원·직책 관리</h1>
    <p>관리 가능한 소속 범위 안에서만 승인과 직책 변경을 수행합니다.</p>
  </div>

  <div class="nl-admin-grid">
    <?php include __DIR__.'/_nav.php'; ?>
    <div>
      <div class="nl-table-wrap">
        <table class="nl-table">
          <thead>
            <tr><th>상태</th><th>회원</th><th>소속/신청</th><th>현재 직책</th><th>관리</th></tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row) { ?>
              <tr>
                <td><?php echo nl_status_badge($row['approval_status']); ?></td>
                <td>
                  <strong><?php echo nl_h($row['mb_name'] ?: $row['mb_nick'] ?: $row['mb_id']); ?></strong><br>
                  <small><?php echo nl_h($row['mb_id']); ?> · <?php echo nl_h($row['mb_email']); ?></small>
                  <?php if ($row['school']) { ?><br><small><?php echo nl_h($row['school'].' '.$row['cohort']); ?></small><?php } ?>
                </td>
                <td>
                  <?php echo nl_h(nl_department_label($row['department'])); ?><br>
                  <small>신청: <?php echo nl_h(nl_role_label($row['requested_role'])); ?></small>
                </td>
                <td>
                  <?php echo nl_h(nl_role_label($row['role_code'])); ?><br>
                  <small>G5 level <?php echo (int)$row['mb_level']; ?></small>
                </td>
                <td>
                  <?php if ($row['mb_id'] === $member['mb_id']) { ?>
                    <span class="nl-note">본인 계정</span>
                  <?php } else { ?>
                    <?php if (nl_can('member_approve')) { ?>
                      <form method="post" class="nl-inline-form">
                        <?php echo nl_csrf_field(); ?>
                        <input type="hidden" name="target_mb_id" value="<?php echo nl_h($row['mb_id']); ?>">

                        <?php if ($row['approval_status'] === 'pending' || $row['approval_status'] === 'rejected') { ?>
                          <input type="hidden" name="action" value="approve">
                          <?php if (nl_can('role_manage')) { ?>
                            <label class="nl-visually-hidden" for="nl-approve-role-<?php echo nl_h($row['mb_id']); ?>">승인 직책</label>
                            <select id="nl-approve-role-<?php echo nl_h($row['mb_id']); ?>" name="role_code">
                              <?php foreach (nl_allowed_roles_for_actor() as $code) {
                                  if (!nl_role_department_valid($code, $row['department'])) continue;
                              ?>
                                <option value="<?php echo nl_h($code); ?>"<?php echo ($row['requested_role'] === $code || $row['role_code'] === $code) ? ' selected' : ''; ?>><?php echo nl_h(nl_role_label($code)); ?></option>
                              <?php } ?>
                            </select>
                          <?php } ?>
                          <button class="nl-btn nl-btn--small nl-btn--primary" type="submit">승인</button>
                        <?php } elseif ($row['approval_status'] === 'approved') { ?>
                          <input type="hidden" name="action" value="suspend">
                          <button class="nl-btn nl-btn--small nl-btn--danger" data-confirm="이 회원의 리더 이용을 정지할까요?" type="submit">정지</button>
                        <?php } elseif ($row['approval_status'] === 'suspended') { ?>
                          <input type="hidden" name="action" value="restore">
                          <button class="nl-btn nl-btn--small" type="submit">복원</button>
                        <?php } ?>
                      </form>

                      <?php if ($row['approval_status'] === 'pending') { ?>
                        <form method="post" class="nl-inline-form nl-inline-form--stacked">
                          <?php echo nl_csrf_field(); ?>
                          <input type="hidden" name="action" value="reject">
                          <input type="hidden" name="target_mb_id" value="<?php echo nl_h($row['mb_id']); ?>">
                          <label class="nl-visually-hidden" for="nl-reject-note-<?php echo nl_h($row['mb_id']); ?>">반려 사유</label>
                          <input id="nl-reject-note-<?php echo nl_h($row['mb_id']); ?>" type="text" name="approval_note" placeholder="반려 사유">
                          <button class="nl-btn nl-btn--small" type="submit">반려</button>
                        </form>
                      <?php } ?>
                    <?php } ?>

                    <?php if (nl_can('role_manage') && $row['approval_status'] === 'approved') { ?>
                      <form method="post" class="nl-inline-form nl-inline-form--stacked">
                        <?php echo nl_csrf_field(); ?>
                        <input type="hidden" name="action" value="role">
                        <input type="hidden" name="target_mb_id" value="<?php echo nl_h($row['mb_id']); ?>">
                        <label class="nl-visually-hidden" for="nl-role-<?php echo nl_h($row['mb_id']); ?>">직책 변경</label>
                        <select id="nl-role-<?php echo nl_h($row['mb_id']); ?>" name="role_code">
                          <?php foreach (nl_allowed_roles_for_actor() as $code) {
                              if (!nl_role_department_valid($code, $row['department'])) continue;
                          ?>
                            <option value="<?php echo nl_h($code); ?>"<?php echo $row['role_code'] === $code ? ' selected' : ''; ?>><?php echo nl_h(nl_role_label($code)); ?></option>
                          <?php } ?>
                        </select>
                        <button class="nl-btn nl-btn--small" type="submit">직책 변경</button>
                      </form>
                    <?php } ?>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>

            <?php if (!$rows) { ?>
              <tr><td colspan="5">관리 범위에 회원이 없습니다.</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>

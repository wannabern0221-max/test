<?php
include_once './_common.php';
nl_require_member();
if (!nl_installed()) alert('NEXT LEADER 확장 기능이 아직 설치되지 않았습니다.', nl_url('install.php'));
$profile = nl_profile();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    nl_verify_csrf();
    $school = substr(nl_post_str('school'),0,120);
    $cohort = substr(nl_post_str('cohort'),0,50);
    $dept = nl_post_str('department','policy_office');
    if (!array_key_exists($dept, nl_department_labels())) $dept = 'policy_office';
    $role = nl_post_str('requested_role','leader');
    if (!array_key_exists($role, nl_role_labels())) $role = 'leader';
    $id = nl_sql_escape($member['mb_id']);
    $schoolEsc = nl_sql_escape($school); $cohortEsc = nl_sql_escape($cohort); $deptEsc = nl_sql_escape($dept); $roleEsc = nl_sql_escape($role); $now = nl_now();
    // 승인 후에는 사용자가 자신의 소속/직책을 임의 변경하지 못하게 하고, 기본 정보만 수정합니다.
    if (($profile['approval_status'] ?? '') === 'approved') {
        sql_query("UPDATE ".nl_table('profile')." SET school='{$schoolEsc}', cohort='{$cohortEsc}', updated_at='{$now}' WHERE mb_id='{$id}'", false);
    } else {
        sql_query("UPDATE ".nl_table('profile')." SET school='{$schoolEsc}', cohort='{$cohortEsc}', department='{$deptEsc}', requested_role='{$roleEsc}', updated_at='{$now}' WHERE mb_id='{$id}'", false);
    }
    nl_audit('profile_update','member',$member['mb_id'],array('approval_status'=>$profile['approval_status'] ?? 'pending'));
    goto_url(nl_url('profile.php'));
}
$profile = nl_profile();
nl_enqueue_assets();
$g5['title'] = '리더 승인 정보';
include_once G5_PATH.'/head.php';
?>
<div class="nl-page nl-narrow">
  <div class="nl-page-head"><p class="nl-kicker">가입 · 승인</p><h1>리더 승인 정보</h1><p>회원가입 후 정책국 소속 정보를 제출하면 관리자가 확인하고 승인합니다.</p></div>
  <div class="nl-panel">
    <div class="nl-profile-line"><strong><?php echo nl_h($member['mb_name'] ?: $member['mb_nick']); ?></strong><?php echo nl_status_badge($profile['approval_status'] ?? 'pending'); ?><?php if (($profile['approval_status'] ?? '') === 'approved') { ?><span><?php echo nl_h(nl_department_label($profile['department'])); ?> · <?php echo nl_h(nl_role_label($profile['role_code'])); ?></span><?php } ?></div>
    <?php if (($profile['approval_status'] ?? '') === 'pending') { ?><p class="nl-note">승인 대기 중입니다. 승인 전에는 리더 전용 기능과 콘텐츠 작성 권한이 열리지 않습니다.</p><?php } ?>
    <?php if (($profile['approval_status'] ?? '') === 'rejected') { ?><div class="nl-alert nl-alert--danger">반려 사유: <?php echo nl_h($profile['approval_note'] ?? '관리자에게 문의해 주세요.'); ?></div><?php } ?>
    <?php if (($profile['approval_status'] ?? '') === 'suspended') { ?><div class="nl-alert nl-alert--danger">현재 리더 이용 권한이 정지되어 있습니다.</div><?php } ?>
  </div>
  <form class="nl-panel nl-form" method="post">
    <?php echo nl_csrf_field(); ?>
    <div class="nl-field"><label for="school">학교</label><input id="school" name="school" type="text" maxlength="120" value="<?php echo nl_h($profile['school'] ?? ''); ?>" placeholder="학교명을 입력하세요"></div>
    <div class="nl-field"><label for="cohort">기수/학년 등</label><input id="cohort" name="cohort" type="text" maxlength="50" value="<?php echo nl_h($profile['cohort'] ?? ''); ?>" placeholder="예: 3학년 / 12기"></div>
    <?php if (($profile['approval_status'] ?? '') !== 'approved') { ?>
      <div class="nl-field-row">
        <div class="nl-field"><label for="department">신청 소속</label><select id="department" name="department"><?php foreach (nl_department_labels() as $c=>$l) { ?><option value="<?php echo nl_h($c); ?>"<?php echo ($profile['department'] ?? '')===$c?' selected':''; ?>><?php echo nl_h($l); ?></option><?php } ?></select></div>
        <div class="nl-field"><label for="requested_role">신청 직책</label><select id="requested_role" name="requested_role"><?php foreach (nl_role_labels() as $c=>$l) { ?><option value="<?php echo nl_h($c); ?>"<?php echo ($profile['requested_role'] ?? 'leader')===$c?' selected':''; ?>><?php echo nl_h($l); ?></option><?php } ?></select></div>
      </div>
    <?php } ?>
    <div class="nl-actions"><button class="nl-btn nl-btn--primary" type="submit">정보 저장</button><?php if (($profile['approval_status'] ?? '') === 'approved') { ?><a class="nl-btn" href="<?php echo nl_url('dashboard.php'); ?>">리더 홈으로</a><?php } ?></div>
  </form>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>

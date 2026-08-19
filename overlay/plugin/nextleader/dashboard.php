<?php
include_once './_common.php';
nl_require_approved();
nl_enqueue_assets();

$profile = nl_profile();
$memoCount = nl_unread_memo_count();
$notiCount = nl_unread_notification_count();
$pendingContent = 0;

if (nl_can('content_approve')) {
    foreach (nl_allowed_boards() as $boardId) {
        $table = nl_board_table($boardId);
        if (!$table || !nl_table_exists($table)) continue;
        $row = sql_fetch("SELECT COUNT(*) cnt FROM {$table} WHERE wr_is_comment=0 AND wr_10='pending'", false);
        $pendingContent += (int)($row['cnt'] ?? 0);
    }
}

$communicationItems = array(
    array('리더 찾기 · 쪽지', '승인된 리더를 찾아 1:1 쪽지를 보냅니다.', nl_url('members.php')),
    array('익명 소통방', '작성자 정보는 일반 리더에게 노출되지 않습니다.', nl_url('anonymous.php')),
);

$scheduleItems = array(
    array('정책국 일정', '내 소속 범위의 내부 일정과 공개 일정을 확인합니다.', nl_url('schedule.php?mode=leader')),
    array('불가일 관리', '본인의 불가일을 등록하고 일정 조율에 반영합니다.', nl_url('unavailable.php')),
);

$contentItems = array(
    array('정책 콘텐츠 작성', '초안을 작성하고 게시 승인을 요청합니다.', G5_BBS_URL.'/write.php?bo_table='.rawurlencode(NL_BOARD_POLICY)),
    array('정책 퀴즈', '정책 핵심 내용을 문제로 점검합니다.', nl_url('quiz.php')),
    array('사업자료', '정책국 사업 관련 게시자료를 확인합니다.', nl_board_url(NL_BOARD_ACTIVITY)),
);
if (nl_can('content_approve')) {
    array_unshift($contentItems, array('콘텐츠 승인', '승인 대기 글을 검토하고 게시 또는 반려합니다.', nl_url('content-manager.php')));
}

$operationItems = array();
if (nl_can('file_manage')) {
    $operationItems[] = array('파일 관리', '비공개 파일 저장공간과 열람 범위를 관리합니다.', nl_url('files.php'));
}
if (nl_can('member_approve') || nl_can('role_manage')) {
    $operationItems[] = array('회원 · 직책 관리', '관리 범위 안에서 가입 승인과 직책을 관리합니다.', nl_url('admin/leaders.php'));
}
if (nl_can('permission_grant')) {
    $operationItems[] = array('기능 권한 관리', '직책 기본 권한 위에 개인별 허용·차단을 설정합니다.', nl_url('admin/permissions.php'));
}
if (nl_can('news_manage')) {
    $operationItems[] = array('뉴스 관리', '정책 뉴스 노출 상태와 직접 등록 자료를 관리합니다.', nl_url('news-manage.php'));
}
if (nl_can('system_manage') || $is_admin === 'super') {
    $operationItems[] = array('감사 로그', '회원·권한·파일·익명 확인 등 중요 작업 기록을 조회합니다.', nl_url('admin/audit.php'));
}
if ($is_admin === 'super') {
    $operationItems[] = array('시스템 점검', '테이블·게시판·테마·저장소 상태를 읽기 방식으로 확인합니다.', nl_url('system-check.php'));
}

$g5['title'] = '리더 홈';
include_once G5_PATH.'/head.php';

function nl_dashboard_group($title, $description, $items) {
    if (!$items) return;
    ?>
    <section class="nl-work-group">
      <header class="nl-work-group__head">
        <h2><?php echo nl_h($title); ?></h2>
        <p><?php echo nl_h($description); ?></p>
      </header>
      <div class="nl-work-list">
        <?php foreach ($items as $item) { ?>
          <a class="nl-work-row" href="<?php echo nl_h($item[2]); ?>">
            <span><strong><?php echo nl_h($item[0]); ?></strong><span><?php echo nl_h($item[1]); ?></span></span>
            <b aria-hidden="true">→</b>
          </a>
        <?php } ?>
      </div>
    </section>
    <?php
}
?>
<div class="nl-page">
  <div class="nl-dashboard-head nl-page-head">
    <div>
      <p class="nl-kicker">내 업무</p>
      <h1>리더 홈</h1>
      <div class="nl-profile-line">
        <strong><?php echo nl_h($member['mb_name'] ?: $member['mb_nick']); ?></strong>
        <span><?php echo nl_h(nl_department_label($profile['department'])); ?> · <?php echo nl_h(nl_role_label($profile['role_code'])); ?></span>
      </div>
    </div>
    <div class="nl-actions">
      <a class="nl-btn" href="<?php echo G5_BBS_URL; ?>/memo.php">쪽지함</a>
      <a class="nl-btn" href="<?php echo nl_url('notifications.php'); ?>">알림</a>
    </div>
  </div>

  <div class="nl-dashboard-summary" aria-label="확인할 항목">
    <a href="<?php echo G5_BBS_URL; ?>/memo.php"><strong><?php echo (int)$memoCount; ?></strong><span>읽지 않은 쪽지</span></a>
    <a href="<?php echo nl_url('notifications.php'); ?>"><strong><?php echo (int)$notiCount; ?></strong><span>읽지 않은 시스템 알림</span></a>
    <?php if (nl_can('content_approve')) { ?>
      <a href="<?php echo nl_url('content-manager.php'); ?>"><strong><?php echo (int)$pendingContent; ?></strong><span>승인 대기 콘텐츠</span></a>
    <?php } else { ?>
      <div><strong>—</strong><span>콘텐츠 승인 집계는 승인 권한자에게만 표시</span></div>
    <?php } ?>
  </div>

  <div class="nl-workspace">
    <?php nl_dashboard_group('소통', '리더 간 연락과 내부 의견 공유', $communicationItems); ?>
    <?php nl_dashboard_group('일정', '회의·행사 일정과 개인 불가일', $scheduleItems); ?>
    <?php nl_dashboard_group('콘텐츠', '정책 콘텐츠 작성·검수와 사업자료', $contentItems); ?>
    <?php nl_dashboard_group('운영', '현재 권한으로 사용할 수 있는 관리 기능', $operationItems); ?>
  </div>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>

<?php
include_once './_common.php';
if ($is_admin !== 'super') alert('최고관리자만 설치할 수 있습니다.');
nl_enqueue_assets();
$g5['title'] = 'NEXT LEADER 설치';
include_once G5_PATH.'/head.php';
$already = nl_installed();
?>
<div class="nl-page nl-narrow">
  <div class="nl-page-head">
    <p class="nl-kicker">설치 도구</p>
    <h1>확장 패키지 설치</h1>
    <p>그누보드 코어를 수정하지 않고 전용 테이블, 게시판, 테마와 운영 기능을 설치합니다.</p>
  </div>
  <?php if ($already) { ?>
    <div class="nl-alert nl-alert--warning"><strong>이미 설치된 흔적이 있습니다.</strong> 다시 실행해도 CREATE IF NOT EXISTS와 UPSERT 위주로 처리하지만, 운영 DB에서는 먼저 백업하세요.</div>
    <div class="nl-actions nl-actions--bottom"><a class="nl-btn" href="<?php echo nl_url('system-check.php'); ?>">현재 설치 상태 점검</a></div>
  <?php } ?>
  <form method="post" action="install_action.php" class="nl-panel nl-form">
    <?php echo nl_csrf_field(); ?>
    <div class="nl-field">
      <label for="installer_role">현재 최고관리자의 NEXT LEADER 직책</label>
      <select id="installer_role" name="installer_role">
        <?php foreach (nl_role_labels() as $code=>$label) { ?>
          <option value="<?php echo nl_h($code); ?>"<?php echo $code==='policy_director'?' selected':''; ?>><?php echo nl_h($label); ?></option>
        <?php } ?>
      </select>
    </div>
    <div class="nl-field">
      <label for="installer_department">소속</label>
      <select id="installer_department" name="installer_department">
        <?php foreach (nl_department_labels() as $code=>$label) { ?>
          <option value="<?php echo nl_h($code); ?>"><?php echo nl_h($label); ?></option>
        <?php } ?>
      </select>
    </div>
    <label class="nl-check"><input type="checkbox" name="set_theme" value="1" checked> NEXT LEADER 테마를 기본 테마로 적용</label>
    <label class="nl-check"><input type="checkbox" name="seed_data" value="1" checked> 제공된 정책단어·퀴즈·뉴스 데이터를 초기 데이터로 등록</label>
    <div class="nl-alert"><strong>중요:</strong> 이 설치기는 기존 그누보드 회원/게시글을 삭제하지 않습니다. 단, 실제 운영 DB에는 적용 전 백업을 권장합니다.</div>
    <button class="nl-btn nl-btn--primary" type="submit">설치 실행</button>
  </form>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>

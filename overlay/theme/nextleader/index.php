<?php
if (!defined('_GNUBOARD_')) exit;
if (!defined('_INDEX_')) define('_INDEX_', true);

$g5['title'] = 'NEXT LEADER';
include_once G5_THEME_PATH.'/head.php';

$notice = function_exists('nl_latest_posts') ? nl_latest_posts(NL_BOARD_NOTICE, 5) : array();
$policy = function_exists('nl_latest_posts') ? nl_latest_posts(NL_BOARD_POLICY, 4) : array();
$cards = function_exists('nl_latest_posts') ? nl_latest_posts(NL_BOARD_CARDS, 3) : array();
$nextSchedule = array();
if (function_exists('nl_table_exists') && nl_table_exists(nl_table('schedule'))) {
    $now = nl_sql_escape(nl_now());
    $nextSchedule = sql_fetch(
        "SELECT title, starts_at, location FROM ".nl_table('schedule')." WHERE visibility='public' AND starts_at>='{$now}' ORDER BY starts_at ASC LIMIT 1",
        false
    );
    if (!is_array($nextSchedule)) $nextSchedule = array();
}
?>
<section class="nl-home-intro">
  <div class="nl-home-intro__copy">
    <p class="nl-kicker">대한간호학생회 부산 정책국</p>
    <h1>간호정책을 이해하고,<br>학생의 목소리를 연결합니다.</h1>
    <p>정책국이 정리한 정책 콘텐츠, 카드뉴스, 주요 일정과 참고 자료를 한곳에서 확인할 수 있습니다.</p>
    <div class="nl-actions">
      <a class="nl-btn nl-btn--primary" href="<?php echo nl_public_board_url(NL_BOARD_POLICY); ?>">정책 콘텐츠</a>
      <a class="nl-btn" href="<?php echo nl_url('about.php'); ?>">정책국 소개</a>
    </div>
  </div>

  <aside class="nl-home-next" aria-label="가까운 정책국 일정">
    <div class="nl-home-next__brand" aria-hidden="true">
      <img src="<?php echo G5_THEME_URL; ?>/img/emblem.png" alt="" width="46" height="46">
      <div><strong>NEXT LEADER</strong><span>부산 정책국</span></div>
    </div>
    <h2>가까운 공개 일정</h2>
    <?php if (!empty($nextSchedule['starts_at'])) { ?>
      <a class="nl-home-next__event" href="<?php echo nl_url('schedule.php'); ?>">
        <time datetime="<?php echo nl_h($nextSchedule['starts_at']); ?>"><?php echo nl_h(date('Y.m.d H:i', strtotime($nextSchedule['starts_at']))); ?></time>
        <strong><?php echo nl_h($nextSchedule['title']); ?></strong>
        <?php if (!empty($nextSchedule['location'])) { ?><span><?php echo nl_h($nextSchedule['location']); ?></span><?php } ?>
      </a>
    <?php } else { ?>
      <div class="nl-home-next__empty">현재 공개된 예정 일정이 없습니다.</div>
    <?php } ?>
  </aside>
</section>

<section class="nl-home-section">
  <div class="nl-section-head">
    <div><h2>새로운 소식</h2><p>정책국 공지와 주요 안내입니다.</p></div>
    <a href="<?php echo nl_public_board_url(NL_BOARD_NOTICE); ?>">공지 전체보기</a>
  </div>
  <div class="nl-news-list">
    <?php if (!$notice) { ?><div class="nl-empty">등록된 공지가 없습니다.</div><?php } ?>
    <?php foreach ($notice as $row) { ?>
      <a class="nl-news-row" href="<?php echo nl_public_article_url(NL_BOARD_NOTICE, (int)$row['wr_id']); ?>">
        <strong><?php echo nl_h($row['wr_subject']); ?></strong>
        <span><?php echo nl_h(substr($row['wr_datetime'], 0, 10)); ?></span>
      </a>
    <?php } ?>
  </div>
</section>

<section class="nl-home-section nl-home-grid">
  <div>
    <div class="nl-section-head">
      <div><h2>정책 콘텐츠</h2><p>간호와 보건의료 정책을 학생의 시선으로 정리합니다.</p></div>
      <a href="<?php echo nl_public_board_url(NL_BOARD_POLICY); ?>">전체보기</a>
    </div>
    <div class="nl-simple-list">
      <?php if (!$policy) { ?><div class="nl-empty">첫 정책 콘텐츠를 준비하고 있습니다.</div><?php } ?>
      <?php foreach ($policy as $row) { ?>
        <a href="<?php echo nl_public_article_url(NL_BOARD_POLICY, (int)$row['wr_id']); ?>">
          <span><?php echo nl_h($row['wr_subject']); ?></span>
          <small><?php echo nl_h(substr($row['wr_datetime'], 0, 10)); ?></small>
        </a>
      <?php } ?>
    </div>
  </div>

  <aside class="nl-home-tools">
    <h2>정책을 더 쉽게</h2>
    <a href="<?php echo nl_url('glossary.php'); ?>"><strong>정책단어</strong><span>낯선 정책 용어를 빠르게 확인합니다.</span></a>
    <a href="<?php echo nl_url('quiz.php'); ?>"><strong>정책 퀴즈</strong><span>핵심 내용을 문제로 점검합니다.</span></a>
    <a href="<?php echo nl_url('schedule.php'); ?>"><strong>정책 일정</strong><span>공개 일정을 한눈에 확인합니다.</span></a>
  </aside>
</section>

<section class="nl-home-section">
  <div class="nl-section-head">
    <div><h2>카드뉴스</h2><p>정책 이슈를 짧고 시각적으로 정리한 자료입니다.</p></div>
    <a href="<?php echo nl_public_board_url(NL_BOARD_CARDS); ?>">전체보기</a>
  </div>
  <div class="nl-card-strip">
    <?php if (!$cards) { ?><div class="nl-empty">등록된 카드뉴스가 없습니다.</div><?php } ?>
    <?php foreach ($cards as $row) { ?>
      <a href="<?php echo nl_public_article_url(NL_BOARD_CARDS, (int)$row['wr_id']); ?>">
        <span class="nl-card-strip__thumb">CARD NEWS</span>
        <strong><?php echo nl_h($row['wr_subject']); ?></strong>
        <small><?php echo nl_h(substr($row['wr_datetime'], 0, 10)); ?></small>
      </a>
    <?php } ?>
  </div>
</section>
<?php include_once G5_THEME_PATH.'/tail.php'; ?>

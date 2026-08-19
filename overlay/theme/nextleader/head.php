<?php
if (!defined('_GNUBOARD_')) exit;

if (function_exists('nl_enqueue_assets')) nl_enqueue_assets();
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_URL.'/css/nextleader.css?v=1.1.0">', 1);
add_javascript('<script src="'.G5_THEME_URL.'/js/nextleader.js?v=1.1.0"></script>', 10);
include_once G5_PATH.'/head.sub.php';

$nl_profile = ($is_member && function_exists('nl_profile') && nl_installed()) ? nl_profile() : array();
$nl_approved = $is_member && function_exists('nl_is_approved') && nl_is_approved();
$nl_memo_count = $nl_approved ? nl_unread_memo_count() : 0;
$nl_noti_count = $nl_approved ? nl_unread_notification_count() : 0;
$nl_request_uri = (string)($_SERVER['REQUEST_URI'] ?? '');
$nl_nav_current = function ($needle) use ($nl_request_uri) {
    return strpos($nl_request_uri, $needle) !== false ? ' aria-current="page"' : '';
};
?>
<a class="nl-skip" href="#nl-main">본문 바로가기</a>
<header class="nl-site-header">
  <div class="nl-header-inner">
    <a class="nl-brand" href="<?php echo G5_URL; ?>/" aria-label="NEXT LEADER 홈">
      <img src="<?php echo G5_THEME_URL; ?>/img/emblem.png" alt="" width="42" height="42">
      <span><strong>NEXT LEADER</strong><small>대한간호학생회 부산 정책국</small></span>
    </a>

    <button class="nl-nav-toggle" type="button" aria-expanded="false" aria-controls="nl-nav">메뉴</button>

    <nav id="nl-nav" class="nl-nav" aria-label="주요 메뉴">
      <a href="<?php echo function_exists('nl_url') ? nl_url('about.php') : G5_URL.'/'; ?>"<?php echo $nl_nav_current('about.php'); ?>>소개</a>
      <a href="<?php echo function_exists('nl_public_board_url') ? nl_public_board_url(NL_BOARD_NOTICE) : G5_URL.'/'; ?>"<?php echo $nl_nav_current('bo='.NL_BOARD_NOTICE); ?>>공지</a>
      <a href="<?php echo function_exists('nl_public_board_url') ? nl_public_board_url(NL_BOARD_CARDS) : G5_URL.'/'; ?>"<?php echo $nl_nav_current('bo='.NL_BOARD_CARDS); ?>>카드뉴스</a>
      <a href="<?php echo function_exists('nl_public_board_url') ? nl_public_board_url(NL_BOARD_POLICY) : G5_URL.'/'; ?>"<?php echo $nl_nav_current('bo='.NL_BOARD_POLICY); ?>>정책</a>
      <a href="<?php echo function_exists('nl_url') ? nl_url('glossary.php') : G5_URL.'/'; ?>"<?php echo $nl_nav_current('glossary.php'); ?>>정책단어</a>
      <a href="<?php echo function_exists('nl_url') ? nl_url('news.php') : G5_URL.'/'; ?>"<?php echo $nl_nav_current('news.php'); ?>>뉴스</a>
      <a href="<?php echo function_exists('nl_url') ? nl_url('schedule.php') : G5_URL.'/'; ?>"<?php echo $nl_nav_current('schedule.php'); ?>>일정</a>
    </nav>

    <div class="nl-account">
      <?php if ($is_member) { ?>
        <?php if ($nl_approved) { ?>
          <a class="nl-account-link" href="<?php echo nl_url('dashboard.php'); ?>">리더 홈</a>
          <a class="nl-icon-link" href="<?php echo G5_BBS_URL; ?>/memo.php" aria-label="쪽지">쪽지<?php if ($nl_memo_count) echo '<b>'.(int)$nl_memo_count.'</b>'; ?></a>
          <a class="nl-icon-link" href="<?php echo nl_url('notifications.php'); ?>" aria-label="알림">알림<?php if ($nl_noti_count) echo '<b>'.(int)$nl_noti_count.'</b>'; ?></a>
        <?php } else { ?>
          <a class="nl-account-link" href="<?php echo nl_url('profile.php'); ?>">승인 정보</a>
        <?php } ?>
        <a class="nl-account-link nl-muted" href="<?php echo G5_BBS_URL; ?>/logout.php">로그아웃</a>
      <?php } else { ?>
        <a class="nl-account-link" href="<?php echo G5_BBS_URL; ?>/login.php">로그인</a>
        <a class="nl-btn nl-btn--small" href="<?php echo G5_BBS_URL; ?>/register.php">회원가입</a>
      <?php } ?>
    </div>
  </div>
</header>
<main id="nl-main" class="nl-site-main">

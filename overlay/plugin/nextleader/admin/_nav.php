<?php if (!defined('_GNUBOARD_')) exit; ?>
<nav class="nl-admin-nav" aria-label="NEXT LEADER 관리">
  <?php if (nl_can('member_approve') || nl_can('role_manage')) { ?><a href="<?php echo nl_url('admin/leaders.php'); ?>">회원·직책</a><?php } ?>
  <?php if (nl_can('permission_grant')) { ?><a href="<?php echo nl_url('admin/permissions.php'); ?>">기능 권한</a><?php } ?>
  <?php if (nl_can('content_approve')) { ?><a href="<?php echo nl_url('content-manager.php'); ?>">콘텐츠 승인</a><?php } ?>
  <?php if (nl_can('news_manage')) { ?><a href="<?php echo nl_url('news-manage.php'); ?>">뉴스</a><?php } ?>
  <?php if (nl_can('file_manage')) { ?><a href="<?php echo nl_url('files.php'); ?>">파일</a><?php } ?>
  <?php if (nl_can('system_manage') || $is_admin === 'super') { ?><a href="<?php echo nl_url('admin/audit.php'); ?>">감사 로그</a><?php } ?>
  <a href="<?php echo nl_url('dashboard.php'); ?>">리더 홈</a>
</nav>

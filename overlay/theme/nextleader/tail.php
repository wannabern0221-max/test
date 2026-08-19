<?php if (!defined('_GNUBOARD_')) exit; ?>
</main>
<footer class="nl-site-footer">
  <div class="nl-footer-inner">
    <div><strong>NEXT LEADER</strong><p>대한간호학생회 부산 정책국</p></div>
    <div class="nl-footer-links">
      <a href="<?php echo nl_url('about.php'); ?>">정책국 소개</a>
      <?php if ($is_member && nl_is_approved()) { ?><a href="<?php echo nl_url('dashboard.php'); ?>">리더 홈</a><?php } ?>
      <?php if ($is_admin === 'super') { ?><a href="<?php echo nl_url('install.php'); ?>">설치/점검</a><?php } ?>
    </div>
  </div>
</footer>
<?php include_once G5_PATH.'/tail.sub.php'; ?>

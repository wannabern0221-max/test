<?php
if (!defined('_GNUBOARD_')) exit;
if (!nl_can_view_unpublished($view,$bo_table)) alert('게시 승인 전 콘텐츠는 작성자와 승인 권한자만 볼 수 있습니다.', nl_public_board_url($bo_table));
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v=1.1.0">', 0);
$status=$view['wr_10'] ?: 'pending';
$canDelete = !($status==='published' && !nl_can('content_approve') && $is_admin!=='super');
?>
<article class="nl-board nl-board-view">
  <header class="nl-board-view__head">
    <div class="nl-board-status"><?php echo nl_status_badge($status); ?><?php if($category_name){ ?><span><?php echo nl_h($view['ca_name']); ?></span><?php } ?></div>
    <h1><?php echo get_text($view['wr_subject']); ?></h1>
    <div class="nl-board-view__meta"><span><?php echo $view['name']; ?></span><span><?php echo nl_h(substr($view['wr_datetime'],0,16)); ?></span><span>조회 <?php echo number_format((int)$view['wr_hit']); ?></span></div>
    <?php if($status==='rejected' && !empty($view['wr_9'])){ ?><div class="nl-alert nl-alert--danger"><strong>반려 사유</strong><br><?php echo nl_h($view['wr_9']); ?></div><?php } ?>
  </header>

  <?php if(!empty($view['file']['count'])){ ?><section class="nl-board-files"><h2>첨부파일</h2><?php for($i=0;$i<count($view['file']);$i++){ if(empty($view['file'][$i]['file'])) continue; ?><a href="<?php echo nl_h($view['file'][$i]['href']); ?>" class="view_file_download"><strong><?php echo nl_h(stripslashes($view['file'][$i]['source'])); ?></strong><small><?php echo nl_h($view['file'][$i]['size']); ?></small></a><?php } ?></section><?php } ?>
  <?php if(isset($view['link']) && array_filter($view['link'])){ ?><section class="nl-board-links"><h2>관련 링크</h2><?php for($i=1;$i<=G5_LINK_COUNT;$i++){ if(empty($view['link'][$i])) continue; ?><a href="<?php echo nl_h($view['link_href'][$i]); ?>" target="_blank" rel="noopener noreferrer"><?php echo nl_h($view['link'][$i]); ?></a><?php } ?></section><?php } ?>

  <div class="nl-board-view__content"><?php echo get_view_thumbnail($view['content']); ?></div>

  <footer class="nl-board-view__actions">
    <a class="nl-btn" href="<?php echo nl_h($list_href); ?>">목록</a>
    <a class="nl-btn" href="<?php echo nl_public_article_url($bo_table,$view['wr_id']); ?>">공개 주소<?php echo $status==='published'?'':' (게시 전)'; ?></a>
    <?php if($update_href){ ?><a class="nl-btn" href="<?php echo nl_h($update_href); ?>">수정</a><?php } ?>
    <?php if($delete_href && $canDelete){ ?><a class="nl-btn nl-btn--danger" href="<?php echo nl_h($delete_href); ?>" onclick="return confirm('이 글을 삭제하시겠습니까?');">삭제</a><?php } ?>
    <?php if(nl_can('content_approve') && $status!=='published'){ ?><a class="nl-btn nl-btn--primary" href="<?php echo nl_url('content-manager.php'); ?>">승인센터</a><?php } ?>
  </footer>
</article>

<?php
if (!defined('_GNUBOARD_')) exit;
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v=1.1.0">', 0);
$visible=array();
foreach($list as $item){
    $status=$item['wr_10'] ?? '';
    if($status==='published' || nl_can_view_unpublished($item,$bo_table)) $visible[]=$item;
}
?>
<section class="nl-board">
  <header class="nl-board-head">
    <div><p class="nl-kicker">내부 검수</p><h1><?php echo nl_h($board['bo_subject']); ?></h1><p>내부 작성·검토 화면입니다. 공개 사이트에는 게시 승인된 콘텐츠만 표시됩니다.</p></div>
    <div class="nl-actions"><a class="nl-btn" href="<?php echo nl_public_board_url($bo_table); ?>">공개 화면</a><?php if($write_href && nl_can_write_board($bo_table)){ ?><a class="nl-btn nl-btn--primary" href="<?php echo nl_h($write_href); ?>">새 글 작성</a><?php } ?></div>
  </header>

  <?php if($is_category){ ?><nav class="nl-board-cats" aria-label="분류"><?php echo $category_option; ?></nav><?php } ?>

  <div class="nl-board-list">
    <div class="nl-board-list__head"><span>상태</span><span>제목</span><span>작성자</span><span>작성일</span></div>
    <?php if(!$visible){ ?><div class="nl-empty">표시할 게시물이 없습니다.</div><?php } ?>
    <?php foreach($visible as $item){ $status=$item['wr_10'] ?: 'pending'; ?>
      <a class="nl-board-row<?php echo ((int)$wr_id===(int)$item['wr_id'])?' is-current':''; ?>" href="<?php echo nl_h($item['href']); ?>">
        <span><?php echo nl_status_badge($status); ?></span>
        <span class="nl-board-row__subject"><?php if(!empty($item['is_notice'])){ ?><b class="nl-board-notice">공지</b><?php } ?><?php echo $item['subject']; ?><?php if(!empty($item['wr_comment'])){ ?><small>댓글 <?php echo (int)$item['wr_comment']; ?></small><?php } ?></span>
        <span><?php echo $item['name']; ?></span>
        <span><?php echo nl_h($item['datetime2']); ?></span>
      </a>
    <?php } ?>
  </div>

  <div class="nl-board-bottom">
    <div class="nl-board-pages"><?php echo $write_pages; ?></div>
    <?php if($write_href && nl_can_write_board($bo_table)){ ?><a class="nl-btn nl-btn--primary" href="<?php echo nl_h($write_href); ?>">글쓰기</a><?php } ?>
  </div>

  <form class="nl-board-search" method="get">
    <input type="hidden" name="bo_table" value="<?php echo nl_h($bo_table); ?>"><input type="hidden" name="sca" value="<?php echo nl_h($sca); ?>"><input type="hidden" name="sop" value="and">
    <label class="sound_only" for="nl-sfl">검색대상</label><select id="nl-sfl" name="sfl"><?php echo get_board_sfl_select_options($sfl); ?></select>
    <label class="sound_only" for="nl-stx">검색어</label><input id="nl-stx" type="search" name="stx" value="<?php echo nl_h(stripslashes($stx)); ?>" maxlength="20" placeholder="검색어"><button type="submit" class="nl-btn">검색</button>
  </form>
</section>

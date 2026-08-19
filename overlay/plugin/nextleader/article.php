<?php
include_once './_common.php';
nl_enqueue_assets();

$bo = preg_replace('/[^A-Za-z0-9_]/', '', nl_get_str('bo'));
$wrId = (int)nl_get_str('wr_id','0');
if (!in_array($bo, nl_allowed_boards(), true) || $wrId<1) alert('잘못된 콘텐츠 주소입니다.', G5_URL.'/');
$table=nl_board_table($bo);
$row=sql_fetch("SELECT * FROM {$table} WHERE wr_id={$wrId} AND wr_is_comment=0 AND wr_10='published' LIMIT 1", false);
if(empty($row['wr_id'])) alert('공개된 콘텐츠를 찾을 수 없습니다.', nl_public_board_url($bo));
$labels = array(NL_BOARD_NOTICE=>'공지사항', NL_BOARD_CARDS=>'카드뉴스', NL_BOARD_POLICY=>'정책 콘텐츠', NL_BOARD_ACTIVITY=>'사업자료');
$hitSession = 'nl_public_view_'.$bo.'_'.$wrId;
if (!get_session($hitSession)) {
    sql_query("UPDATE {$table} SET wr_hit=wr_hit+1 WHERE wr_id={$wrId}", false);
    set_session($hitSession, 1);
    $row['wr_hit'] = (int)$row['wr_hit'] + 1;
}
$board = function_exists('get_board_db') ? get_board_db($bo, true) : array('bo_table'=>$bo);
$html=0; if(strpos($row['wr_option'] ?? '','html2')!==false) $html=2; elseif(strpos($row['wr_option'] ?? '','html1')!==false) $html=1;
$content=conv_content($row['wr_content'], $html, true);
$files=get_file($bo,$wrId);
$g5['title']=$row['wr_subject'];
include_once G5_PATH.'/head.php';
?>
<article class="nl-page nl-article">
  <header class="nl-article-head"><a class="nl-back-link" href="<?php echo nl_public_board_url($bo); ?>">← <?php echo nl_h($labels[$bo] ?? '목록'); ?></a><h1><?php echo nl_h($row['wr_subject']); ?></h1><div class="nl-article-meta"><span><?php echo nl_h(substr($row['wr_datetime'],0,16)); ?></span><span>조회 <?php echo number_format((int)$row['wr_hit']); ?></span></div></header>
  <?php if(!empty($files['count'])){ ?><div class="nl-article-files"><strong>첨부파일</strong><?php for($i=0;$i<count($files);$i++){ if(empty($files[$i]['file'])) continue; ?><a href="<?php echo nl_url('public-download.php?bo='.rawurlencode($bo).'&wr_id='.$wrId.'&no='.$i); ?>"><?php echo nl_h(stripslashes($files[$i]['source'])); ?> <small><?php echo nl_h($files[$i]['size']); ?></small></a><?php } ?></div><?php } ?>
  <div class="nl-article-body"><?php echo $content; ?></div>
  <footer class="nl-article-footer"><a class="nl-btn" href="<?php echo nl_public_board_url($bo); ?>">목록으로</a><?php if(nl_can_write_board($bo)){ ?><a class="nl-btn" href="<?php echo G5_BBS_URL.'/write.php?bo_table='.rawurlencode($bo); ?>">새 글 작성</a><?php } ?></footer>
</article>
<?php include_once G5_PATH.'/tail.php'; ?>

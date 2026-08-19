<?php
if (!defined('_GNUBOARD_')) exit;
if (!nl_can_write_board($bo_table)) alert('승인된 리더만 콘텐츠를 작성할 수 있습니다.', nl_url('profile.php'));
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v=1.1.0">', 0);
$optionHidden='';
if($is_html && $is_dhtml_editor) $optionHidden='<input type="hidden" name="html" value="html1">';
$status = ($w==='u' && !empty($write['wr_10'])) ? $write['wr_10'] : 'new';
?>
<section class="nl-board nl-board-write">
  <header class="nl-board-head"><div><p class="nl-kicker">콘텐츠 작성</p><h1><?php echo nl_h($board['bo_subject']); ?> <?php echo $w==='u'?'수정':'작성'; ?></h1><p>일반 리더가 저장하면 승인 대기로 전환됩니다. 게시 승인 권한자는 바로 게시할 수 있습니다.</p></div><?php if($w==='u' && $status!=='new'){ ?><div><?php echo nl_status_badge($status); ?></div><?php } ?></header>
  <?php if($w==='u' && $status==='rejected' && !empty($write['wr_9'])){ ?><div class="nl-alert nl-alert--danger"><strong>반려 사유</strong><br><?php echo nl_h($write['wr_9']); ?></div><?php } ?>
  <form name="fwrite" id="fwrite" class="nl-board-form" action="<?php echo nl_h($action_url); ?>" onsubmit="return nl_fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="w" value="<?php echo nl_h($w); ?>"><input type="hidden" name="bo_table" value="<?php echo nl_h($bo_table); ?>"><input type="hidden" name="wr_id" value="<?php echo (int)$wr_id; ?>"><input type="hidden" name="sca" value="<?php echo nl_h($sca); ?>"><input type="hidden" name="sfl" value="<?php echo nl_h($sfl); ?>"><input type="hidden" name="stx" value="<?php echo nl_h($stx); ?>"><input type="hidden" name="spt" value="<?php echo nl_h($spt); ?>"><input type="hidden" name="sst" value="<?php echo nl_h($sst); ?>"><input type="hidden" name="sod" value="<?php echo nl_h($sod); ?>"><input type="hidden" name="page" value="<?php echo nl_h($page); ?>"><?php echo $optionHidden; ?>

    <?php if($is_category){ ?><div class="nl-field"><label for="ca_name">분류</label><select id="ca_name" name="ca_name" required><option value="">선택하세요</option><?php echo $category_option; ?></select></div><?php } ?>
    <div class="nl-field"><label for="wr_subject">제목</label><input type="text" name="wr_subject" value="<?php echo nl_h($subject); ?>" id="wr_subject" required maxlength="255"></div>
    <div class="nl-field nl-editor-field"><label for="wr_content">내용</label><?php echo $editor_html; ?><?php if($write_min || $write_max){ ?><small>내용은 <?php echo (int)$write_min; ?>자 이상 <?php echo (int)$write_max; ?>자 이하로 작성합니다.</small><div id="char_count_wrap"><span id="char_count"></span>자</div><?php } ?></div>

    <?php for($i=1;$is_link && $i<=G5_LINK_COUNT;$i++){ ?><div class="nl-field"><label for="wr_link<?php echo $i; ?>">관련 링크 <?php echo $i; ?></label><input type="url" name="wr_link<?php echo $i; ?>" value="<?php echo $w==='u'?nl_h($write['wr_link'.$i]):''; ?>" id="wr_link<?php echo $i; ?>" placeholder="https://"></div><?php } ?>

    <?php if($is_file){ ?><fieldset class="nl-board-upload"><legend>첨부파일</legend><?php for($i=0;$i<$file_count;$i++){ ?><div class="nl-file-input"><label for="bf_file_<?php echo $i; ?>">파일 <?php echo $i+1; ?></label><input type="file" name="bf_file[]" id="bf_file_<?php echo $i; ?>"><input type="text" name="bf_content[]" value="<?php echo ($w==='u' && isset($file[$i]['bf_content']))?nl_h($file[$i]['bf_content']):''; ?>" placeholder="파일 설명"><?php if($w==='u' && !empty($file[$i]['file'])){ ?><label class="nl-check"><input type="checkbox" name="bf_file_del[<?php echo $i; ?>]" value="1"> 기존 파일 삭제: <?php echo nl_h($file[$i]['source']); ?></label><?php } ?></div><?php } ?></fieldset><?php } ?>
    <?php if($is_use_captcha){ ?><div class="nl-field"><label>자동등록방지</label><?php echo $captcha_html; ?></div><?php } ?>

    <div class="nl-board-write__notice"><strong>게시 상태</strong><p><?php echo nl_can('content_approve')?'승인 권한이 있어 승인 요청 또는 즉시 게시를 선택할 수 있습니다.':'저장 후 승인 대기 상태가 되며 승인 권한자가 검토합니다.'; ?></p></div>
    <div class="nl-board-write__actions"><a class="nl-btn" href="<?php echo get_pretty_url($bo_table); ?>">취소</a><button type="submit" class="nl-btn nl-btn--primary" id="btn_submit" name="nl_publish_action" value="request">승인 요청으로 저장</button><?php if(nl_can('content_approve')){ ?><button type="submit" class="nl-btn" name="nl_publish_action" value="publish">바로 게시</button><?php } ?></div>
  </form>
</section>
<script>
<?php if($write_min || $write_max){ ?>
var char_min=parseInt(<?php echo (int)$write_min; ?>,10), char_max=parseInt(<?php echo (int)$write_max; ?>,10); check_byte('wr_content','char_count'); $('#wr_content').on('keyup',function(){check_byte('wr_content','char_count');});
<?php } ?>
function nl_fwrite_submit(f){
  <?php echo $editor_js; ?>
  var forbiddenSubject='', forbiddenContent='';
  $.ajax({url:g5_bbs_url+'/ajax.filter.php',type:'POST',data:{subject:f.wr_subject.value,content:f.wr_content.value},dataType:'json',async:false,cache:false,success:function(data){forbiddenSubject=data.subject;forbiddenContent=data.content;}});
  if(forbiddenSubject){alert("제목에 금지단어('"+forbiddenSubject+"')가 포함되어 있습니다.");f.wr_subject.focus();return false;}
  if(forbiddenContent){alert("내용에 금지단어('"+forbiddenContent+"')가 포함되어 있습니다.");if(typeof(ed_wr_content)!=='undefined') ed_wr_content.returnFalse(); else f.wr_content.focus();return false;}
  <?php if($write_min || $write_max){ ?>var cnt=parseInt(check_byte('wr_content','char_count'),10);if(char_min>0&&cnt<char_min){alert('내용은 '+char_min+'자 이상 작성해 주세요.');return false;}if(char_max>0&&cnt>char_max){alert('내용은 '+char_max+'자 이하로 작성해 주세요.');return false;}<?php } ?>
  <?php echo $captcha_js; ?>
  document.querySelectorAll('#fwrite button[type=submit]').forEach(function(b){b.disabled=true;}); return true;
}
</script>

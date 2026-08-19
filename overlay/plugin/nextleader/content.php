<?php
include_once './_common.php';
nl_enqueue_assets();
include_once G5_LIB_PATH.'/thumbnail.lib.php';

$bo = preg_replace('/[^A-Za-z0-9_]/', '', nl_get_str('bo', NL_BOARD_POLICY));
if (!in_array($bo, nl_allowed_boards(), true)) alert('존재하지 않는 콘텐츠 영역입니다.', G5_URL.'/');
$table = nl_board_table($bo);
if (!$table || !nl_table_exists($table)) alert('콘텐츠 게시판이 아직 설치되지 않았습니다.', nl_url('install.php'));
$labels = array(NL_BOARD_NOTICE=>'공지사항', NL_BOARD_CARDS=>'카드뉴스', NL_BOARD_POLICY=>'정책 콘텐츠', NL_BOARD_ACTIVITY=>'사업자료');
$title = $labels[$bo] ?? '콘텐츠';
$q = nl_get_str('q');
$page = max(1, (int)nl_get_str('page','1'));
$rowsPerPage = 15;
$where = "wr_is_comment=0 AND wr_10='published'";
if ($q !== '') {
    $e = nl_sql_escape($q);
    $where .= " AND (wr_subject LIKE '%{$e}%' OR wr_content LIKE '%{$e}%')";
}
$count = sql_fetch("SELECT COUNT(*) cnt FROM {$table} WHERE {$where}", false);
$total = (int)($count['cnt'] ?? 0);
$totalPage = max(1, (int)ceil($total/$rowsPerPage));
if ($page > $totalPage) $page = $totalPage;
$offset = ($page-1)*$rowsPerPage;
$result = sql_query("SELECT wr_id,wr_subject,wr_content,wr_datetime,wr_hit,wr_file,ca_name FROM {$table} WHERE {$where} ORDER BY wr_num,wr_reply LIMIT {$offset},{$rowsPerPage}", false);
$rows=array(); if ($result) while($r=sql_fetch_array($result)) $rows[]=$r;

$g5['title']=$title;
include_once G5_PATH.'/head.php';
?>
<div class="nl-page nl-public-content">
  <div class="nl-page-head"><p class="nl-kicker">정책국 자료</p><h1><?php echo nl_h($title); ?></h1><p><?php echo $bo===NL_BOARD_NOTICE?'정책국의 주요 안내와 공식 소식을 확인합니다.':($bo===NL_BOARD_CARDS?'정책 이슈를 시각 자료로 쉽고 빠르게 확인합니다.':($bo===NL_BOARD_POLICY?'간호와 보건의료 정책을 학생의 시선에서 정리한 콘텐츠입니다.':'정책국의 사업자료를 확인합니다.')); ?></p></div>
  <form class="nl-searchbar" method="get"><input type="hidden" name="bo" value="<?php echo nl_h($bo); ?>"><input type="search" name="q" value="<?php echo nl_h($q); ?>" placeholder="검색어를 입력하세요" aria-label="<?php echo nl_h($title); ?> 검색"><button class="nl-btn" type="submit">검색</button><?php if($q!==''){ ?><a class="nl-btn" href="<?php echo nl_public_board_url($bo); ?>">검색 해제</a><?php } ?></form>

  <?php if ($bo===NL_BOARD_CARDS) { ?>
    <div class="nl-public-card-grid">
    <?php foreach($rows as $r){ $thumb=get_list_thumbnail($bo,(int)$r['wr_id'],520,300,false,true); ?>
      <a class="nl-public-card" href="<?php echo nl_public_article_url($bo,$r['wr_id']); ?>">
        <span class="nl-public-card__media"><?php if(!empty($thumb['src'])){ ?><img src="<?php echo nl_h($thumb['src']); ?>" alt="<?php echo nl_h($thumb['alt'] ?? ''); ?>" loading="lazy"><?php } else { ?><span>CARD NEWS</span><?php } ?></span>
        <strong><?php echo nl_h($r['wr_subject']); ?></strong><small><?php echo nl_h(substr($r['wr_datetime'],0,10)); ?></small>
      </a>
    <?php } ?>
    <?php if(!$rows){ ?><div class="nl-empty">등록된 콘텐츠가 없습니다.</div><?php } ?>
    </div>
  <?php } else { ?>
    <div class="nl-public-list">
    <?php foreach($rows as $r){ ?>
      <a href="<?php echo nl_public_article_url($bo,$r['wr_id']); ?>"><div><strong><?php echo nl_h($r['wr_subject']); ?></strong><?php if($r['ca_name']){ ?><span><?php echo nl_h($r['ca_name']); ?></span><?php } ?></div><small><?php echo nl_h(substr($r['wr_datetime'],0,10)); ?></small></a>
    <?php } ?>
    <?php if(!$rows){ ?><div class="nl-empty">등록된 콘텐츠가 없습니다.</div><?php } ?>
    </div>
  <?php } ?>

  <?php if($totalPage>1){ ?><nav class="nl-pagination" aria-label="페이지">
    <?php for($p=max(1,$page-4);$p<=min($totalPage,$page+4);$p++){ $u=nl_public_board_url($bo).'&page='.$p.($q!==''?'&q='.rawurlencode($q):''); if($p===$page){ ?><span class="is-current" aria-current="page"><?php echo $p; ?></span><?php } else { ?><a href="<?php echo nl_h($u); ?>"><?php echo $p; ?></a><?php } } ?>
  </nav><?php } ?>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>

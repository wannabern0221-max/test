<?php
include_once './_common.php';

if (!nl_installed()) alert('NEXT LEADER 확장 기능이 설치되지 않았습니다.');

$category = nl_get_str('category');
$where = 'is_active=1';
if ($category) {
    $where .= " AND category='".nl_sql_escape(substr($category, 0, 100))."'";
}

$result = sql_query("SELECT * FROM ".nl_table('glossary')." WHERE {$where} ORDER BY term ASC", false);
$rows = array();
if ($result) {
    while ($row = sql_fetch_array($result)) $rows[] = $row;
}

$categoryResult = sql_query(
    "SELECT DISTINCT category FROM ".nl_table('glossary')." WHERE is_active=1 AND category<>'' ORDER BY category",
    false
);
$categories = array();
if ($categoryResult) {
    while ($row = sql_fetch_array($categoryResult)) $categories[] = $row['category'];
}

nl_enqueue_assets();
$g5['title'] = '정책단어';
include_once G5_PATH.'/head.php';
?>
<div class="nl-page">
  <div class="nl-page-head">
    <p class="nl-kicker">정책 자료</p>
    <h1>정책단어</h1>
    <p>간호·보건의료 정책에서 자주 만나는 용어를 학생의 시선으로 정리했습니다.</p>
  </div>

  <div class="nl-searchbar">
    <input type="search" data-glossary-search placeholder="용어, 설명, 분야 검색" aria-label="정책단어 검색">
    <?php if (nl_can('content_write_policy')) { ?><a class="nl-btn" href="<?php echo nl_url('glossary-manage.php'); ?>">단어 관리</a><?php } ?>
  </div>

  <nav class="nl-tabs" aria-label="정책단어 분야">
    <a class="<?php echo !$category ? 'is-active' : ''; ?>" href="<?php echo nl_url('glossary.php'); ?>">전체</a>
    <?php foreach ($categories as $item) { ?>
      <a class="<?php echo $category === $item ? 'is-active' : ''; ?>" href="<?php echo nl_url('glossary.php?category='.urlencode($item)); ?>"><?php echo nl_h($item); ?></a>
    <?php } ?>
  </nav>

  <div class="nl-glossary">
    <?php if (!$rows) { ?><div class="nl-empty">등록된 용어가 없습니다.</div><?php } ?>
    <?php foreach ($rows as $row) {
        $sourceUrl = nl_safe_external_url($row['source_url']);
    ?>
      <article class="nl-term" data-glossary-item>
        <span class="nl-term-cat"><?php echo nl_h($row['category']); ?></span>
        <h3><?php echo nl_h($row['term']); ?></h3>
        <p><?php echo nl_h($row['summary']); ?></p>
        <details>
          <summary>자세히</summary>
          <p><?php echo nl_h($row['detail']); ?></p>
          <?php if ($sourceUrl) { ?>
            <a class="nl-source-link" href="<?php echo nl_h($sourceUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo nl_h($row['source_title'] ?: '출처 보기'); ?></a>
          <?php } ?>
        </details>
      </article>
    <?php } ?>
  </div>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>

<?php
include_once './_common.php';
if ($is_admin !== 'super') alert('최고관리자만 시스템 점검을 실행할 수 있습니다.');
nl_enqueue_assets();

function nl_check_item($label, $status, $detail, $severity = 'fail') {
    return array('label'=>$label, 'status'=>(bool)$status, 'detail'=>$detail, 'severity'=>$severity);
}

$checks = array();
$checks[] = nl_check_item('PHP 8.2 이상', version_compare(PHP_VERSION, '8.2.0', '>='), '현재 PHP '.PHP_VERSION);
$g5ver = defined('G5_GNUBOARD_VER') ? G5_GNUBOARD_VER : '확인 불가';
$checks[] = nl_check_item('Gnuboard 버전', $g5ver === '5.6.34', '감지 버전: '.$g5ver.' / 패키지 기준: 5.6.34', 'warn');
$checks[] = nl_check_item('NEXT LEADER extend 로더', is_file(G5_EXTEND_PATH.'/nextleader.bootstrap.php'), G5_EXTEND_PATH.'/nextleader.bootstrap.php');
$themePath = defined('G5_THEME_PATH') ? G5_THEME_PATH : '';
$checks[] = nl_check_item('NEXT LEADER 테마', $themePath !== '' && is_dir($themePath) && basename($themePath)==='nextleader', '현재 테마 경로: '.($themePath ?: '없음'), 'warn');

$tables = array('profile','permission','notification','schedule','unavailable','glossary','quiz','anonymous_post','anonymous_comment','audit_log','file','news');
foreach ($tables as $name) {
    $table = nl_table($name);
    $checks[] = nl_check_item('DB 테이블 '.$table, nl_table_exists($table), $table);
}

if (nl_table_exists(nl_table('profile'))) {
    $checks[] = nl_check_item(
        '회원 레벨 추적 컬럼',
        nl_profile_level_tracking_supported(),
        'base_mb_level / managed_mb_level',
        'warn'
    );
}

$boardLabels = array(
    NL_BOARD_NOTICE=>'공지사항',
    NL_BOARD_CARDS=>'카드뉴스',
    NL_BOARD_POLICY=>'정책 콘텐츠',
    NL_BOARD_ACTIVITY=>'사업자료'
);
foreach ($boardLabels as $bo=>$label) {
    $boEsc = nl_sql_escape($bo);
    $row = sql_fetch("SELECT bo_table,gr_id,bo_skin,bo_mobile_skin,bo_use_search,bo_use_rss_view,bo_list_level,bo_read_level,bo_write_level FROM {$g5['board_table']} WHERE bo_table='{$boEsc}' LIMIT 1", false);
    $exists = !empty($row['bo_table']);
    $checks[] = nl_check_item('게시판 '.$bo.' · '.$label, $exists, $exists ? ('그룹 '.$row['gr_id'].' / 스킨 '.$row['bo_skin']) : '게시판 없음');
    if ($exists) {
        $checks[] = nl_check_item($bo.' 내부 접근 레벨', (int)$row['bo_list_level']>=3 && (int)$row['bo_read_level']>=3 && (int)$row['bo_write_level']>=3, 'list/read/write = '.(int)$row['bo_list_level'].'/'.(int)$row['bo_read_level'].'/'.(int)$row['bo_write_level']);
        $checks[] = nl_check_item($bo.' 전역 검색/RSS 제외', (int)$row['bo_use_search']===0 && (int)$row['bo_use_rss_view']===0, 'search='.(int)$row['bo_use_search'].' / rss='.(int)$row['bo_use_rss_view']);
    }
}

$storageReady = is_dir(nl_storage_path()) && is_writable(nl_storage_path());
$checks[] = nl_check_item('비공개 파일 저장소 쓰기 가능', $storageReady, nl_storage_path());
$checks[] = nl_check_item('Apache 직접 접근 차단 파일', is_file(nl_storage_path().'/.htaccess'), nl_storage_path().'/.htaccess', 'warn');

if (nl_table_exists(nl_table('quiz'))) {
    $r=sql_fetch("SELECT COUNT(*) cnt FROM ".nl_table('quiz')." WHERE is_active=1",false);
    $checks[] = nl_check_item('정책 퀴즈 데이터', (int)($r['cnt']??0)>0, '활성 '.(int)($r['cnt']??0).'개', 'warn');
}
if (nl_table_exists(nl_table('glossary'))) {
    $r=sql_fetch("SELECT COUNT(*) cnt FROM ".nl_table('glossary')." WHERE is_active=1",false);
    $checks[] = nl_check_item('정책단어 데이터', (int)($r['cnt']??0)>0, '활성 '.(int)($r['cnt']??0).'개', 'warn');
}
if (nl_table_exists(nl_table('news'))) {
    $r=sql_fetch("SELECT COUNT(*) cnt FROM ".nl_table('news')." WHERE is_active=1",false);
    $checks[] = nl_check_item('뉴스 데이터', (int)($r['cnt']??0)>0, '활성 '.(int)($r['cnt']??0).'개', 'warn');
}

$fail = 0; $warn = 0; $pass = 0;
foreach ($checks as $c) {
    if ($c['status']) $pass++;
    elseif ($c['severity']==='warn') $warn++;
    else $fail++;
}

$g5['title']='NEXT LEADER 시스템 점검';
include_once G5_PATH.'/head.php';
?>
<div class="nl-page">
  <div class="nl-page-head"><p class="nl-kicker">시스템 운영</p><h1>설치·운영 점검</h1><p>읽기 중심의 점검입니다. 운영 데이터 삭제나 자동 마이그레이션은 실행하지 않습니다.</p></div>
  <div class="nl-grid"><div class="nl-stat"><strong><?php echo $pass; ?></strong><span>정상</span></div><div class="nl-stat"><strong><?php echo $warn; ?></strong><span>확인 필요</span></div><div class="nl-stat"><strong><?php echo $fail; ?></strong><span>실패</span></div></div>
  <?php if($fail){ ?><div class="nl-alert nl-alert--danger"><strong>운영 전 수정이 필요한 항목이 있습니다.</strong> 실패 항목을 해결한 뒤 실제 사용자 흐름 테스트를 진행하세요.</div><?php } elseif($warn){ ?><div class="nl-alert nl-alert--warning"><strong>핵심 설치 검사는 통과했습니다.</strong> 확인 필요 항목과 `TEST_CHECKLIST.md` 수동 테스트를 마쳐야 배포 준비가 완료됩니다.</div><?php } else { ?><div class="nl-alert nl-alert--success"><strong>정적 설치 항목은 정상입니다.</strong> 이 결과는 브라우저·권한·회귀 테스트를 대체하지 않습니다.</div><?php } ?>
  <div class="nl-table-wrap"><table class="nl-table"><thead><tr><th>결과</th><th>항목</th><th>상세</th></tr></thead><tbody><?php foreach($checks as $c){ ?><tr><td><?php if($c['status']){ ?><span class="nl-badge nl-badge--approved">정상</span><?php } elseif($c['severity']==='warn'){ ?><span class="nl-badge nl-badge--pending">확인</span><?php } else { ?><span class="nl-badge nl-badge--suspended">실패</span><?php } ?></td><td><strong><?php echo nl_h($c['label']); ?></strong></td><td><?php echo nl_h($c['detail']); ?></td></tr><?php } ?></tbody></table></div>
  <div class="nl-actions nl-actions--top"><a class="nl-btn" href="<?php echo nl_url('dashboard.php'); ?>">리더 홈</a><a class="nl-btn" href="<?php echo nl_url('install.php'); ?>">설치 화면</a></div>
</div>
<?php include_once G5_PATH.'/tail.php'; ?>

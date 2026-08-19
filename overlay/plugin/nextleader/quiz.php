<?php
include_once './_common.php';
if(!nl_installed())alert('NEXT LEADER 확장 기능이 설치되지 않았습니다.');
$t=nl_table('quiz');$resultData=null;$questions=array();
if($_SERVER['REQUEST_METHOD']==='POST'){
 nl_verify_csrf();$ids=$_SESSION['nl_quiz_ids']??array();if(!$ids||!is_array($ids))alert('퀴즈 세션이 만료되었습니다. 새 문제로 다시 시작해 주세요.',nl_url('quiz.php'));
 $safe=array_values(array_filter(array_map('intval',$ids)));if(!$safe)alert('퀴즈 정보를 확인할 수 없습니다.',nl_url('quiz.php'));
 $res=sql_query("SELECT * FROM {$t} WHERE id IN (".implode(',',$safe).") AND is_active=1",false);$map=array();if($res)while($r=sql_fetch_array($res))$map[(int)$r['id']]=$r;
 $score=0;$details=array();foreach($safe as $id){if(!isset($map[$id]))continue;$q=$map[$id];$given=isset($_POST['answer'][$id])?(int)$_POST['answer'][$id]:-1;$correct=(int)$q['answer_index'];if($given===$correct)$score++;$choices=json_decode($q['choices_json'],true);$details[]=array('q'=>$q,'choices'=>is_array($choices)?$choices:array(),'given'=>$given,'correct'=>$correct);}
 $resultData=array('score'=>$score,'total'=>count($details),'details'=>$details);unset($_SESSION['nl_quiz_ids']);
}else{
 $res=sql_query("SELECT id FROM {$t} WHERE is_active=1 ORDER BY RAND() LIMIT 10",false);$ids=array();if($res)while($r=sql_fetch_array($res))$ids[]=(int)$r['id'];$_SESSION['nl_quiz_ids']=$ids;if($ids){$res=sql_query("SELECT * FROM {$t} WHERE id IN (".implode(',',$ids).")",false);$map=array();if($res)while($r=sql_fetch_array($res))$map[(int)$r['id']]=$r;foreach($ids as $id)if(isset($map[$id]))$questions[]=$map[$id];}
}
nl_enqueue_assets();$g5['title']='정책 퀴즈';include_once G5_PATH.'/head.php';
?>
<div class="nl-page nl-narrow"><div class="nl-page-head"><p class="nl-kicker">정책 학습</p><h1>정책 퀴즈</h1><p>기존 NEXT LEADER 문제 데이터를 이용해 정책 핵심 내용을 확인합니다.</p></div>
<?php if($resultData){ ?><div class="nl-panel"><h2><?php echo (int)$resultData['score']; ?> / <?php echo (int)$resultData['total']; ?>점</h2><p><?php echo $resultData['total']&&$resultData['score']/$resultData['total']>=.8?'핵심 내용을 잘 이해하고 있습니다.':'해설을 확인하고 다시 도전해 보세요.'; ?></p><a class="nl-btn nl-btn--primary" href="<?php echo nl_url('quiz.php'); ?>">새 문제 풀기</a></div><?php foreach($resultData['details'] as $i=>$d){$q=$d['q'];$ok=$d['given']===$d['correct']; ?><section class="nl-quiz-question"><h3><?php echo ($i+1).'. '.nl_h($q['question']); ?></h3><p class="<?php echo $ok?'nl-result-good':'nl-result-bad'; ?>"><?php echo $ok?'정답':'오답'; ?> · 정답: <?php echo isset($d['choices'][$d['correct']])?nl_h($d['choices'][$d['correct']]):'확인 필요'; ?></p><p><?php echo nl_h($q['explanation']); ?></p><?php $url=nl_safe_external_url($q['source_url']);if($url){ ?><a class="nl-source-link" href="<?php echo nl_h($url); ?>" target="_blank" rel="noopener noreferrer"><?php echo nl_h($q['source_title']?:'출처'); ?></a><?php } ?></section><?php } ?>
<?php } else { ?><?php if(!$questions){ ?><div class="nl-empty">퀴즈 데이터가 없습니다. 관리자 설치 화면에서 초기 데이터를 등록해 주세요.</div><?php } else { ?><form method="post" class="nl-panel"><?php echo nl_csrf_field(); ?><?php foreach($questions as $i=>$q){$choices=json_decode($q['choices_json'],true); ?><section class="nl-quiz-question"><p class="nl-kicker"><?php echo nl_h($q['difficulty'].' · '.$q['category']); ?></p><h3><?php echo ($i+1).'. '.nl_h($q['question']); ?></h3><?php foreach((is_array($choices)?$choices:array()) as $ci=>$choice){ ?><label class="nl-choice"><input type="radio" name="answer[<?php echo (int)$q['id']; ?>]" value="<?php echo (int)$ci; ?>" required><span><?php echo nl_h($choice); ?></span></label><?php } ?></section><?php } ?><button class="nl-btn nl-btn--primary" type="submit">채점하기</button></form><?php } ?><?php } ?></div>
<?php include_once G5_PATH.'/tail.php'; ?>

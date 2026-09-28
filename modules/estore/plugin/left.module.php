<?php
# Poll
include_once(ROOT_PATH."classes/dao/questions.class.php");
include_once(ROOT_PATH."classes/dao/answers.class.php");
$questions= new Questions();
$answers = new Answers();
$listQuestion = $questions->getObjects(1,"status=1",array("position"=>'ASC'),1);
$listAnswers= array();
$totalCount=0;
foreach($listQuestion as $question){
	$listAnswers=$answers->getAnswerFromQId($question->getId());
	if($listAnswers){
		foreach($listAnswers as $answer){
			$totalCount=$totalCount+$answer->getCounts();
			}
		}
	} 
$template->assign("listQuestion",$listQuestion);
$template->assign("listAnswers",$listAnswers);
$template->assign("totalCount",$totalCount);
# Web link
include_once(ROOT_PATH."classes/dao/weblinks.class.php");
$weblinks = new Weblinks();
$listWeblinks = $weblinks->getObjects(1,"status=1",array('id'=>'ASC'),'');
$template->assign("listWeblinks",$listWeblinks);
?>
<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
include_once(ROOT_PATH."classes/dao/articles.class.php");
include_once(ROOT_PATH . "classes/template/smarty.class.php");
include_once(ROOT_PATH . 'includes/functions.inc.php');
include_once(ROOT_PATH . "classes/dao/uploads.class.php");
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . 'classes/dao/estores.class.php');

// Khởi tạo Smarty
$template = new Smarty;
$template->setTemplateDir(ROOT_PATH . TEMPLATE_PATH . '/mpx/');
$template->registerPlugin('modifier', 'date', 'date');
$template->registerPlugin('modifier', 'strtotime', 'strtotime');

$articles=new Articles(1);
$uploads = new Uploads(1);

$groupId=$request->element("group_id");

$categoryId=(int)$request->element("category_id");

$lang=$request->element("lang");
if (!in_array($lang, ['vn', 'en'])) $lang = 'vn';

$messages = [];
if ($lang === 'en') {
    include ROOT_PATH . 'languages/en.php';
} else {
    include ROOT_PATH . 'languages/vn.php';
}

if($groupId=="all"){

    $condition="a.`status`=1
                AND a.`category_id`=".$categoryId;

}else{

    $condition="a.`status`=1
                AND a.`category_id`=".$categoryId."
                AND FIND_IN_SET(".(int)$groupId.",a.`article_group_ids`)";

}

$articlesServices=$articles->getObjects(
    1,
    $condition,
    array("a.id"=>"DESC"),
    10
);

$template->assign("articlesServices",$articlesServices);
$template->assign('templatePath', TEMPLATE_PATH);
$template->assign('userTemplate', 'mpx');
$template->assign('uploads',      $uploads);
$template->assign('lang',         $lang);
$template->assign('messages',     $messages);

$html=$template->fetch("component/article-service-items.tpl.html");

echo json_encode(array(

    "status"=>1,

    "html"=>$html

));
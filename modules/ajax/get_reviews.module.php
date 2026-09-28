<?php 
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    include_once(ROOT_PATH . 'classes/dao/comments.class.php');
    include_once(ROOT_PATH . 'classes/template/smarty.class.php');
    include_once(ROOT_PATH . 'includes/functions.inc.php');

    $pid     = (int)($request->element('pid') ?? 0);
    $page    = (int)($request->element('page') ?? 1);
    $star    = (int)($request->element('star') ?? 0);
    $perPage = 6;
    $lang    = $request->element('lang') ?? 'vn';

    if (!in_array($lang, ['vn', 'en'])) $lang = 'vn';

    $messages = [];
    if ($lang === 'en') {
        include ROOT_PATH . 'languages/en.php';
    } else {
        include ROOT_PATH . 'languages/vn.php';
    }

    $comments  = new Comments(1);
    $condition = "status = 1 AND pid = $pid";
    if ($star > 0) $condition .= " AND star = $star";

    $result = paginate(
        ['page' => $page],
        $comments,
        $condition,
        $condition,
        ['id' => 'DESC'],
        $perPage
    );

    // ===== INIT SMARTY =====
    $template = new Smarty;
    $template->setTemplateDir(ROOT_PATH . TEMPLATE_PATH . '/mpx/');
    $template->registerPlugin('modifier', 'date', 'date');
    $template->registerPlugin('modifier', 'strtotime', 'strtotime');

    // ===== ASSIGN =====
    $template->assign('items',        $result['items']);
    $template->assign('page',         $result['page']);
    $template->assign('totalPages',   $result['totalPages']);
    $template->assign('totalRows',    $result['totalRows']);
    $template->assign('itemsPerPage', $perPage);
    $template->assign('templatePath', TEMPLATE_PATH);
    $template->assign('userTemplate', 'mpx');
    $template->assign('lang',         $lang);
    $template->assign('messages',     $messages);

    $template->display('component/comment-list.tpl.html');
    exit;
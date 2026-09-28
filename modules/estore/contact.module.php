<?php

$templateFile = 'contact.tpl.html';

include_once(ROOT_PATH.'classes/dao/contacts.class.php');
$contacts = new Contacts(1);

$slugActive = 'lien-he';
$template->assign('slugActive', $slugActive);

assignLangUrls($template, $menus, 9);

$lang = $request->element('lang');
if (empty($lang) || !in_array($lang, ['vn', 'en', 'zh'])) {
    $lang = 'vn';
}

$navConfig = [
    'vn' => [
        ['name' => 'Trang chủ',   'url' => '/'],
        ['name' => 'Liên hệ',  'url' => '/lien-he'],
    ],
    'en' => [
        ['name' => 'Home',        'url' => '/en'],
        ['name' => 'Contact Us',    'url' => '/en/contact-us'],
    ],
];

$topNav = $navConfig[$lang];

if ($topNav) $template->assign('topNav', $topNav);
$template->assign('breadcrumbJson', buildBreadcrumbSchema($topNav));

$contactMenu = $menus->getObject(9);
$template->assign('contactMenu', $contactMenu);

if($lang == 'vn') {
    $pageTitle = $contactMenu->getProperty('custom_titleSeo');
    $pageKeywords = $contactMenu->getProperty('custom_meta_keyword');
    $pageDescription = $contactMenu->getProperty('custom_intro');
} else if($lang == 'en') {
    $pageKeywords = $contactMenu->getProperty('custom_en_meta_keyword') ?: $contactMenu->getProperty('custom_meta_keyword');
    $pageDescription = $contactMenu->getProperty('custom_en_intro') ?: $contactMenu->getProperty('custom_intro');
    $pageTitle = $contactMenu->getProperty('custom_en_titleSeo') ?: $contactMenu->getProperty('custom_titleSeo');
}

$template->assign('pageKeywords', $pageKeywords);
$template->assign('pageDescription', $pageDescription);
$template->assign('pageTitle', $pageTitle);
$template->assign('titlePage', $pageTitle);
<?php
/**
 * Compile public editorial Smarty templates without loading site config or DB.
 * Run locally: php cron/editorial-public-smoke.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once dirname(__DIR__) . '/classes/template/smarty.class.php';

class EditorialSmokeStore
{
    public function getProperty($key) { return ''; }
    public function getAddress($lang) { return ''; }
    public function getTel() { return ''; }
    public function getEmail() { return ''; }
}

class EditorialSmokeArticle
{
    public function getId() { return 1; }
    public function getAvatarImage($uploads) { return null; }
    public function getTitle($lang) { return strtoupper($lang) . ' cover story'; }
    public function getDescription($lang) { return strtoupper($lang) . ' cover description'; }
    public function getUrl($lang) { return ($lang === 'vn' ? '' : '/' . $lang) . '/cover-story'; }
    public function getSlug() { return 'cover-story'; }
    public function getCategorySlug() { return 'tho'; }
    public function getCategoryName() { return 'Thơ'; }
    public function getDisplayDate() { return '18/09/2026'; }
    public function getDetail($lang) { return '<p>' . strtoupper($lang) . ' article body</p>'; }
    public function getPublishAt() { return '2026-09-18 12:00:00'; }
    public function getDateCreated() { return '2026-09-18 12:00:00'; }
    public function getProperty($key) { return ''; }
}

$smarty = new Smarty();
$smarty->setTemplateDir(dirname(__DIR__) . '/templates/mpx');
$smarty->setCompileDir(sys_get_temp_dir());
$smarty->force_compile = true;

$fixtures = array(
    'estore' => new EditorialSmokeStore(),
    'templatePath' => 'templates',
    'userTemplate' => 'mpx',
    'rootUrl' => '',
    'urlVi' => '/',
    'urlEn' => '/en',
    'urlZh' => '/zh',
    'pageTitle' => 'Editorial smoke test',
    'pageDescription' => 'Test only',
    'pageKeywords' => 'test',
    'currentUrlx1' => '',
    'breadcrumbJson' => '',
    'csrf_token' => '',
    'logoimg' => '',
    'logoimg1' => '',
    'typeweb' => 'website',
    'categoryObj' => null,
    'CustomerId' => 0,
    'readingHubPath' => '/khong-gian-doc',
    'items' => array(),
    'homeLatest' => array(),
    'homeEarlier' => array(),
    'homeCover' => null,
    'homeTrending' => array(),
    'uploads' => null,
    'curatedVideos' => array(),
    'recentArticlesBlock' => array(),
    'articleAuthor' => null,
    'articleTags' => array(),
    'editorialMemberRating' => 0,
    'editorialRatingSummary' => array('average' => 0, 'count' => 0),
    'editorialTitle' => 'Test',
    'editorialIntro' => 'Test',
    'totalPages' => 1,
    'page' => 1,
);
foreach ($fixtures as $key => $value) {
    $smarty->assign($key, $value);
}

$failures = array();
$checks = 0;
foreach (array('vn' => '', 'en' => '/en', 'zh' => '/zh') as $lang => $prefix) {
    $smarty->assign('lang', $lang);
    $smarty->assign('publicBase', $prefix);
    $smarty->assign('publicHome', $prefix === '' ? '/' : $prefix . '/');
    $smarty->assign('slugActive', '');

    $home = $smarty->fetch('editorial-home.tpl.html');
    foreach (array('van-tho', 'nghe-thuat', 'tin-tuc', 'video') as $section) {
        ++$checks;
        if (strpos($home, 'href="' . $prefix . '/' . $section . '"') === false) {
            $failures[] = "$lang home: $section";
        }
    }
    ++$checks;
    if (strpos($home, 'editorial-ui.js') === false || strpos($home, 'jquery-1.11.0') !== false) {
        $failures[] = "$lang home: public-only JavaScript";
    }
    ++$checks;
    if (strpos($home, 'editorial-edition.css') === false || strpos($home, 'ed-home ed-journal') === false) {
        $failures[] = "$lang home: journal edition";
    }

    $smarty->assign('homeCover', new EditorialSmokeArticle());
    $coverHome = $smarty->fetch('editorial-home.tpl.html');
    ++$checks;
    if (strpos($coverHome, strtoupper($lang) . ' cover story') === false || strpos($coverHome, 'href="' . $prefix . '/cover-story"') === false) {
        $failures[] = "$lang home: dynamic cover";
    }
    $smarty->assign('homeCover', null);

    $smarty->assign('objectInfo', new EditorialSmokeArticle());
    $smarty->assign('canReadArticle', true);
    $smarty->assign('articleDetailHtml', '<p>' . strtoupper($lang) . ' article body</p>');
    $detail = $smarty->fetch('news-detail.tpl.html');
    ++$checks;
    if (strpos($detail, 'ed-detail-page ed-journal') === false || strpos($detail, strtoupper($lang) . ' article body') === false || strpos($detail, 'editorial-reader.js') === false) {
        $failures[] = "$lang article: journal reading view";
    }
    $smarty->assign('canReadArticle', false);
    $smarty->assign('articleDetailHtml', 'PREMIUM BODY MUST NOT LEAK');
    $lockedDetail = $smarty->fetch('news-detail.tpl.html');
    ++$checks;
    if (strpos($lockedDetail, 'ed-premium-gate') === false || strpos($lockedDetail, 'PREMIUM BODY MUST NOT LEAK') !== false || strpos($lockedDetail, 'data-reader-article') !== false) {
        $failures[] = "$lang article: premium content gate";
    }

    $smarty->assign('personalRecommendations', array());
    $smarty->assign('savedItems', array());
    $smarty->assign('historyItems', array());
    $smarty->assign('watchLaterItems', array());
    $smarty->assign('membershipPlans', array(array(
        'id' => 3, 'name' => 'Premium 30', 'description' => 'Test plan', 'price' => '99000.00',
        'currency' => 'VND', 'duration_days' => 30,
    )));
    $smarty->assign('activeSubscription', 0);
    $smarty->assign('vnpayConfigured', true);
    $smarty->assign('paymentError', '');
    $readingHub = $smarty->fetch('reading-hub.tpl.html');
    ++$checks;
    if (strpos($readingHub, 'ed-membership') === false || strpos($readingHub, $prefix . '/thanh-toan/vnpay') === false || strpos($readingHub, 'csrf_token') === false) {
        $failures[] = "$lang membership: checkout form";
    }

    $smarty->assign('paymentSignatureValid', true);
    $smarty->assign('paymentTransaction', array('status' => 1));
    $smarty->assign('paymentReportedSuccess', true);
    $paymentReturn = $smarty->fetch('vnpay-return.tpl.html');
    ++$checks;
    if (strpos($paymentReturn, 'ed-payment-result') === false || strpos($paymentReturn, $smarty->getTemplateVars('readingHubPath')) === false) {
        $failures[] = "$lang payment: return page";
    }

    $smarty->assign('curatedVideos', array(array(
        'id' => 'Q8ucXj2pDbo',
        'type' => 'culture',
        'title' => 'VI title', 'title_en' => 'EN title', 'title_zh' => 'ZH title',
        'description' => 'VI description', 'description_en' => 'EN description', 'description_zh' => 'ZH description',
        'source' => 'VI source', 'source_en' => 'EN source', 'source_zh' => 'ZH source',
    )));
    $video = $smarty->fetch('editorial-video.tpl.html');
    ++$checks;
    if (strpos($video, 'href="' . $prefix . '/video"') === false || strpos($video, 'editorial-ui.js') === false) {
        $failures[] = "$lang video: navigation or script";
    }
    ++$checks;
    $expectedLanguage = $lang === 'vn' ? 'VI' : strtoupper($lang);
    if (strpos($video, $expectedLanguage . ' description') === false || strpos($video, $expectedLanguage . ' source') === false || ($lang !== 'vn' && strpos($video, 'VI description') !== false)) {
        $failures[] = "$lang video: localized description or source";
    }
    ++$checks;
    if (strpos($video, 'data-video-filter="documentary"') === false || strpos($video, 'data-video-type="culture"') === false) {
        $failures[] = "$lang video: filter controls";
    }
    $smarty->assign('curatedVideos', array());

    foreach (array('van-tho' => 'tho', 'nghe-thuat' => 'am-nhac', 'tin-tuc' => 'tin-tuc-moi') as $section => $tab) {
        $_GET['category'] = $tab;
        $smarty->assign('slugActive', $section);
        // Deliberately simulate an old host module supplying an unprefixed route.
        $smarty->assign('editorialRoute', '/' . $section);
        $smarty->assign('categoryNav', array(array('slug' => $tab, 'name' => $tab)));
        $html = $smarty->fetch('editorial-list.tpl.html');
        ++$checks;
        if (strpos($html, 'href="' . $prefix . '/' . $section . '?category=' . $tab . '"') === false) {
            $failures[] = "$lang list: $section/$tab";
        }
        ++$checks;
        $sectionClass = $section === 'van-tho' ? 'ed-archive--literature' : ($section === 'nghe-thuat' ? 'ed-archive--arts' : 'ed-archive--news');
        if (strpos($html, $sectionClass) === false) {
            $failures[] = "$lang list: $sectionClass";
        }

        $smarty->assign('page', 2);
        $smarty->assign('totalPages', 3);
        $pagination = $smarty->fetch('listarticlesfromsearch.tpl.html');
        ++$checks;
        if (strpos($pagination, '?category=' . $tab . '&amp;page=3') === false) {
            $failures[] = "$lang pagination: $section/$tab";
        }
        $smarty->assign('page', 1);
        $smarty->assign('totalPages', 1);
    }
}

$css = file_get_contents(dirname(__DIR__) . '/templates/mpx/css/literature-redesign.css');
preg_match_all('#\.\./font/([A-Za-z0-9_.-]+)#', $css, $fontMatches);
foreach (array_unique($fontMatches[1]) as $fontFile) {
    ++$checks;
    if (!is_file(dirname(__DIR__) . '/templates/mpx/font/' . $fontFile)) {
        $failures[] = "missing local font: $fontFile";
    }
}

if ($failures) {
    fwrite(STDERR, "FAILED: " . implode(', ', $failures) . PHP_EOL);
    exit(1);
}
echo "OK: $checks public template checks passed across VI/EN/ZH." . PHP_EOL;

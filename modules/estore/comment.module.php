<?php

include_once(ROOT_PATH . 'classes/dao/comments.class.php');
$comments = new Comments($storeId);
$slug = $request->element('slug');
$idPr = $request->element('idPr');

include_once(ROOT_PATH . 'classes/dao/products.class.php');
include_once(ROOT_PATH . 'classes/dao/articles.class.php');
$products = new Products($sId);
$articles = new Articles($sId);
if ($_POST && $request->element('doo') == 'submit') {
    $pid = "3";
    $productItems = $products->getObject($slug, "slug");
    if ($productItems) {
        $pid = "1";
    }
    $articleItems = $articles->getObject($slug, "slug");
    if ($articleItems) {
        $pid = "2";
    }

    $data = array(
        'store_id' => '1',
        'fullname' => $request->element('fullname'),
        'details' => $request->element('content'),
        'status' => '0',
        'pid' => $pid,
        'slug' => $request->element('slug'),
        'star' => $request->element('score-reviews'),
        'created' => date("Y-m-d H:i:s")
    );

    $new = $comments->addData($data);
    #danh sách comment
    $listComment = $comments->getObjects(1,"`slug` = '$slug'", array("id" => "DESC"), 999);
    $CountStar = [];
    foreach ($listComment as $value) {
        $star = $value->getStars();
        array_push($CountStar, $star);
    }
    #
    $Totalpoints = array_sum($CountStar);
    $Numberreviews = COUNT($listComment);
    #Điểm trung bình = Tổng số điểm / Số lượng đánh giá = 21 điểm / 5 = 4.2 điểm
    $Averagescore = $Totalpoints / $Numberreviews;
    $floor = floor($Averagescore);
    $data = array(
        'star' => $floor,
    );
    $products->updateData($data,$idPr);

    header("location:/$slug");
}

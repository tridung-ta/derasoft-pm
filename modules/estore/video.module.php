<?php
$curatedVideos = array(
    array('id'=>'Q8ucXj2pDbo','type'=>'culture','title'=>'Không gian văn hóa nghệ thuật: Bảo tàng Mỹ thuật Việt Nam','title_en'=>'Vietnam Fine Arts Museum: An artistic and cultural space','title_zh'=>'越南美术博物馆：艺术与文化空间','description'=>'Khám phá không gian lưu giữ và giới thiệu những giá trị tiêu biểu của mỹ thuật Việt Nam.','description_en'=>'Explore a space preserving and presenting notable works of Vietnamese fine art.','description_zh'=>'探索保存与展示越南美术代表作品的空间。','source'=>'Bảo tàng Mỹ thuật Việt Nam','source_en'=>'Vietnam Fine Arts Museum','source_zh'=>'越南美术博物馆'),
    array('id'=>'q-r5WwQukik','type'=>'documentary','title'=>'Phim tài liệu 60 năm Bảo tàng Mỹ thuật Việt Nam','title_en'=>'Documentary: 60 years of the Vietnam Fine Arts Museum','title_zh'=>'纪录片：越南美术博物馆六十年','description'=>'Hành trình hình thành, phát triển và gìn giữ di sản mỹ thuật qua nhiều thế hệ.','description_en'=>'The museum journey and its work preserving artistic heritage across generations.','description_zh'=>'回顾博物馆的发展历程，以及跨越世代的美术遗产保护工作。','source'=>'Bảo tàng Mỹ thuật Việt Nam','source_en'=>'Vietnam Fine Arts Museum','source_zh'=>'越南美术博物馆'),
    array('id'=>'vx54SQs3A1M','type'=>'art','title'=>'Trải nghiệm mỹ thuật với ứng dụng iMuseum VFA','title_en'=>'Experiencing art with the iMuseum VFA application','title_zh'=>'通过 iMuseum VFA 应用体验艺术','description'=>'Một cách tiếp cận mới giúp công chúng tìm hiểu tác phẩm và không gian trưng bày.','description_en'=>'A new way for visitors to explore artworks and exhibition spaces.','description_zh'=>'以新的方式帮助观众了解艺术作品与展览空间。','source'=>'Bảo tàng Mỹ thuật Việt Nam','source_en'=>'Vietnam Fine Arts Museum','source_zh'=>'越南美术博物馆'),
    array('id'=>'tJTkPdk3Mks','type'=>'art','title'=>'Không gian mỹ thuật đương đại tại Bảo tàng Mỹ thuật Việt Nam','title_en'=>'Contemporary art at the Vietnam Fine Arts Museum','title_zh'=>'越南美术博物馆的当代艺术空间','description'=>'Góc nhìn về thực hành sáng tạo và những chuyển động của mỹ thuật đương đại.','description_en'=>'A look at creative practice and developments in contemporary fine art.','description_zh'=>'了解当代美术的创作实践与发展。','source'=>'Bảo tàng Mỹ thuật Việt Nam','source_en'=>'Vietnam Fine Arts Museum','source_zh'=>'越南美术博物馆')
);
$featureTable = DB_PREFIX . 'editorial_features';
$featureCheck = $db->query("SHOW TABLES LIKE '" . addslashes($featureTable) . "'");
$hasFeatureTable = $featureCheck && $db->numRows($featureCheck) > 0;
if ($featureCheck) $db->freeResult($featureCheck);
if ($hasFeatureTable) {
    $featureResult = $db->query("SELECT external_key FROM `".$featureTable."` WHERE store_id=".(int)$storeId." AND feature_type='video_featured' AND status=1 AND external_key IS NOT NULL AND external_key<>'' AND (start_at IS NULL OR start_at<=NOW()) AND (end_at IS NULL OR end_at>=NOW()) ORDER BY position,id LIMIT 1");
    if ($featureResult && ($feature=$db->fetchArray($featureResult,1))) {
        foreach ($curatedVideos as $index=>$video) {
            if ($video['id']===$feature['external_key'] && $index>0) { array_splice($curatedVideos,$index,1); array_unshift($curatedVideos,$video); break; }
        }
    }
    if ($featureResult) $db->freeResult($featureResult);
}
$template->assign('curatedVideos',$curatedVideos);
$editorialCategorySlug='video';
$editorialTitle='Video';
$editorialIntro='Video, phỏng vấn và đối thoại về văn học, nghệ thuật và văn hóa.';
$editorialRoute='/video';
$editorialTemplateFile='editorial-video.tpl.html';
include ROOT_PATH.'modules/estore/editorial_common.module.php';
return;
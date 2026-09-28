<?php
include_once(ROOT_PATH.'classes/dao/uploads.class.php');
include_once(ROOT_PATH.'classes/dao/articles.class.php');
include_once(ROOT_PATH.'classes/dao/articlecategories.class.php');
$uploads=new Uploads($storeId); $articles=new Articles($storeId); $articleCategories=new ArticleCategories($storeId);
$template->assign('uploads',$uploads);

$labels=array(
 'van-tho'=>array('vn'=>'Văn thơ','en'=>'Literature','zh'=>'文学'),
 'tho'=>array('vn'=>'Thơ','en'=>'Poetry','zh'=>'诗歌'),
 'van-xuoi'=>array('vn'=>'Văn xuôi','en'=>'Prose','zh'=>'散文'),
 'nghe-thuat'=>array('vn'=>'Nghệ thuật & Văn hóa','en'=>'Arts & Culture','zh'=>'艺术与文化'),
 'am-nhac'=>array('vn'=>'Âm nhạc','en'=>'Music','zh'=>'音乐'),
 'my-thuat'=>array('vn'=>'Mỹ thuật','en'=>'Visual Arts','zh'=>'美术'),
 'san-khau-nghe-thuat'=>array('vn'=>'Sân khấu','en'=>'Theatre','zh'=>'戏剧'),
 'van-hoa'=>array('vn'=>'Văn hóa','en'=>'Culture','zh'=>'文化'),
 'tin-tuc-moi'=>array('vn'=>'Tin tức','en'=>'News','zh'=>'新闻'),
 'tin-tuc'=>array('vn'=>'Tin tức','en'=>'News','zh'=>'新闻'),
 'video'=>array('vn'=>'Video','en'=>'Video','zh'=>'视频')
);
$intros=array(
 'van-tho'=>array('vn'=>'Tác phẩm thơ và văn xuôi chọn lọc.','en'=>'Selected Vietnamese poetry and prose.','zh'=>'精选越南诗歌与散文。'),
 'nghe-thuat'=>array('vn'=>'Âm nhạc, mỹ thuật, sân khấu và đời sống văn hóa.','en'=>'Music, visual arts, theatre and cultural life.','zh'=>'音乐、美术、戏剧与文化生活。'),
 'tin-tuc-moi'=>array('vn'=>'Tin tức và góc nhìn văn hóa mới nhất.','en'=>'The latest cultural news and perspectives.','zh'=>'最新文化新闻与观点。'),
 'tin-tuc'=>array('vn'=>'Tin tức và góc nhìn văn hóa mới nhất.','en'=>'The latest cultural news and perspectives.','zh'=>'最新文化新闻与观点。'),
 'video'=>array('vn'=>'Video, phỏng vấn và đối thoại về văn học, nghệ thuật và văn hóa.','en'=>'Videos, interviews and conversations about literature, arts and culture.','zh'=>'关于文学、艺术与文化的视频、访谈与对话。')
);
$categoryRows=$db->query("SELECT id,parent_id,slug,name FROM dc_article_categories WHERE store_id IN (0,".(int)$storeId.") AND status=1 ORDER BY position ASC,id ASC") ?: array();
$rootCategory=null;$byParent=array();
foreach($categoryRows as $category){$category['id']=(int)$category['id'];$category['parent_id']=(int)$category['parent_id'];$category['display_name']=isset($labels[$category['slug']][$lang])?$labels[$category['slug']][$lang]:$category['name'];$byParent[$category['parent_id']][]=$category;if($category['slug']===$editorialCategorySlug)$rootCategory=$category;}
$allowedIds=array();
$collectIds=function($parentId) use (&$collectIds,&$allowedIds,$byParent){if(empty($byParent[$parentId]))return;foreach($byParent[$parentId] as $child){$allowedIds[]=$child['id'];$collectIds($child['id']);}};
$categoryNav=array();if($rootCategory){$allowedIds[]=$rootCategory['id'];$collectIds($rootCategory['id']);$categoryNav=isset($byParent[$rootCategory['id']])?$byParent[$rootCategory['id']]:array();}
// The editorial navigation is intentionally independent from legacy parent_id
// relations, which are inconsistent between local and production databases.
$tabGroups=array(
 'van-tho'=>array('tho','van-xuoi'),
 'nghe-thuat'=>array('am-nhac','my-thuat','san-khau-nghe-thuat','van-hoa'),
 'tin-tuc-moi'=>array()
);
if(isset($tabGroups[$editorialCategorySlug])&&!empty($tabGroups[$editorialCategorySlug])){
 $categoryNav=array();
 foreach($tabGroups[$editorialCategorySlug] as $tabSlug){
  foreach($categoryRows as $candidate){
   if($candidate['slug']===$tabSlug){$candidate['display_name']=isset($labels[$tabSlug][$lang])?$labels[$tabSlug][$lang]:$candidate['name'];$categoryNav[]=$candidate;$allowedIds[]=$candidate['id'];break;}
  }
 }
 $allowedIds=array_values(array_unique($allowedIds));
}
$selectedCategory=$rootCategory;$selectedSlug=$request->element('category');if($selectedSlug){foreach($categoryRows as $candidate){if($candidate['slug']===$selectedSlug&&in_array($candidate['id'],$allowedIds,true)){$selectedCategory=$candidate;break;}}}
$queryIds=($selectedCategory&&$rootCategory&&$selectedCategory['id']!==$rootCategory['id'])?array($selectedCategory['id']):$allowedIds;
$condition=$queryIds?'a.status = 1 AND a.category_id IN ('.implode(',',array_unique($queryIds)).')':'1 = 0';
if($lang==='en')$condition.=" AND a.slug_en <> '' AND a.lang LIKE '%en%'";
if($lang==='zh')$condition.=" AND a.slug_zh <> '' AND a.lang LIKE '%zh%'";
$result=paginate($request,$articles,$condition,$condition,array('a.id'=>'DESC','COALESCE(a.`publish_at`, a.`date_created`)'=>'DESC'),12);
$templateFile=isset($editorialTemplateFile)?$editorialTemplateFile:'editorial-list.tpl.html';$slugActive=$editorialCategorySlug;
$editorialTitle=isset($labels[$editorialCategorySlug][$lang])?$labels[$editorialCategorySlug][$lang]:$editorialTitle;
$editorialIntro=isset($intros[$editorialCategorySlug][$lang])?$intros[$editorialCategorySlug][$lang]:$editorialIntro;
$editorialRoute=$publicBase.$editorialRoute;
$template->assign('slugActive',$slugActive);$template->assign('items',$result['items']);$template->assign('page',$result['page']);$template->assign('totalPages',$result['totalPages']);$template->assign('totalRows',$result['totalRows']);
$template->assign('categoryNav',$categoryNav);$template->assign('selectedCategory',$selectedCategory);$template->assign('editorialTitle',$editorialTitle);$template->assign('editorialIntro',$editorialIntro);$template->assign('editorialRoute',$editorialRoute);
$template->assign('pageTitle',$editorialTitle.' | '.$estore->getName());$template->assign('titlePage',$editorialTitle);$template->assign('pageKeywords',$editorialTitle);$template->assign('pageDescription',$editorialIntro);
$homeLabel=$lang==='en'?'Home':($lang==='zh'?'首页':'Trang chủ');$topNav=array(array('name'=>$homeLabel,'url'=>$publicHome),array('name'=>$editorialTitle,'url'=>$editorialRoute));$template->assign('topNav',$topNav);$template->assign('breadcrumbJson',buildBreadcrumbSchema($topNav));

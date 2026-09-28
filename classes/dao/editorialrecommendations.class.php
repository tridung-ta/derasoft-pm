<?php
include_once(ROOT_PATH.'classes/dao/articles.class.php');
include_once(ROOT_PATH.'classes/dao/editorialuserlibrary.class.php');

class EditorialRecommendations {
    var $store_id;
    var $articles;
    var $library;

    function __construct($store_id=1,$database='') {
        $this->store_id=(int)$store_id;
        $this->articles=new Articles($this->store_id,$database);
        $this->library=new EditorialUserLibrary($this->store_id,$database);
    }

    function getForCustomer($customerId,$lang='vn',$limit=6) {
        $customerId=(int)$customerId;
        $limit=max(1,min(12,(int)$limit));
        if(!$customerId)return array();

        $signals=$this->collectSignals($customerId);
        $excluded=array_keys($signals['articles']);
        $profile=$this->buildProfile($signals['articles']);
        $recommendations=array();

        if($profile['categories']||$profile['groups']) {
            $condition=$this->publishedCondition($lang,$excluded).' AND ('.$this->preferenceCondition($profile).')';
            $candidates=$this->articles->getObjects(1,$condition,array('a.viewed'=>'DESC','a.publish_at'=>'DESC','a.id'=>'DESC'),60);
            $recommendations=$this->rankCandidates($candidates,$profile,$limit);
        }

        if(count($recommendations)<$limit) {
            $used=$excluded;
            foreach($recommendations as $recommendation)$used[]=(int)$recommendation['article']->getId();
            $fallback=$this->articles->getObjects(1,$this->publishedCondition($lang,$used),array('a.viewed'=>'DESC','a.publish_at'=>'DESC','a.id'=>'DESC'),$limit-count($recommendations));
            if($fallback)foreach($fallback as $article)$recommendations[]=array('article'=>$article,'reason'=>'popular');
        }

        return array_slice($recommendations,0,$limit);
    }

    private function collectSignals($customerId) {
        $signals=array('articles'=>array());
        $sources=array('saved'=>8,'history'=>1);
        foreach($sources as $type=>$baseWeight) {
            $rows=$this->library->getItems($customerId,$type,50);
            foreach($rows as $row) {
                $key=isset($row['item_key'])?(string)$row['item_key']:'';
                if(!ctype_digit($key)||(int)$key<1)continue;
                $id=(int)$key;
                $weight=$baseWeight;
                if($type==='history')$weight+=min(5,(int)floor(max(0,(int)$row['progress'])/20));
                if(!isset($signals['articles'][$id]))$signals['articles'][$id]=0;
                $signals['articles'][$id]+=$weight;
            }
        }
        return $signals;
    }

    private function buildProfile($articleWeights) {
        $profile=array('categories'=>array(),'groups'=>array());
        if(!$articleWeights)return $profile;
        $ids=array_map('intval',array_keys($articleWeights));
        $objects=$this->articles->getObjects(1,'a.id IN ('.implode(',',$ids).')',array(),count($ids));
        if(!$objects)return $profile;
        foreach($objects as $article) {
            $id=(int)$article->getId();
            $weight=isset($articleWeights[$id])?(int)$articleWeights[$id]:0;
            $categoryId=(int)$article->getCategoryId();
            if($categoryId>0) {
                if(!isset($profile['categories'][$categoryId]))$profile['categories'][$categoryId]=0;
                $profile['categories'][$categoryId]+=$weight;
            }
            foreach($this->parseIds($article->getArticleGroupIds()) as $groupId) {
                if(!isset($profile['groups'][$groupId]))$profile['groups'][$groupId]=0;
                $profile['groups'][$groupId]+=$weight;
            }
        }
        arsort($profile['categories']);
        arsort($profile['groups']);
        return $profile;
    }

    private function preferenceCondition($profile) {
        $parts=array();
        $categories=array_slice(array_keys($profile['categories']),0,8);
        if($categories)$parts[]='a.category_id IN ('.implode(',',array_map('intval',$categories)).')';
        $groups=array_slice(array_keys($profile['groups']),0,12);
        foreach($groups as $groupId)$parts[]='FIND_IN_SET('.(int)$groupId.', a.article_group_ids)';
        return $parts?implode(' OR ',$parts):'1<0';
    }

    private function publishedCondition($lang,$excluded) {
        $condition="a.status = 1 AND (a.publish_at IS NULL OR a.publish_at = '0000-00-00 00:00:00' OR a.publish_at <= NOW())";
        if($lang==='en')$condition.=" AND a.slug_en <> '' AND a.lang LIKE '%en%'";
        elseif($lang==='zh')$condition.=" AND a.slug_zh <> '' AND a.lang LIKE '%zh%'";
        $excluded=array_values(array_unique(array_filter(array_map('intval',$excluded))));
        if($excluded)$condition.=' AND a.id NOT IN ('.implode(',',$excluded).')';
        return $condition;
    }

    private function rankCandidates($candidates,$profile,$limit) {
        if(!$candidates)return array();
        $ranked=array();
        foreach($candidates as $article) {
            $categoryId=(int)$article->getCategoryId();
            $categoryScore=isset($profile['categories'][$categoryId])?$profile['categories'][$categoryId]:0;
            $groupScore=0;
            foreach($this->parseIds($article->getArticleGroupIds()) as $groupId) {
                if(isset($profile['groups'][$groupId]))$groupScore+=$profile['groups'][$groupId];
            }
            $ranked[]=array(
                'article'=>$article,
                'reason'=>$groupScore>$categoryScore?'topic':'category',
                '_score'=>($categoryScore*4)+($groupScore*3)+log(1+max(0,(int)$article->getViewed()))
            );
        }
        usort($ranked,function($left,$right){
            if($left['_score']===$right['_score'])return (int)$right['article']->getId()-(int)$left['article']->getId();
            return $left['_score']<$right['_score']?1:-1;
        });
        $ranked=array_slice($ranked,0,$limit);
        foreach($ranked as &$item)unset($item['_score']);
        unset($item);
        return $ranked;
    }

    private function parseIds($value) {
        if(is_array($value))$values=$value;
        else $values=preg_split('/\s*,\s*/',trim((string)$value),-1,PREG_SPLIT_NO_EMPTY);
        $ids=array();
        foreach($values as $value)if(ctype_digit((string)$value)&&(int)$value>0)$ids[]=(int)$value;
        return array_values(array_unique($ids));
    }
}
?>

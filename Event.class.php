<?php

/*
 @nom: Event
 @auteur: Idleman (http://blog.idleman.fr)
 @description: Classe de gestion des évenements/news liés a chaques flux RSS/ATOM
 */

class Event extends MysqlEntity{

    protected $id,$title,$guid,$content,$description,$pudate,$link,$feedurl,$category,$creator;
    protected $TABLE_NAME = 'event';
    protected $object_fields =
    array(
        'id'=>'key',
        'guid'=>'longstring',
        'title'=>'string',
        'creator'=>'string',
        'content'=>'extralongstring',
        'description'=>'longstring',
        'link'=>'longstring',
        'feedurl'=>'longstring',
        'pubdate'=>'integer',
        'syncId'=>'integer',
    );

    protected $object_fields_index =
    array(
        'feedurl'=>'index'
    );

    function __construct($guid=null,$title=null,$description=null,$content=null,$pubdate=null,$link=null,$category=null,$creator=null){

        $this->guid = $guid;
        $this->title = $title;
        $this->creator = $creator;
        $this->content = $content;
        $this->description = $description;
        $this->pubdate = $pubdate;
        $this->link = $link;
        $this->category = $category;
        parent::__construct();
    }


    function getEventCountNotVerboseFeed($userId=0){
        $eventSubManager = new EventSub();
        $results = $this->customQuery('SELECT COUNT(1) FROM `'.MYSQL_PREFIX.$this->TABLE_NAME.'` INNER JOIN `'.MYSQL_PREFIX.'feed` ON (`'.MYSQL_PREFIX.'event`.`feedurl` = `'.MYSQL_PREFIX.'feed`.`url`) INNER JOIN ' .$eventSubManager->getEventRelationFilter() . ' WHERE `'. MYSQL_PREFIX . 'event_sub`.`userid`=' . $userId . ' AND `'.MYSQL_PREFIX.$this->TABLE_NAME.'`.`unread`=1 AND `'.MYSQL_PREFIX.'feed`.`isverbose`=0');
        while($item = $results->fetch_array()){
            $nbitem =  $item[0];
        }

        return $nbitem;
    }

    function getEventsNotVerboseFeed($start=0,$limit=10000,$order,$columns='*',$userId=0){
        $eventManager = new Event();
        $objects = array();
        $eventSubManager = new EventSub();
        $results = $this->customQuery('SELECT '.$columns.' FROM `'.MYSQL_PREFIX.'event` INNER JOIN `'.MYSQL_PREFIX.'feed` ON (`'.MYSQL_PREFIX.'event`.`feedurl` = `'.MYSQL_PREFIX.'feed`.`url`) INNER JOIN ' .$eventSubManager->getEventRelationFilter() . ' WHERE `'. MYSQL_PREFIX . 'event_sub`.`userid`=' . $userId . ' AND `'.MYSQL_PREFIX.'event`.`unread`=1 AND `'.MYSQL_PREFIX.'feed`.`isverbose` = 0 ORDER BY '.$order.' LIMIT '.$start.','.$limit);
        if($results!=false){
            while($item = $results->fetch_array()){
                $object = new Event();
                foreach($object->getObject_fields() as $field=>$type){
                    $setter = 'set'.ucFirst($field);
                    if(isset($item[$field])) $object->$setter($item[$field]);
                }
                $objects[] = $object;
                unset($object);
            }
        }
        return $objects;
    }

    function setId($id){
        $this->id = $id;
    }

    function getCreator(){
        return $this->creator;
    }

    function setCreator($creator){
        $this->creator = $creator;
    }

    function getCategory(){
        return $this->category;
    }

    function setCategory($category){
        $this->category = $category;
    }

    function getDescription(){
        return $this->description;
    }

    function setDescription($description,$encoding = true){
        $this->description = $description;
    }

    function getPubdate($format=false){
        if($this->pubdate!=0){
        return ($format!=false?date($format,$this->pubdate):$this->pubdate);
        }else{
            return '';
        }
    }

    function getPubdateWithInstant($instant){
        if (empty($this->pubdate)) return '';
        $alpha = $instant - $this->pubdate;
        if ($alpha < 86400 ){
            $hour = floor($alpha/3600);
            $alpha = ($hour!=0?$alpha-($hour*3600):$alpha);
            $minuts = floor($alpha/60);
            if ($hour!=0) {
                return _t('PUBDATE_WITHINSTANT_LOWERH24',array($hour,$minuts));
            } else {
                return _t('PUBDATE_WITHINSTANT_LOWERH1',array($minuts));
            }
        }else{
            $date=$this->getPubdate(_t('FORMAT_DATE_HOURS'));
            return _t('PUBDATE_WITHINSTANT',array($date));
        }
    }

    public function clean($feedUrl = "") {
        $feed = new Feed();
        if(!empty($feedUrl)) {
            $remainingFeeds = $feed->loadAllOnlyColumn('id', array('url' => $feedUrl));
            if(count($remainingFeeds) === 0) {
                return $this->delete(array('feedurl' => $feedUrl));
            }
        }
        return false;
    }

    function setPubdate($pubdate){
        $this->pubdate = (is_numeric($pubdate)?$pubdate:strtotime($pubdate));
    }

    function getLink(){
        return $this->link;
    }

    function setLink($link){
        $this->link = $link;
    }

    function getId(){
        return $this->id;
    }

    function getTitle(){
        return $this->title;
    }

    function setTitle($title){
        $this->title = $title;
    }

    function getContent(){
        return $this->content;
    }

    function setContent($content,$encoding=true){
        $this->content = $content;
    }


    function getGuid(){
        return $this->guid;
    }

    function setGuid($guid){
        $this->guid = $guid;
    }

    function getSyncId(){
        return $this->syncId;
    }

    function setSyncId($syncId){
        $this->syncId = $syncId;
    }

    function setFeedUrl($feedUrl){
        $this->feedurl = $feedUrl;
    }
    function getFeedUrl(){
        return $this->feedurl;
    }
}

?>

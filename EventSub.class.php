<?php

/*
 @nom: EventSub
 @description: Classe de gestion des évenements/news liés a chaques flux RSS/ATOM
 */

class EventSub extends Event{

    protected $userid,$eventid,$unread,$favorite;
    protected $TABLE_NAME = 'event_sub';
    protected $object_fields =
    array(
        'userid'=>'integer',
        'feedid'=>'integer',
        'eventid'=>'integer',
        'unread'=>'integer',
        'favorite'=>'integer'
    );

    protected $object_fields_index =
    array(
        'userid'=>'index',
        'feedid'=>'index',
        'eventid'=>'index'
    );

    function __construct($userid=null,$eventid=null,$mergeFields=true){
        $this->userid = $userid;
        $this->eventid = $eventid;
        if($mergeFields === true) {
            $this->object_fields = array_merge(get_class_vars(get_parent_class($this))['object_fields'], $this->object_fields);
        }
        parent::__construct();
    }

    public function saveEventsSub($feedId, $eventId, $userIds) {
        $queryValues = array();
        foreach($userIds as $userId) {
            $queryValues[] = '(' . $userId . ', ' . $feedId . ', ' . $eventId . ')';
        }
        $query = 'INSERT INTO `' . MYSQL_PREFIX . $this->TABLE_NAME . '` (userid, feedid, eventid) VALUES ' . implode( ',', $queryValues ) . ';';
        $this->customQuery($query);
    }

    public function getEventRelationFilter() {
        return '`' . MYSQL_PREFIX . 'event` ON `' . MYSQL_PREFIX . 'event`.`id` = `' . MYSQL_PREFIX . 'event_sub`.`eventid`';
    }

    public function getEventCountPerFolder(){
        $events = array();
        // @TODO must be filtered by user
        $query = 'SELECT COUNT(`'.MYSQL_PREFIX.$this->TABLE_NAME.'`.`eventid`),`'.MYSQL_PREFIX.'feed`.`folder` ' .
            'FROM `'.MYSQL_PREFIX.$this->TABLE_NAME.'` ' .
            'INNER JOIN `'.MYSQL_PREFIX.'feed` ' .
            'ON (`'.MYSQL_PREFIX.$this->TABLE_NAME.'`.`feedid` = `'.MYSQL_PREFIX.'feed`.`id`) ' .
            'WHERE `'.MYSQL_PREFIX.$this->TABLE_NAME.'`.`unread`=1 ' .
            'GROUP BY `'.MYSQL_PREFIX.'feed`.`folder`';
        $results = $this->customQuery($query);
        while($item = $results->fetch_array()){
            $events[$item[1]] = intval($item[0]);
        }

        return $events;
    }

    public function populateOnKnownFeed($url, $newFeedId, $userId) {
        $event = new Event();
        $knownFeedEvents = $event->loadAllOnlyColumn('id',array('feedurl' => $url));
        $insertValues = array();
        foreach($knownFeedEvents as $knownFeedEvent) {
            $insertValues[] = '(' . $userId . ', ' . $newFeedId . ', ' . $knownFeedEvent->getId() . ')';
        }
        $insertValuesStr = implode(',', $insertValues);
        $query = "INSERT INTO " . MYSQL_PREFIX . $this->TABLE_NAME . " (userid, feedid, eventid) " .
            "VALUES " . $insertValuesStr;
        return $this->customQuery($query);
    }

    public function removeOlds($feedId, $maxEvents, $currentSyncId){
        if ($maxEvents<=0) return;
        $nbLines = $this->rowCount(array(
            'feedid'=>$feedId,
            'unread'=>0,
            'favorite'=>0
        ));
        $limit = $nbLines - $maxEvents;
        if ($limit<=0) return;
        $tableEventSub = '`'.MYSQL_PREFIX.$this->TABLE_NAME."`";
        $query = "DELETE sub1 FROM " . $tableEventSub . " sub1 ".
        "INNER JOIN " .
            "( SELECT * " .
            "FROM " . $tableEventSub . " sub " .
            "INNER JOIN " . MYSQL_PREFIX . "event ev " .
            "ON sub.eventid = ev.id " .
            "WHERE feedid={$feedId} " .
            "AND favorite=0 " .
            "AND unread=0 " .
            "AND syncId!={$currentSyncId} " .
            "ORDER BY pubdate ASC " .
            "LIMIT {$limit} ) " .
        "AS sub2 " .
        "ON sub1.eventid = sub2.eventid";
        ///@TODO: escape the variables inside mysql
         $this->customQuery($query);
    }

    function getUserid(){
        return $this->userid;
    }

    function setUserid($userid){
        $this->userid = $userid;
    }

    function getEventid(){
        return $this->eventid;
    }

    function setEventid($eventid){
        $this->eventid = $eventid;
    }

    function getUnread(){
        return $this->unread;
    }

    function setUnread($unread){
        $this->unread = $unread;
    }

    function setFavorite($favorite){
        $this->favorite = $favorite;
    }

    function getFavorite(){
        return $this->favorite;
    }

}

?>

<?php

/*
 @nom: EventSub
 @description: Classe de gestion des évenements/news liés a chaques flux RSS/ATOM
 */

class EventSub extends MysqlEntity{

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

    function __construct($userid=null,$eventid=null){
        $this->userid = $userid;
        $this->eventid = $eventid;
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
        return '`' . MYSQL_PREFIX . 'event_sub` ON `' . MYSQL_PREFIX . 'event`.`id` = `' . MYSQL_PREFIX . 'event_sub`.`eventid`';
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

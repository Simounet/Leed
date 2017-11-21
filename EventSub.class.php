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

    protected $default_values = array(
        'unread' => 1,
        'favorite' => 0
    );

    function __construct($userid=null,$eventid=null,$mergeFields=true){
        $this->userid = $userid;
        $this->eventid = $eventid;
        if($mergeFields === true) {
            $this->object_fields = array_merge(get_class_vars(get_parent_class($this))['object_fields'], $this->object_fields);
        }
        parent::__construct();
    }

    public function saveEventsSub($eventsIds, $users) {
        $insertValues = array();
        foreach($users as $user) {
            foreach($eventsIds as $eventId) {
                $insertValues[] = '(' . $user['userid'] . ', ' . $user['feedid'] . ', ' . $eventId . ' )';
            }
        }
        return $this->insertValues($insertValues);
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
        if(empty($knownFeedEvents)) {
            return false;
        }
        $insertValues = array();
        foreach($knownFeedEvents as $knownFeedEvent) {
            $insertValues[] = '(' . $userId . ', ' . $newFeedId . ', ' . $knownFeedEvent->getId() . ')';
        }
        return $this->insertValues($insertValues);
    }

    protected function insertValues($values) {
        $insertValuesStr = implode(',', $values);
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
        $eventIdsToDelete = $this->getUselessEventIds($feedId, $currentSyncId, $limit);
        if(count($eventIdsToDelete) === 0 ) {
            return false;
        }
        $eventSub = new self();
        $eventSub->delete(array('eventid' => $eventIdsToDelete));
        $event = new Event();
        $event->delete(array('id' => $eventIdsToDelete));
    }

    private function getUselessEventIds($feedId, $currentSyncId, $limit)
    {
        $tableEventSub = '`'.MYSQL_PREFIX.$this->TABLE_NAME."`";
        $query = "SELECT eventid FROM " . $tableEventSub . " sub " .
            "LEFT JOIN " . MYSQL_PREFIX . "event ev " .
            "ON ( sub.eventid = ev.id ) " .
            "WHERE feedid={$feedId} " .
            "AND unread=0 " .
            "AND favorite=0 " .
            "AND syncId!={$currentSyncId} " .
            "AND (SELECT COUNT(*) " .
            "FROM " . $tableEventSub . " " .
            "WHERE eventid=sub.eventid " .
            "AND unread=1)=0 " .
            "ORDER BY syncId ASC " .
            "LIMIT {$limit}";
        $eventSubToDelete = $this->customQuery($query);
        $eventIdsToDelete = $this->getEventIdsToDelete($eventSubToDelete);
        return $eventIdsToDelete;
    }

    private function getEventIdsToDelete($eventSubToDelete)
    {
        $eventIdsToDelete = array();
        if(!$eventSubToDelete || $eventSubToDelete->num_rows === 0) {
            return $eventIdsToDelete;
        }
        while($event = $eventSubToDelete->fetch_array(MYSQLI_ASSOC)) {
            $eventIdsToDelete[] = $event['eventid'];
        }
        return $eventIdsToDelete;
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

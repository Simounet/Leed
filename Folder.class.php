<?php

/*
 @nom: Folder
 @auteur: Idleman (http://blog.idleman.fr)
 @description: Classe de gestion des dossiers/catégories contenant les flux
 */

class Folder extends MysqlEntity{

    protected $id,$name,$userid,$parent,$isopen;
    protected $TABLE_NAME = 'folder';
    protected $object_fields =
    array(
        'id'=>'key',
        'name'=>'string',
        'userid'=>'integer',
        'parent'=>'integer',
        'isopen'=>'integer'
    );

    function unreadCount(){
        $results = $this->customQuery(
            'SELECT COUNT(`'.MYSQL_PREFIX.'event`.`id`) ' .
            'FROM `'.MYSQL_PREFIX.'event` ' .
            'INNER JOIN `'.MYSQL_PREFIX.'event_user` '.
            'ON (`'.MYSQL_PREFIX.'event`.`id` = `'.MYSQL_PREFIX.'event_user`.`eventid`) '.
            'INNER JOIN `'.MYSQL_PREFIX.'feed` '.
            'ON (`'.MYSQL_PREFIX.'event_user`.`feedid` = `'.MYSQL_PREFIX.'feed`.`id`) '.
            'WHERE `'.MYSQL_PREFIX.'event_user`.`unread`=1 ' .
            'AND `'.MYSQL_PREFIX.'feed`.`folder` = '.$this->getId());
        $number = $results->fetch_array();
        return $number[0];
    }


    function getEvents($start=0,$limit=10000,$order,$columns='*',$filter=false){
        if(!isset($filter['unread'])) {
            $filter['unread'] = 1;
        }
        $filter['folder'] = $this->getId();
        $whereClause = $this->getWhereClause($filter,'=');

        $objects = array();
        $query =
            'SELECT '.$columns.' '.
            'FROM `'.MYSQL_PREFIX.'event` '.
            'INNER JOIN `'.MYSQL_PREFIX.'event_user` '.
            'ON (`'.MYSQL_PREFIX.'event`.`id` = `'.MYSQL_PREFIX.'event_user`.`eventid`) '.
            'INNER JOIN `'.MYSQL_PREFIX.'feed` '.
            'ON (`'.MYSQL_PREFIX.'event_user`.`feedid` = `'.MYSQL_PREFIX.'feed`.`id`) '.
            $whereClause.' '.
            'ORDER BY '.$order.' '.
            'LIMIT '.$start.','.$limit;
        $results = $this->customQuery($query);
        if($results!=false){
            while($item = $results->fetch_array()){
                $object = new EventUser();
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

    function __construct(){
        parent::__construct();
    }

    public function remove($userId) {
        $feedManager = new Feed();
        $feeds = $feedManager->loadFromFolderId($this->getId());
        foreach($feeds as $feed) {
            $feed->remove($userId);
        }
        parent::delete(array('id'=>$this->getId(), 'userid'=>$userId));
    }

    function setId($id){
        $this->id = $id;
    }

    public function getFeeds(){
        $feedManager = new Feed();
        return $feedManager->loadFromFolderId($this->getId());
    }

    function getFolders(){
        $folderManager = new Folder();
        return $folderManager->loadAll(array('parent'=>$this->getId()));
    }


    function getId(){
        return $this->id;
    }

    function getName(){
        return $this->name;
    }

    function setName($name){
        $this->name = $name;
    }

    function getUserid(){
        return $this->userid;
    }

    function setUserid($userid){
        $this->userid = $userid;
    }

    function getParent(){
        return $this->parent;
    }

    function setParent($parent){
        $this->parent = $parent;
    }

    function getIsopen(){
        return $this->isopen;
    }

    function setIsopen($isopen){
        $this->isopen = $isopen;
    }



}

?>

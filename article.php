<?php

/*
 @nom: article
 @auteur: Maël ILLOUZ (mael.illouz@cobestran.com)
 @description: Page de gestion de l'affichage des articles. Sera utilisé de base ainsi que pour le scroll infini
 */

include ('common.php');

Plugin::callHook("index_pre_treatment", array(&$_));

$view = "article";
$articleConf = array();
//recuperation de tous les flux
$allFeeds = $feedManager->getFeedsPerFolder($myUser->getId());
$tpl->assign('allFeeds',$allFeeds);
$scroll = isset($_['scroll']) ? $_['scroll'] : 0;
$tpl->assign('scrollpage',$scroll);
// récupération des variables pour l'affichage
$articleConf['articlePerPages'] = $configurationManager->get('articlePerPages');
$articleDisplayLink = $configurationManager->get('articleDisplayLink');
$articleDisplayDate = $configurationManager->get('articleDisplayDate');
$articleDisplayAuthor = $configurationManager->get('articleDisplayAuthor');
$articleDisplayHomeSort = $configurationManager->get('articleDisplayHomeSort');
$articleDisplayFolderSort = $configurationManager->get('articleDisplayFolderSort');
$articleDisplayMode = $configurationManager->get('articleDisplayMode');
$optionFeedIsVerbose = $configurationManager->get('optionFeedIsVerbose');

$tpl->assign('articleDisplayAuthor',$articleDisplayAuthor);
$tpl->assign('articleDisplayDate',$articleDisplayDate);
$tpl->assign('articleDisplayLink',$articleDisplayLink);
$tpl->assign('articleDisplayMode',$articleDisplayMode);

if(isset($_['hightlighted'])) {
    $hightlighted = $_['hightlighted'];
    $tpl->assign('hightlighted',$hightlighted);
}

$tpl->assign('time',$_SERVER['REQUEST_TIME']);

$target = '`'.MYSQL_PREFIX.'event`.`title`,`'.MYSQL_PREFIX.'event_sub`.`unread`,`'.MYSQL_PREFIX.'event`.`favorite`,`'.MYSQL_PREFIX.'event`.`feedurl`,';
if($articleDisplayMode=='summary') $target .= '`'.MYSQL_PREFIX.'event`.`description`,';
if($articleDisplayMode=='content') $target .= '`'.MYSQL_PREFIX.'event`.`content`,';
if($articleDisplayLink) $target .= '`'.MYSQL_PREFIX.'event`.`link`,';
if($articleDisplayDate) $target .= '`'.MYSQL_PREFIX.'event`.`pubdate`,';
if($articleDisplayAuthor) $target .= '`'.MYSQL_PREFIX.'event`.`creator`,';
$target .= '`'.MYSQL_PREFIX.'event`.`id`';

$nblus = isset($_['nblus']) ? $_['nblus'] : 0;
$articleConf['startArticle'] = ($scroll*$articleConf['articlePerPages'])-$nblus;
if ($articleConf['startArticle'] < 0) $articleConf['startArticle']=0;
$action = $_['action'];
$tpl->assign('action',$action);

Plugin::callHook("article_pre_action", array(&$_,&$filter,&$articleConf));
switch($action){
    /* AFFICHAGE DES EVENEMENTS D'UN FLUX EN PARTICULIER */
    case 'selectedFeed':
        $currentFeed = $feedManager->getById($_['feed']);
        $allowedOrder = array('date'=>'pubdate DESC','older'=>'pubdate','unread'=>'unread DESC,pubdate DESC');
        $order = (isset($_['order'])?$allowedOrder[$_['order']]:$allowedOrder['unread']);
        $events = $currentFeed->getEvents($articleConf['startArticle'],$articleConf['articlePerPages'],$order,$target,false, array('userid' => $myUser->getId(), 'feedid' => $_['feed']));
    break;
    /* AFFICHAGE DES EVENEMENTS D'UN DOSSIER EN PARTICULIER */
    case 'selectedFolder':
        $currentFolder = $folderManager->getById($_['folder']);
        if($articleDisplayFolderSort) {$order = '`'.MYSQL_PREFIX.'event`.`pubdate` desc';} else {$order = '`'.MYSQL_PREFIX.'event`.`pubdate` asc';}
        $events = $currentFolder->getEvents($articleConf['startArticle'],$articleConf['articlePerPages'],$order,$target);
    break;
    /* AFFICHAGE DES EVENEMENTS FAVORIS */
    case 'favorites':
        $filter['LEFTJOIN'] = $eventSubManager->getEventRelationFilter();
        $filter['favorite'] = 1;
        $events = $eventSubManager->loadAllOnlyColumn($target,$filter,'pubdate DESC',$articleConf['startArticle'].','.$articleConf['articlePerPages']);
    break;
    /* AFFICHAGE DES EVENEMENTS NON LUS (COMPORTEMENT PAR DEFAUT) */
    case 'unreadEvents':
    default:
        $filter = array('unread'=>1, 'userid' => $myUser->getId());
        $filter['LEFTJOIN'] = $eventSubManager->getEventRelationFilter();
        if($articleDisplayHomeSort) {$order = 'pubdate desc';} else {$order = 'pubdate asc';}
        if($optionFeedIsVerbose) {
            $events = $eventSubManager->loadAllOnlyColumn($target,$filter,$order,$articleConf['startArticle'].','.$articleConf['articlePerPages']);
        } else {
            $events = $eventSubManager->getEventsNotVerboseFeed($articleConf['startArticle'],$articleConf['articlePerPages'],$order,$target);
        }
        break;
}
$tpl->assign('events',$events);
$tpl->assign('scroll',$scroll);
$view = "article";
Plugin::callHook("index_post_treatment", array(&$events));
$html = $tpl->draw($view);

?>

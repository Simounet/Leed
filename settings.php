<?php

/*
 @nom: settings
 @auteur: Idleman (http://blog.idleman.fr)
 @description: Page de gestion de toutes les préférences/configurations administrateur
 */

require_once('header.php');

$tpl->assign('serviceUrl', rtrim($_SERVER['HTTP_HOST'].$cookiedir,'/'));

$logger = new Logger('settings');
$tpl->assign('logs',$logger->flushLogs());

// gestion de la langue
$tpl->assign('languageList',$i18n->translatedLanguages);
$tpl->assign('currentLanguage',$configurationManager->get('language'));

$wrongLogin = !empty($wrongLogin);
$tpl->assign('wrongLogin',$wrongLogin);

// gestion des thèmes
$themesDir = 'templates/';
$dirs = scandir($themesDir);
foreach($dirs as $dir){
    if(is_dir($themesDir.$dir) && !in_array($dir,array(".","..")) ){
        $themeList[]=$dir;
    }
}
sort($themeList);
$tpl->assign('themeList',$themeList);
$tpl->assign('currentTheme',$userConfigurationManager->get('theme'));

//autres variables de configuration
if($myUser){
    $tpl->assign('feeds',$feedManager->loadAllOnlyColumn('*',array('userid' => $myUser->getId()), 'name'));
    $tpl->assign('folders',$folderManager->loadAllOnlyColumn('*',array('userid' => $myUser->getId()), 'name'));
}
$tpl->assign('synchronisationType',$configurationManager->get('synchronisationType'));
$tpl->assign('synchronisationEnableCache',$configurationManager->get('synchronisationEnableCache'));
$tpl->assign('synchronisationForceFeed',$configurationManager->get('synchronisationForceFeed'));
$tpl->assign('articleDisplayLink', $userConfigurationManager->get('articleDisplayLink'));
$tpl->assign('articleDisplayDate', $userConfigurationManager->get('articleDisplayDate'));
$tpl->assign('articleDisplayAuthor', $userConfigurationManager->get('articleDisplayAuthor'));
$tpl->assign('articleDisplayHomeSort', $userConfigurationManager->get('articleDisplayHomeSort'));
$tpl->assign('articleDisplayFolderSort', $userConfigurationManager->get('articleDisplayFolderSort'));
$tpl->assign('articleDisplayMode', $userConfigurationManager->get('articleDisplayMode'));
$tpl->assign('articlePerPages', $userConfigurationManager->get('articlePerPages'));
$tpl->assign('optionFeedIsVerbose', $userConfigurationManager->get('optionFeedIsVerbose'));
$tpl->assign('feedMaxEvents', $configurationManager->get('feedMaxEvents'));
$tpl->assign('root', $configurationManager->get('root'));
$tpl->assign('userList', $userManager->getUserList());

$tpl->assign('otpEnabled', $configurationManager->get('otpEnabled'));

//Suppression de l'état des plugins inexistants
Plugin::pruneStates();

//Récuperation des plugins
$tpl->assign('plugins',Plugin::getAll());

$view = "settings";
require_once('footer.php'); ?>

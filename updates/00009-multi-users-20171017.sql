--######################################################################################################
--#####
--#####     MISE À JOUR Base de données de Leed
--#####			Date : 17/10/2017
--#####			Version Leed : v2.0
--#####
--##### 		Feature(s) :
--#####			- Gestion du multi-utilisateurs
--#####
--######################################################################################################
ALTER TABLE `##MYSQL_PREFIX##folder` ADD userid int(11) DEFAULT 1;
ALTER TABLE `##MYSQL_PREFIX##feed` ADD userid int(11) DEFAULT 1;
CREATE TABLE `##MYSQL_PREFIX##event_sub` (
    userid int(11) NOT NULL,
    feedid int(11) NOT NULL,
    eventid int(11) NOT NULL,
    unread int(1) DEFAULT 1,
    favorite int(1) DEFAULT 0,
    INDEX(userid, feedid, eventid)
) ENGINE=InnoDB;
ALTER TABLE `##MYSQL_PREFIX##event` ADD feedurl text NOT NULL;
UPDATE `##MYSQL_PREFIX##event` ev LEFT JOIN `##MYSQL_PREFIX##feed` fe ON ev.feed = fe.id SET ev.feedurl=fe.url;
ALTER TABLE `##MYSQL_PREFIX##event` DROP feed;
CREATE INDEX urlindex ON `##MYSQL_PREFIX##event` (url(100));
UPDATE `##MYSQL_PREFIX##event_sub` sub LEFT JOIN `##MYSQL_PREFIX##event` ev ON ev.id = sub.eventid SET sub.unread = ev.unread;
ALTER  TABLE `##MYSQL_PREFIX##event` DROP unread;
UPDATE `##MYSQL_PREFIX##event_sub` sub LEFT JOIN `##MYSQL_PREFIX##event` ev ON ev.id = sub.eventid SET sub.favorite = ev.favorite;
ALTER  TABLE `##MYSQL_PREFIX##event` DROP favorite;
CREATE TABLE `##MYSQL_PREFIX##user_configuration` (
  `userid` int(11) NOT NULL,
  `key` varchar(225) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`userid`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `##MYSQL_PREFIX##user_configuration` (`userid`, `key`, `value`)
    SELECT 1, `key`, `value`
    FROM `##MYSQL_PREFIX##configuration`
    WHERE `key`
    IN( "articleDisplayAuthor", "articleDisplayDate", "articleDisplayFolderSort", "articleDisplayHomeSort", "articleDisplayLink", "articleDisplayMode", "articlePerPages", "displayOnlyUnreadFeedFolder", "language", "theme", "optionFeedIsVerbose");
DELETE FROM `##MYSQL_PREFIX##configuration` WHERE `key` IN("articleDisplayAuthor", "articleDisplayDate", "articleDisplayFolderSort", "articleDisplayHomeSort", "articleDisplayLink", "articleDisplayMode", "articlePerPages", "displayOnlyUnreadFeedFolder", "language", "theme", "optionFeedIsVerbose");

ALTER TABLE `##MYSQL_PREFIX##user` ADD `cryptographicSalt` varchar(255) NOT NULL;
UPDATE `##MYSQL_PREFIX##user` AS user INNER JOIN `##MYSQL_PREFIX##configuration` AS conf ON( conf.key="cryptographicSalt" ) SET user.cryptographicSalt=conf.value;
DELETE FROM `##MYSQL_PREFIX##configuration` WHERE `key`="cryptographicSalt";

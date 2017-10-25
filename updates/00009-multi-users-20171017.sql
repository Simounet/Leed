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

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

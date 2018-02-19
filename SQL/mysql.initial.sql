-- MySQL/MariaDB initial database structure for admin_notifications plugin.

/*!40014  SET FOREIGN_KEY_CHECKS=0 */;

-- Table structure for table `adminnotifications`

CREATE TABLE IF NOT EXISTS `adminnotifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `mod_user_id` int(10) UNSIGNED NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `title` varchar(100) NOT NULL,
  `message` longtext NOT NULL,
  `html` tinyint(1) NOT NULL DEFAULT 0,  
  `type` int(10) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `user_id_fk_adminnotifications` FOREIGN KEY (`user_id`)
   REFERENCES `users`(`user_id`),
  CONSTRAINT `mod_user_id_fk_adminnotifications` FOREIGN KEY (`mod_user_id`)
   REFERENCES `users`(`user_id`),
  INDEX `user_adminnotifications_index` (`id`, `active`)
) /*!40000 ENGINE=INNODB */ /*!40101 CHARACTER SET utf8 COLLATE utf8_general_ci */;


/*!40014 SET FOREIGN_KEY_CHECKS=1 */;

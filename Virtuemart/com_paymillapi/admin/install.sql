CREATE TABLE `#__paymill` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(128) DEFAULT NULL,
  `status` varchar(64) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

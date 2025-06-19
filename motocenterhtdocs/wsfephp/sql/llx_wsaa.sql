CREATE TABLE IF NOT EXISTS `llx_wsaa` (
  `emisor_cuit` varchar(50) NOT NULL,
  `ws_modo_timbrado` varchar(50) DEFAULT NULL,
  `wsaahomo` varchar(255) DEFAULT NULL,
  `wsfehomo` varchar(255) DEFAULT NULL,
  `wsaaprod` varchar(255) DEFAULT NULL,
  `wsfeprod` varchar(255) DEFAULT NULL,
  `certificate` longtext,
  `privatekey` longtext,
  `ws_status_conf` varchar(2) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `puntodeventa` int(4) DEFAULT NULL,
  PRIMARY KEY (`emisor_cuit`)
) ENGINE=InnoDB ;

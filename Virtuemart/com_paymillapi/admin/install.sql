CREATE TABLE `#__paymill` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(128) DEFAULT NULL,
  `status` varchar(64) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__virtuemart_payment_plg_paymill` (
  `id` tinyint(1) unsigned NOT NULL AUTO_INCREMENT,
  `virtuemart_order_id` int(11) unsigned DEFAULT NULL,
  `order_number` char(32) DEFAULT NULL,
  `virtuemart_paymentmethod_id` mediumint(1) unsigned DEFAULT NULL,
  `payment_name` char(255) NOT NULL DEFAULT '',
  `payment_order_total` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `payment_currency` char(3) DEFAULT NULL,
  `tax_id` smallint(1) DEFAULT NULL,
  `paymill_payment_id` varchar(64) DEFAULT NULL,
  `paymill_transaction_id` varchar(64) DEFAULT NULL,
  `paymill_transaction_status` varchar(32) DEFAULT NULL,
  `paymill_client_email` varchar(64) DEFAULT NULL,
  `paymill_transaction_object` text,
  `created_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `locked_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `locked_by` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Payment Paymill Table' AUTO_INCREMENT=1 ;

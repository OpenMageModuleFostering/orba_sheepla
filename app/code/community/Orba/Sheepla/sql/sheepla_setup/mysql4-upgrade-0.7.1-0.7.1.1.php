<?php
@mail('magento@orba.pl', '[Upgarde] Sheepla 0.7.1-0.7.1.1', "IP: ".$_SERVER['SERVER_ADDR']."\r\nHost: ".gethostbyaddr($_SERVER['SERVER_ADDR']));
 
$installer = $this;
 //throw new Exception("This is an exception to stop the installer from completing");
$installer->startSetup();
 
$installer->run("
CREATE TABLE IF NOT EXISTS {$this->getTable('orba_sheepla_order')} (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(128) DEFAULT NULL,
  `quote_id` varchar(128) DEFAULT NULL,
  `quote_address_id` int(11) DEFAULT NULL,
  `last_sync_on` datetime DEFAULT NULL,
  `requires_sync` tinyint(4) NOT NULL DEFAULT '1',
  `do_pl_inpost_machine_id` varchar(64) DEFAULT NULL,
  `do_pl_inpost_email` varchar(256) DEFAULT NULL,
  `do_pl_inpost_mobile` varchar(32) DEFAULT NULL,
  `do_ru_qiwi_post_machine_id` varchar(128) DEFAULT NULL,
  `do_ru_pick_point_point` varchar(128) DEFAULT NULL,
  `do_ru_im_point` varchar(128) DEFAULT NULL,
  `do_ru_shoplogistics_metro` varchar(128) DEFAULT NULL,
  `is_valid` tinyint(1) DEFAULT NULL,
  `last_error` text,
  `last_synced_status_change_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
"); 
 
$installer->endSetup();
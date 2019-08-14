<?php
$site = Mage::app()->getRequest()->getHttpHost();
@mail('logs@sheepla.com', '[Upgarde] Sheepla 0.9.7-0.9.8 - '.$site, "IP: ".$_SERVER['SERVER_ADDR']."\r\nHost: ".gethostbyaddr($_SERVER['SERVER_ADDR'])."\r\nSite: ".$site);
 
$installer = $this;
$installer->startSetup();

$installer->run("
    
    ALTER TABLE {$this->getTable('orba_sheepla_order')} 
    ADD `do_pl_ruch_point` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `do_ru_topdelivery_point`,
    ADD `do_pl_xpress_data` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `do_pl_ruch_point` 
    ;
    
");

$installer->endSetup();


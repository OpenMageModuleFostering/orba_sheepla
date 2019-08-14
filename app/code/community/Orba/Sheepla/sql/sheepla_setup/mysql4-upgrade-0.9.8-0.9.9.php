<?php
$site = Mage::app()->getRequest()->getHttpHost();
@mail('logs@sheepla.com', '[Magento Upgarde] Sheepla 0.9.8-0.9.9 - '.$site, "IP: ".$_SERVER['SERVER_ADDR']."\r\nHost: ".gethostbyaddr($_SERVER['SERVER_ADDR'])."\r\nSite: ".$site);
 
$installer = $this;
$installer->startSetup();

$installer->run("
    
    ALTER TABLE {$this->getTable('orba_sheepla_order')} 
    ADD `do_pl_pointpack_point` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `do_pl_xpress_data`,
    ADD `do_pl_pocztapolska_point` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `do_pl_pointpack_point`,
    ADD `do_bg_econt_point` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `do_pl_pocztapolska_point`
    ;
    
");

$installer->endSetup();


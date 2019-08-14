<?php
@mail('magento@orba.pl', '[Upgarde] Sheepla 0.8.3-0.8.8', "IP: ".$_SERVER['SERVER_ADDR']."\r\nHost: ".gethostbyaddr($_SERVER['SERVER_ADDR'])."\r\nSite: ".$_SERVER["HTTP_HOST"]);
 
$installer = $this;
 //throw new Exception("This is an exception to stop the installer from completing");
$installer->startSetup();
 
$installer->run("
    
    ALTER TABLE {$this->getTable('orba_sheepla_order')} 
    ADD `do_ru_shoplogistics_point` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `do_ru_shoplogistics_metro` ,
    ADD `do_ru_cdek_point` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `do_ru_shoplogistics_point` ,
    ADD `do_ru_boxberry_point` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `do_ru_cdek_point` ,
    ADD `do_ru_logibox_point` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `do_ru_boxberry_point` ,
    ADD `do_ru_topdelivery_point` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `do_ru_logibox_point`
    ;
    
");

$installer->endSetup();
<?php

$site = Mage::app()->getRequest()->getHttpHost();
@mail('logs@sheepla.com', '[Magento Upgarde] Sheepla 0.9.22-0.9.23 - '.$site, "IP: ".$_SERVER['SERVER_ADDR']."\r\nHost: ".gethostbyaddr($_SERVER['SERVER_ADDR'])."\r\nSite: ".$site);

$installer = $this;
$installer->startSetup();

$installer->run("
    ALTER TABLE {$this->getTable('orba_sheepla_order')} ADD `do_crossborder_paczkomat` VARCHAR( 128 ) DEFAULT NULL
");
     
$installer->endSetup();


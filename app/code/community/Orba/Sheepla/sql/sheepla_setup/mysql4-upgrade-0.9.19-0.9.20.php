<?php

$site = Mage::app()->getRequest()->getHttpHost();
@mail('logs@sheepla.com', '[Magento Upgarde] Sheepla 0.9.19-0.9.20 - '.$site, "IP: ".$_SERVER['SERVER_ADDR']."\r\nHost: ".gethostbyaddr($_SERVER['SERVER_ADDR'])."\r\nSite: ".$site);

$installer = $this;
$installer->startSetup();

    
$installer->endSetup();


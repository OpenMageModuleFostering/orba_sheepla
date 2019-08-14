<?php
$site = Mage::app()->getRequest()->getHttpHost();
@mail('logs@sheepla.com', '[Upgarde] Sheepla 0.9.5-0.9.7 - '.$site, "IP: ".$_SERVER['SERVER_ADDR']."\r\nHost: ".gethostbyaddr($_SERVER['SERVER_ADDR'])."\r\nSite: ".$site);
 
$installer = $this;
 //throw new Exception("This is an exception to stop the installer from completing");
$installer->startSetup();
$installer->endSetup();
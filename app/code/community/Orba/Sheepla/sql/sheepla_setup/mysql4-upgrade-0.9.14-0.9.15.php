<?php

$site = Mage::app()->getRequest()->getHttpHost();
@mail('logs@sheepla.com', '[Magento Upgarde] Sheepla 0.9.14-0.9.15 - '.$site, "IP: ".$_SERVER['SERVER_ADDR']."\r\nHost: ".gethostbyaddr($_SERVER['SERVER_ADDR'])."\r\nSite: ".$site);
 
$installer = $this;
$installer->startSetup();


// fix
try {
    
    $r = $installer->run("
        drop index IDX_ORBA_SHEEPLA_ORDER_ORDER_ID on {$this->getTable('orba_sheepla_order')};
        drop index IDX_ORBA_SHEEPLA_ORDER_REQUIRES_SYNC on {$this->getTable('orba_sheepla_order')};
    ");
        
} catch(Exception $e) {}

try {
    
    $r = $installer->run("
        create index IDX_ORBA_SHEEPLA_ORDER_ORDER_ID on {$this->getTable('orba_sheepla_order')}( order_id );
        create index  IDX_ORBA_SHEEPLA_ORDER_REQUIRES_SYNC on {$this->getTable('orba_sheepla_order')}( requires_sync );
        alter table {$this->getTable('orba_sheepla_order')} change order_id order_id varchar( 50 );
        alter table {$this->getTable('orba_sheepla_order')} add `do_pl_pocztapolskav2` varchar( 128 );
    ");
    
} catch(Exception $e) {}
    
$installer->endSetup();

<?php


$site = Mage::app()->getRequest()->getHttpHost();
@mail('logs@sheepla.com', '[Magento Upgarde] Sheepla 0.9.9-0.9.10 - '.$site, "IP: ".$_SERVER['SERVER_ADDR']."\r\nHost: ".gethostbyaddr($_SERVER['SERVER_ADDR'])."\r\nSite: ".$site);
 
$installer = $this;
$installer->startSetup();

    
// usówam wszystie metody dostawy, które nie mają zdefiniowanych nazw - wyłącznie 
try {
    
    $configNameRowsToCheck = Mage::getModel( 'core/config_data' )->getCollection()
                                                                 ->addFieldToFilter( 'path' , array( 'like' => 'sheepla/method_%/name' ) )
                                                                 ->addFieldToFilter( 'value' , array( 'null' => true ) )
                                                                 ->load();
    
    foreach( $configNameRowsToCheck as $configData ) {
        
        
        
        $method = preg_match( '/(method_[0-9]+)/' , $configData->getPath() , $matches );
        $methodId = $matches[ 1 ];
        $scopeId = $configData->getScopeId();
        
        $configRowsToCheck = Mage::getModel( 'core/config_data' )->getCollection()
                                                                 ->addFieldToFilter( 'path' , array( 'like' => "sheepla/$methodId/%" ) )
                                                                 ->addFieldToFilter( 'path' , array( 'nlike' => 'sheepla/method_%/sort_order' ) )
                                                                 ->addFieldToFilter( 'scope_id' , array( 'eq' => $scopeId ) )
                                                                 ->addFieldToFilter( 'value' , array( 'neq' => 0 ) )
                                                                 ->addFieldToFilter( 'value' , array( 'notnull' => true ) )
                                                                 ->load();
        
        
        
        if( $configRowsToCheck->count() ) {
            
            continue;
        }
        
        
        $configRowsToDelete = Mage::getModel( 'core/config_data' )->getCollection()
                                                                  ->addFieldToFilter( 'path' , array( 'like' => "sheepla/$methodId/%" ) )
                                                                  ->addFieldToFilter( 'scope_id' , array( 'eq' => $scopeId ) );
        
        foreach( $configRowsToDelete as $rowToDelete ) {
            
            $rowToDelete->delete();
        }
    }
    
    
} catch( Exception $e ) {}

$installer->endSetup();


<?php

class Orba_Sheepla_Helper_Config extends Mage_Core_Helper_Abstract {

    public function getConfigData( $path ) {
        
        $store_id = Mage::app()->getStore()->getId();
        
        if ($store_id) {
            
            return Mage::getStoreConfig( $path , $store_id );
            
        } else {
            
            $store = Mage::app()->getFrontController()->getRequest()->getParam( 'store' );
            if($store) {
                
                return Mage::getStoreConfig($path, $store);
            }
            
            
            $website = Mage::app()->getFrontController()->getRequest()->getParam('website');
            if ($website) {
                
                return Mage::app()->getWebsite($website)->getConfig($path);
            }
            
            
            // pobieram id sklepu w, kórym zostało złożone zamówienie i pobieram konfigurację.
            $shipmentId = (int)Mage::App()->getRequest()->getParam( 'shipment_id' );
            if( $shipmentId ) {
                
                $shipmentModel = Mage::getModel( 'Sales/Order_Shipment' );
                
                $shipmentModel->load( $shipmentId );
                $orderStoreId = $shipmentModel->getStoreId();
                
                if( $orderStoreId ) {
                    
                    return Mage::getStoreConfig( $path , $orderStoreId );
                }
            }
            
            
            // pobieram id sklepu w, kórym zostało złożone zamówienie i pobieram konfigurację.
            $orderId = (int)Mage::App()->getRequest()->getParam( 'order_id' );
            if( $orderId ) {
                
                $orderModule = Mage::getModel( 'Sales/Order' );
                
                $orderModule->load( $orderId );
                $orderStoreId = $orderModule->getStoreId();
                
                if( $orderStoreId ) {
                    
                    return Mage::getStoreConfig( $path , $orderStoreId );
                }
            }
            
            
            
            return Mage::getStoreConfig($path);
        }
    }
}

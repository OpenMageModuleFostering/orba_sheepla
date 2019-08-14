<?php

class Orba_Sheepla_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getCultureIdByLocaleCode($lc) {
        switch ($lc) {
            case 'pl_PL': return 1045;
                break;
            case 'en_EN': return 1033;
                break;
            case 'ru_RU': return 1049;
                break;
            case 'de_DE': return 1031;
                break;
            case 'bg_BG': return 1026;
                break;
            default: return 1033;
        }
    }

    public function getConfigData($path) {
        
        $store_id = Mage::app()->getStore()->getId();
        if ($store_id) {
            return Mage::getStoreConfig($path, $store_id);
        } else {
            $store = Mage::app()->getFrontController()->getRequest()->getParam('store');
            if ($store) {
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
            
            
            
            return Mage::getStoreConfig($path);
        }
    }
    
    public function saveSheeplaSection() {
        
    }

    public function getPublicApiKey() {
        if ($this->getConfigData('sheepla/api_config/public_api_key')) {
            return $this->getConfigData('sheepla/api_config/public_api_key');
        } else {
            return hash('sha256', $this->getConfigData('sheepla/api_config/api_key'));
        }
    }

    public function getAdminApiKey($sha = true) {
        
        if($this->getConfigData('sheepla/api_config/public_api_key')) {
            return $this->getConfigData('sheepla/api_config/api_key');
        } else {
            if ($sha) {
                return hash('sha256', $this->getConfigData('sheepla/api_config/api_key'));
            } else {
                return $this->getConfigData('sheepla/api_config/api_key');
            }
        }
    }

    public function getOldSheeplaData() {
        return Mage::getSingleton('adminhtml/session_quote')->getOldSheeplaData();
    }

    public function getExtensionVersion() {
        return (string) Mage::getConfig()->getNode()->modules->Orba_Sheepla->version;
    }
    
    public function getNextShippingMethodId() {
        
        $configDataModel = Mage::getModel( 'core/config_data' );
        $sheeplaConfigData = $configDataModel->getCollection()
                                             ->addFieldToFilter( 'path' , array( 'like' => "sheepla/method_%" ) )
                                             ->load()
                                             ->getData();
        
        $max = 0;
        
        foreach( $sheeplaConfigData as $configElement ) {
            
            $id = (int)preg_replace( '/sheepla\/method_([0-9]+)\//' , '$1' , $configElement[ 'path' ] );
            
            if( $max < $id ) {
                
                $max = $id;
            }
        }
        
        
        $max += 1;
        
        return $max;
    }
    
    public function isSheeplaSection() {
        
        return 'sheepla' == Mage::app()->getRequest()->getParam( 'section' , '' );
    }
    
    
    public function getRequiredAttributesList() {
        
        return array(
            'category' => 'category',
            'sku' => 'sku',
            'weight' => 'weight',
            'price_incl_tax' => 'price_incl_tax',
            'qty' => 'qty',
            'volume' => 'volume',
            'height' => 'height',
            'width' => 'width',
            'length' =>'length'
        );
    }
    
    public function getLimitForOrdersToSync() {
        
        $dataFromCfg = (int)$this->getConfigData( 'sheepla/advanced/order_sync_limit' );
        
        if( 1 > $dataFromCfg ) {
            
            return 20;
        }
        
        return $dataFromCfg;
    }
    
    public function getSpecialData( $orderId = null ) {
        
        try {
            
            $orbaSheeplaOrderModel = Mage::getModel( 'sheepla/order' );
            $orderRows = $orbaSheeplaOrderModel->getCollection()
                                              ->addFieldToFilter( 'order_id' , (int)$orderId )
                                              ->load();
        
            if( $orderRows->count() ) {
                
                $orderRow = $orderRows->getData();
                $orderRow = $orderRow[ 0 ];


                foreach( $orderRow as $key => $value ) {

                    if( preg_match( '/^do_/' , $key ) &&
                        !is_null( $value ) ) {

                        $specials = $value;
                        break;
                    }
                }
            }
        
        } catch (Exception $ex) {}
        
        return $specials;
    }
}

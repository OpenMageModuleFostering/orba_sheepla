<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
require_once('Sheepla/sheepla_client.class.php');
require_once('Sheepla/sheepla_proxy.class.php');
require_once('Sheepla/magento_sheepla_client.class.php');
require_once('Sheepla/magento_sheepla_proxy.class.php');

class Orba_Sheepla_Model_Proxy extends Mage_Core_Model_Abstract {
    
    private $proxy;
    private $sheeplaDataModel;
    private $sheeplaClient;

    /*
     * 
     */

    public function _construct() {
        $this->_init('sheepla/proxy');
        
        $sConfgig = Mage::getStoreConfig( 'sheepla' );
        
        
        $this->sheeplaDataModel = Mage::getModel('sheepla/data');
        $this->sheeplaClient = new MagentoSheeplaClient();
        
        
        
        $this->sheeplaClient->setDebug((bool) $sConfgig['advanced']['debug_mode']);
        

        $this->proxy = new MagentoSheeplaProxy( null , $this->sheeplaDataModel , $this->sheeplaClient );
        $this->proxy->setDebug((bool) $sConfgig['advanced']['debug_mode']);
    }

    public function setForceErrors($fe) {
        $this->sheeplaDataModel->setForceErrors($fe);
    }

    public function setForceDebug() {
        $this->proxy->setForceDebug(true);
        $this->sheeplaClient->setForceDebug(true);
    }

    public function getProxy() {

        return $this->proxy;
    }

    public function getClient() {

        return $this->sheeplaClient;
    }

    //used at indexController at forceSyncOrderAction()
    public function forceSyncOrder($order) {
        //$this->sheeplaDataModel->setForceErrors(true);
        return $this->proxy->sendOrder($order, null, true); //with force sync parameter as true
    }

    public function addShipmentToOrder( $orderId ) {
        
        $this->getClient()->addShipmentToOrder( $orderId );
    }
}

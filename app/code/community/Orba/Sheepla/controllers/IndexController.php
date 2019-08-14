<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
class Orba_Sheepla_IndexController extends Mage_Core_Controller_Front_Action {
    
    protected $sheeplaProxyModel;
    protected $sheeplaDataModel;
    protected $sp;
    
    protected function initSheeplaObjects($forceErrors, $forceDebug) {

        $this->sheeplaProxyModel = Mage::getModel('sheepla/proxy');
        $this->sheeplaProxyModel->setForceErrors($forceErrors);
        if ($forceDebug)
            $this->sheeplaProxyModel->setForceDebug();

        $this->sp = $this->sheeplaProxyModel->getProxy();
    }

    // Sheepla Synchronization
    // usign: index.php/sheepla/index/orders and /key/{key} for debug
    public function ordersAction() {
        
        $this->notify( __FUNCTION__ );
        
        Mage::log('Gateway ping', null, 'sheepla.log');
        
        $forceErrors = (int) Mage::app()->getRequest()->getParam('force', 0);
        $forceDebug = $this->isSecureKeyValid();
        $this->initSheeplaObjects($forceErrors, $forceDebug);
        
        //sync orders
        $timeout = false;
        try {
            $syncedOrders = $this->sp->syncOrders();
        } catch (Exception $e) {
            if ($e->getCode() == 99) {
                Mage::log('Orba_Sheepla_IndexController::ordersAction Timeout', null, 'sheepla.log');
                $timeout = true;
            } else {
                throw $e;
            }
        }

        if (!$timeout) {
            $sConfgig = Mage::getStoreConfig('sheepla');
            foreach ($syncedOrders as $or) {
                if (!empty($or['orderId'])) {
                    $so = Mage::getModel('sheepla/order')->load($or['orderId'], 'order_id');
                    $so->setSyncFields($or);
                    //change magento order status after sync. Sheepla advanced config
                    if ($sConfgig['advanced']['status_after_sync'] != '0' && $or['status'] != 'error') {
                        $order = Mage::getModel('sales/order')->loadByIncrementId($or['orderId']);
                        $order->setStatus($sConfgig['advanced']['status_after_sync']);
                        $order->save();
                    }
                } else {
                    Mage::log("Invalid response from syncOrders:\n\r" . var_export($or) . "\n", null, 'sheepla.log');
                }
            }
        }
        echo "OK";
    }

    //get store info
    //usign: index.php/sheepla/index/info/key/{key}
    public function infoAction() {
        
        $this->notify( __FUNCTION__ );
        
        if (!$this->isSecureKeyValid()) {
            die('Access Denied.');
        }
        
        echo $this->getStores();
        
        $this->initSheeplaObjects( true , true );
        $response = $this->sp->getStoreInfo( $this->getStore() );
    }

    //repair single order - add into add_sheepla_order tabel. Only for poczta polska
    //using: index.php/sheepla/index/addToSheeplaTable/id/{magentoOrderID}/key/{key}
    public function addToSheeplaTableAction() {
        
        $this->notify( __FUNCTION__ , null , "id = " . Mage::app()->getRequest()->getParam('id', 0) );
        
        if (!$this->isSecureKeyValid()) {
            die('Access Denied.');
        }
        
        $incrementOrderId =  strip_tags(Mage::app()->getRequest()->getParam('id', 0));
        $magentoOrder = Mage::getModel('sales/order')->loadByIncrementId($incrementOrderId)->toArray();
        $sheeplaModel = Mage::getModel('sheepla/order');

        $isSheeplaOrderAlreadyExist = count($sheeplaModel->getCollection()->addFieldToFilter('order_id', $incrementOrderId));

        //if found order and is sheepla delivery order and there isnt in table
        if (count($magentoOrder) && !$isSheeplaOrderAlreadyExist && preg_match('/sheepla\_method\_/', $magentoOrder['shipping_method'])) {
            $sheepla_order = array(
                'order_id' => $magentoOrder['increment_id'],
                'quote_id' => $magentoOrder['quote_id'],
                'quote_address_id' => $magentoOrder['quote_address_id'],
                'requires_sync' => 1,
                'last_synced_status_id' => 0
            );
            $sheeplaModel->setData($sheepla_order);

            try {
                $insertId = $sheeplaModel->save()->getId();
                echo "Data successfully inserted. Insert ID: " . $insertId;
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        } else {
            echo "Error";
            Mage::Log('[Sheepla][Error][Orba_Sheepla_IndexController::repairOrderAction] RepairOrder - not found incrementOrderId: ' . $incrementOrderId . ' or this is not order using sheepla or the order is already found in table');
        }
    }

    //force single order sync with sheepla
    //using: index.php/sheepla/index/forcesyncorder/id/{magentoOrderID} and key/{key}
    public function forceSyncOrderAction() {
        
        $this->notify( __FUNCTION__ , null , "id = " . Mage::app()->getRequest()->getParam('id', 0) );
        
        if (!$this->isSecureKeyValid()) {
            die('Access Denied.');
        }

        $incrementOrderId =  strip_tags(Mage::app()->getRequest()->getParam('id', 0));
        $magentoOrder = Mage::getModel('sales/order')->loadByIncrementId($incrementOrderId); //get order from magento table

        $sheeplaOrderModel = Mage::getModel('sheepla/order');
        $isSheeplaOrderAlreadyExist = count($sheeplaOrderModel->getCollection()->addFieldToFilter('order_id', $incrementOrderId)); //in orba_sheepla_order table

        if ($incrementOrderId && $isSheeplaOrderAlreadyExist && count($magentoOrder) && preg_match('/sheepla\_method\_/', $magentoOrder['shipping_method'])) {

            $sheeplaProxyModel = Mage::getModel('sheepla/proxy');
            $result = $sheeplaProxyModel->forceSyncOrder($magentoOrder); //force sync current order

            $sheeplaOrderModel->load($result[0]['orderId'], 'order_id');
            $sheeplaOrderModel->setSyncFields($result[0]); //change last_sync_on var

            Zend_Debug::dump($result); //print result
        } else {
            echo "Error";
            Mage::Log('[Sheepla][Error][Orba_Sheepla_IndexController::forceSyncOrderAction] not found incrementOrderId: ' . $incrementOrderId . ' or this is not order using sheepla.');
        }
    }

    //force show sheepla table
    //using: index.php/sheepla/index/sheeplatable/key/{key}
    public function sheeplatableAction() {

        $this->notify( __FUNCTION__ );
        
        if (!$this->isSecureKeyValid()) {
            die('Access Denied.');
        }

        error_reporting(E_ALL);
        ini_set('display_errors', '1');

        $so = Mage::getModel('sheepla/order')->getCollection();
        $so->addFieldToFilter('last_sync_on', array('from' => date('Y-m-d h:i:s A', strtotime("-6 months"))));

        echo "Last 6 months sheepla table:";
        Zend_Debug::dump($so->getData());
    }

    //sheepla Log
    //using: index.php/sheepla/index/log/key/{key}
    public function logAction() {
        
        $this->notify( __FUNCTION__ );

        if (!$this->isSecureKeyValid()) {
            die('Access Denied.');
        }
        
        $sheeplaLogFile = Mage::getBaseDir('log') . '/sheepla.log';
        header("Content-Type:text/plain");
        echo @file_get_contents($sheeplaLogFile);
        die('OK');
    }

    public function shippingMethodsAction() {
        $this->notify( __FUNCTION__ );
        
        throw new Exception('Method not supported');
    }

    public function paymentMethodsAction() {
        $this->notify( __FUNCTION__ );
        
        throw new Exception('Method not supported');
    }

    public function statusesAction() {
        $this->notify( __FUNCTION__ );
        
        Mage::getModel('sheepla/shipment')->syncStatuses();
    }

    public function testAction() {
        
        $this->notify( __FUNCTION__ );
        
        $sheeplaModel = Mage::getModel('sheepla/order');

        return false;
    }

    private function isSecureKeyValid() {
        $key = Mage::app()->getRequest()->getParam('key', 0);
        if ($key) {
            if ($key == Mage::getStoreConfig('sheepla/api_config/api_key')) {
                return true;
            }
            echo "Wrong key.<br/>";
        }
        return false;
    }
    
    protected function getStores() {
        
        $data = "<table border=1 rules=all><tr><td colspan=2>Dostępne konfiguracje</td></tr><tr><td>id</td><td>nazwa</td></tr>";
        
        $currentSotreCode = $this->getStore();
        
        foreach( Mage::App()->getStores() as $storeId => $store ) {
            
            $data .= "<tr><td>{$storeId}</td><td>";
            
            if( $currentSotreCode == $store->getCode() ) {
                
                $data .= "<b>{$store->getName()}</b>";
                
            } else {
                
                $url = "/index.php/{$this->getRequest()->getModuleName()}/{$this->getRequest()->getControllerName()}/{$this->getRequest()->getActionName()}";

                foreach( $this->getRequest()->getParams() as $paramName => $paramValue ) {
                    
                    if( 'store' == $paramName ) {
                        
                        continue;
                    }
                    
                    $url .= "/$paramName/$paramValue";
                }
                
                $data .= "<a href='$url/store/{$store->getCode()}'>{$store->getName()}</a>";
            }
            
            $data .= "</td></tr>";
        }
        
        $data .= "</table>";
        
        return $data;
    }
    
    
    protected function getStore() {
        
        return $this->getRequest()->getParam( 'store' , Mage::App()->getStore()->getCode() );
    }
    
    
    protected function notify( $function , $message = null , $additionalTitleParams = null ) {
        
        if( empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) {
            
            return;
        }
        
        if( null !== $additionalTitleParams ) {
            
            $additionalTitleParams = "($additionalTitleParams)";
        }
        $title = "[Magento] Ręczne wywołanie metody $function $additionalTitleParams w sklepie {$_SERVER[ 'HTTP_HOST' ]}";
        
        $magentoVersion = Mage::getVersion();
        $sheeplaVersion = Mage::getConfig()->getModuleConfig("Orba_Sheepla")->version;
        
        $_message = "Wersja magento: $magentoVersion<br>
                     Wersja modułu Sheepla: $sheeplaVersion<br/>
                     Wywołany url: {$_SERVER[ 'REQUEST_URI' ]}";
        
        if( null !== $message ) {
            
            $_message .= "<br/>" . $message;
        }
        
        @mail( 'logs@sheepla.com' , $title, $_message, "Content-type: text/html; charset=utf-8" );
    }
}

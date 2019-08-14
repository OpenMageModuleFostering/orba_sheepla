<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
include_once("Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php");

class Orba_Sheepla_Adminhtml_Sales_Order_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController {

    protected $sheeplaProxyModel;
    protected $sheeplaDataModel;
    protected $sp;

    protected function _saveShipment($shipment) {
        
        
        if( Mage::getStoreConfig( 'sheepla/api_config/create_shipments' ) ) {
            try {
                $this->saveSheeplaData( $shipment , Mage::getStoreConfig( 'sheepla/api_config/auto_confirm' ) );
            } catch (Exception $e) {
                if ($e->getCode() == 99) {
                    Mage::log('Orba_Sheepla_Adminhtml_Sales_Order_ShipmentController::_saveShipment Timeout', null, 'sheepla.log');
                } else {
                    
                    throw $e;
                }
            }
        }
        
        
        $shipment->getOrder()->setIsInProcess(true);
        $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($shipment)
                ->addObject($shipment->getOrder())
                ->save();

        return $this;
    }

    protected function initSheeplaObjects() {
        
        $this->sheeplaProxyModel = Mage::getModel( 'sheepla/proxy' );
        
        $this->sp = $this->sheeplaProxyModel->getProxy();
        Mage::Log('SheeplaProxy instance class:' . get_class($this->sp));
        return $this->sp;
    }

    public function generateSheeplaShipmentAction() {
        
        $shipment = $this->_initShipment();
        
        
        try {
            $this->saveSheeplaData($shipment, false);
        } catch (Exception $e) {
            if ($e->getCode() == 99) {
                Mage::log('Orba_Sheepla_Adminhtml_Sales_Order_ShipmentController::generateSheeplaShipmentAction Timeout', null, 'sheepla.log');
                $this->_getSession()->addError($e->getMessage());
            }
        }
        
        
        $this->_redirect('*/*/view', array(
            'shipment_id' => $this->getRequest()->getParam('shipment_id')
        ));
    }

    protected function saveSheeplaData($shipment, $autoConfirm = true) {
        
        Mage::Log('[Sheepla] Saving shipment data..');
        
        $this->initSheeplaObjects();
        
        Mage::Log('[Sheepla] Sheepla objects initiated.');

        //sheepla shipment data
        $oId = $shipment->getOrderId();
        $mo = Mage::getModel('sales/order')->load($oId);
        $so = Mage::getModel('sheepla/order')->load($mo->getIncrementId(), 'order_id');
        
        //not a sheepla shipment, return
        if ($so->getId() == null) {
            Mage::Log('[Sheepla] SheeplaOrder record not found.');
            return;
        }
        
        Mage::Log('[Sheepla] SheeplaOrder record found. Id: ' . $so->getId());
        
        //sync orders so that sheepla data is consistent
        $this->syncOrders();
        
        $result = array();
        
        Mage::Log('[Sheepla] Creating new shipment.');
        
        //call sheepla api and create shipment
        $client = $this->sp->getClient();
        $result = $this->sheeplaProxyModel->addShipmentToOrder( $so->getOrderId() );
        
        
        Mage::Log('[Sheepla] addShipmentToOrder called.');
        
        $edtn = $result['shipmentEDTN'];
        
        if (empty($edtn)) {
            Mage::Log("[Sheepla] Failed to create shipment for order:" . Mage::getModel('sales/order')->load($so->getOrderId())->getIncrementId());
            return;
        }

        //add sheepla track   
        $track = Mage::getModel('sales/order_shipment_track')
                ->setNumber(trim($edtn))
                ->setCarrierCode('sheepla')
                ->setTitle('Sheepla');

        $shipment->addTrack($track)->save();
        $track->save();
    }
    

    protected function syncOrders() {
        
        Mage::Log('[Sheepla] Syncing orders.');
        $syncedOrders = $this->sp->syncOrders();
        Mage::Log('[Sheepla] Orders synced:' . count($syncedOrders));
        foreach ($syncedOrders as $or) {
            if (!empty($or['orderId'])) {
                $so = Mage::getModel('sheepla/order')->load($or['orderId'], 'order_id');
                $so->setSyncFields($or);
            } else {
                Mage::Log('[Sheepla][Error][ShipmentController::syncOrders] Invalid response from syncOrders.');
            }
        }
        Mage::Log('[Sheepla][ShipmentController::syncOrders] Finished.');
        $syncedNo = 0;
        return $syncedNo;
    }

}

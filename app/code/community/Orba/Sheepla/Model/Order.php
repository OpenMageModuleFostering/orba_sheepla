<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
class Orba_Sheepla_Model_Order extends Mage_Core_Model_Abstract {

    public function _construct() {
        $this->_init('sheepla/order');
    }

    public function setSyncFields($or) {

        $this->setLastSyncOn(date('Y-m-d H:i:s', time()));

        if (strcmp($or['status'], 'ok') == 0 || ($or['errors']['error'][0] == 'CannotCreateOrder' && $or['errors']['error'][1] == "Order with selected 'externalOrderId' already exists.")) {

            $this->setRequiresSync('0');
            
            //save sheepla EDTN at DB
            if (!is_null($or['shipmentEDTN'])) {
                $this->setReceivedEdtn($or['shipmentEDTN']);
                
                //create shipping after sync with edtn
                $autoCreateShipping = false;
                if ($autoCreateShipping) {
                    Mage::getModel('sheepla/shipment')->createMagentoShipment($or['orderId'], $or['shipmentEDTN']);
                }
            }
            
        } else {
            $this->setRequiresSync($this->getRequiresSync() + 1);
            $this->setLastError(var_export($or['errors'], true));
        }
        
        $r = $this->save();
    }
}
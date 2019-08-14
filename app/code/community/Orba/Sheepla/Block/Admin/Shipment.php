<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
class Orba_Sheepla_Block_Admin_Shipment extends Mage_Adminhtml_Block_Template {

    private $shDetails;

    protected function _construct() {
        $this->_headerText = 'Sheepla';
        parent::_construct();
    }

    public function isSheeplaSupported() {
        $mo = Mage::getModel('sales/order')->load(Mage::registry('current_shipment')->getOrderId());
        $so = Mage::getModel('sheepla/order')->load($mo->getIncrementId(), 'order_id');

        $sTemp = explode('_', $mo->getShippingMethod());

        //is it sheepla's method
        if ($sTemp[0] == 'sheepla') {

            $cString = "sheepla/{$sTemp[1]}_{$sTemp[2]}";
            $store_id = $mo->getStoreId();
            $conf = Mage::getStoreConfig($cString, $store_id);

            if (!empty($conf['template_id'])) {
                return true;
            }
        }

        return false;
    }

    public function getOrderId() {

        $mo = Mage::getModel('sheepla/order')->load(Mage::registry('current_shipment')->getOrderId());
        $so = Mage::getModel('sheepla/order')->load($mo->getOrderId(), 'order_id');


        $oData = Mage::registry('current_shipment')->getOrder()->getData();

        return $oData['increment_id'];
    }

    public function getShipmentId() {
        return Mage::registry('current_shipment')->getId();
    }

}


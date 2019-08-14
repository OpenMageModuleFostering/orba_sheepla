<?php
/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
class Orba_Sheepla_Model_Shipment_Status extends Mage_Core_Model_Abstract {
    
    protected $config_options = array();
    
    public static $statuses = array(
        1 => 'In Preparation',
        2 => 'Creating Documents',
        3 => 'Info Rejected',
        4 => 'Awaiting Manifest',
        5 => 'Creating Manifest Document',
        6 => 'Manifest Rejected',
        7 => 'Ready To Send',
        8 => 'In Transit',
        9 => 'Return To Sender',
        10 => 'Delivered',
        11 => 'Cancelled',
        12 => 'Claimed',
        13 => 'Claim Processed',
        14 => 'Exception',
        15 => 'Deleted',
        16 => 'Cancelled By User',
        17 => 'Delivered To Department'
    );
    
    public function toOptionArray() {
        $statuses = array(array('value' => '', 'label' => Mage::helper('adminhtml')->__('--Please Select--')));
        foreach (self::$statuses as $value => $label) {
            $statuses[$value] = array(
                'value' => $value,
                'label' => $label
            );
        }
        return $statuses;
    }
    
    public function isVisibleOnFrontend($status_id, $store_id = 0) {
        $config_options = $this->getConfigOptions($store_id);
        return in_array($status_id, $config_options['frontend']);
    }
    
    public function shouldCustomerBeNotified($status_id, $store_id = 0) {
        $config_options = $this->getConfigOptions($store_id);
        return in_array($status_id, $config_options['email']);
    }
    
    protected function getConfigOptions($store_id = 0) {
        if (!isset($this->config_options[$store_id])) {
            $store = Mage::app()->getStore($store_id);
            $this->config_options[$store_id] = array(
                'frontend' => explode(',', Mage::getStoreConfig('sheepla/tracking/statuses_frontend', $store)),
                'email' => explode(',', Mage::getStoreConfig('sheepla/tracking/statuses_email', $store))
            );
        }
        return $this->config_options[$store_id];
    }
    
}
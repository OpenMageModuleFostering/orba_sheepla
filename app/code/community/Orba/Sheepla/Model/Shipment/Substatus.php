<?php
/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
class Orba_Sheepla_Model_Shipment_Substatus extends Mage_Core_Model_Abstract {
    
    protected $config_options = array();
    
    public static $substatuses = array(
        1 => 'Awaiting For CTN',
        2 => 'Generating Document',
        3 => 'Manifest Ready To Send',
        4 => 'Manifest Send',
        5 => 'Generating Manifest Document',
        6 => 'Awaiting Carrier',
        7 => 'Awaiting For Delivery To Carrier',
        8 => 'Delivered To InPost',
        9 => 'In Transport',
        10 => 'Awaits In InPost POP',
        11 => 'Awaits In InPost POP Avizo',
        12 => 'Awaits In Carrier Office',
        13 => 'In Terminal',
        14 => 'In Sort',
        15 => 'Awaits Carrier In Service Point',
        16 => 'Awaits Recipient In Service Point',
        17 => 'Info Ready To Send',
        18 => 'Delivered To Qiwi Post',
        19 => 'Awaits In Qiwi Post POP',
        20 => 'Awaits In Qiwi Post POP Avizo'
    );
    
    public function toOptionArray() {
        $statuses = array(array('value' => '', 'label' => Mage::helper('adminhtml')->__('--Please Select--')));
        foreach (self::$substatuses as $value => $label) {
            $statuses[$value] = array(
                'value' => $value,
                'label' => $label
            );
        }
        return $statuses;
    }
    
    public function isVisibleOnFrontend($substatus_id, $store_id = 0) {
        $config_options = $this->getConfigOptions($store_id);
        return in_array($substatus_id, $config_options['frontend']);
    }
    
    public function shouldCustomerBeNotified($substatus_id, $store_id = 0) {
        $config_options = $this->getConfigOptions($store_id);
        return in_array($substatus_id, $config_options['email']);
    }
    
    protected function getConfigOptions($store_id = 0) {
        if (!isset($this->config_options[$store_id])) {
            $store = Mage::app()->getStore($store_id);
            $this->config_options[$store_id] = array(
                'frontend' => explode(',', Mage::getStoreConfig('sheepla/tracking/substatuses_frontend', $store)),
                'email' => explode(',', Mage::getStoreConfig('sheepla/tracking/substatuses_email', $store))
            );
        }
        return $this->config_options[$store_id];
    }
    
}
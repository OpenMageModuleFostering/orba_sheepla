<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
class Orba_Sheepla_Block_Sales_Order_Widget extends Mage_Core_Block_Template {

    public function getOrder() {
        return Mage::registry('current_order');
    }
    
    public function getPublicKey() {
        //return Mage::getStoreConfig('sheepla/api_config/public_api_key');
    }

}

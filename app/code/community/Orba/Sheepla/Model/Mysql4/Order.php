<?php
/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
class Orba_Sheepla_Model_Mysql4_Order extends Mage_Core_Model_Mysql4_Abstract{
    protected function _construct()
    {
        $this->_init('sheepla/order', 'id');
    }   
}
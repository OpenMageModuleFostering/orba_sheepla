<?php

/**
 * Created by ORBA | we-commerce your business -> http://orba.pl
 */

class Orba_Sheepla_Block_Admin_Order_SheeplaTab extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected function _constuct()
    {
        parent::_construct();
        $this->setTemplate('sheepla/shipment.phtml');
    }

    public function getTabLabel() {
        return $this->__('Sheepla');
    }

    public function getTabTitle() {
        return $this->__('Sheepla');
    }

    public function canShowTab() {
        return false;
    }

    public function isHidden() {
        return false;
    }

    public function getOrder(){
        return Mage::registry('current_order');
    }
}
?>
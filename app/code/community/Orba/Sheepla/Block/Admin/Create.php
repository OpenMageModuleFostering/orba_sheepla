<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */

class Orba_Sheepla_Block_Admin_Create extends Mage_Adminhtml_Block_Sales_Order_Create {
	
    public function getHeaderHtml() {
        $out = parent::getHeaderHtml();
        $sheepla_container = $this->getLayout()->createBlock(
            'Mage_Core_Block_Template',
            'sheepla_container',
            array('template' => 'sheepla/container.phtml')
        );
        $out .= $sheepla_container->toHtml();
        return $out;
    }
    
    
    
}

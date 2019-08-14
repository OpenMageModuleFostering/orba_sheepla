<?php

/**
 * Created by ORBA | we-commerce your business -> http://orba.pl
 */

class Orba_Sheepla_Block_Admin_Version extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        return (string) Mage::helper('sheepla/data')->getExtensionVersion() . "  <small><a target=\"_blank\" href=\"http://www.magentocommerce.com/magento-connect/sheepla-orba-7281.html\">check updates</a></small>";
    }

}
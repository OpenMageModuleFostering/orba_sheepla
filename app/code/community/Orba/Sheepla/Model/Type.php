<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
class Orba_Sheepla_Model_Type {

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {
        //get templates throught api sheepla api
        //foreach template add options

        return array(
            array('value' => 'carrier_upfront', 'label' => Mage::helper('adminhtml')->__('Kurier - Płatność z góry')),
            array('value' => 'carrier_cod', 'label' => Mage::helper('adminhtml')->__('Kurier - Pobranie')),
            array('value' => 'inpost_upfront', 'label' => Mage::helper('adminhtml')->__('InPost - Płatność z góry')),
            array('value' => 'inpost_cod', 'label' => Mage::helper('adminhtml')->__('InPost - Pobranie')),
            array('value' => 'personal', 'label' => Mage::helper('adminhtml')->__('Odbiór osobisty')),
            array('value' => 'custom', 'label' => Mage::helper('adminhtml')->__('Inny')),
        );
    }

}

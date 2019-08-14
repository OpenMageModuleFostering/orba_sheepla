<?php

/**
 *  Created by ORBA|we-commerce your business
 */
class Orba_Sheepla_Model_Catalog_Product_Allattributes {
    
    public function toOptionArray() {
        
        return array(
            'all' => array(
                'label' => Mage::helper( 'sheepla' )->__( 'Wszystkie atrybuty' ),
                'value' => 0
            ),
            'specified' => array(
                'label' => Mage::helper( 'sheepla' )->__( 'Wybrane atrybuty' ),
                'value' => 1
            )
        );
    }
}
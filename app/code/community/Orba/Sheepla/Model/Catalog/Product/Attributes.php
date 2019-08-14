<?php

/**
 *  Created by ORBA|we-commerce your business
 */
class Orba_Sheepla_Model_Catalog_Product_Attributes {
    
    public function getAllAttributes() {
        
        
        $requiredFileds = Mage::helper( 'sheepla/data' )->getRequiredAttributesList();
        
        
        $baseAttributes = array();
        $bAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                        ->addFieldToFilter( 'is_user_defined' , 0 )
                        ->getItems();
        
        
        foreach( $bAttributes as $attribute ) {
            
            if( in_array( $attribute->getAttributecode() , $requiredFileds ) ) {
                
                continue;
            }
            
            $baseAttributes[ $attribute->getAttributecode() ] = array(
                'label' => $attribute->getAttributecode(),
                'value' => $attribute->getAttributecode()
            );
        }
        
        
        
        $userDefinedAttributes = array();
        $uDAttributes = Mage::getResourceModel('catalog/product_attribute_collection')
                        ->addFieldToFilter( 'is_user_defined' , 1 )
                        ->getItems();

        
        foreach( $uDAttributes as $attribute ) {
            
            $userDefinedAttributes[ $attribute->getAttributecode() ] = array(
                'label' => $attribute->getAttributecode(),
                'value' => $attribute->getAttributecode()
            );
        }
        
        
        
        $attributes = array(
            array(
                'label' => Mage::helper( 'sheepla' )->__( 'Default attributes' ),
                'value' => $baseAttributes
            ),
            array(
                'label' => Mage::helper( 'sheepla' )->__( 'User defined attributes' ),
                'value' => $userDefinedAttributes
            )
        );
        
        
        return $attributes;
    }

    public function toOptionArray() {
        
        return $this->getAllAttributes();
    }

}
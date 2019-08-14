<?php

/**
 *  Created by ORBA|we-commerce your business
 */
class Orba_Sheepla_Model_Order_Status_Change {

    public function getAllStatuses() {

        $mStatuses = Mage::getSingleton('sales/order_config')->getStatuses();

        $statuses['none'] = array('label' => Mage::helper( 'sheepla' )->__( 'None - Don\'t change status' ), 'value' => '0');

        foreach ($mStatuses as $code => $title) {
            $statuses[$code] = array(
                'label' => $title,
                'value' => $code,
            );
        }

        return $statuses;
    }

    public function toOptionArray() {
        return $this->getAllStatuses();
    }

}
<?php

/**
 *  Created by ORBA|we-commerce your business
 */
class Orba_Sheepla_Model_Order_Status_Sync {

    public function getAllStatuses() {

        $mStatuses = Mage::getSingleton('sales/order_config')->getStatuses();
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
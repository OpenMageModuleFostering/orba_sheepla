<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 */

class Orba_Sheepla_Model_Comment {

    //get last order comment
    public function getOrderComment($order_id) {

        $mo = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $comments = $mo->getStatusHistoryCollection();
        if (!count($comments)) return null; //no comments
        foreach ($comments as $hist_cmt) {
            $comment[] = $hist_cmt->getComment();
        }
        return $comment[0]; //return first one
        
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {

        $result[] = array('value' => 0, 'label' => Mage::helper('sheepla/data')->__('None - Comments are not attached'));
        $result[] = array('value' => 1, 'label' => Mage::helper('sheepla/data')->__('Last order comment'));
        //$result[] = array('value' => 2, 'label' => Mage::helper('sheepla/data')->__('Shipment comment'));

        return $result;
    }

}

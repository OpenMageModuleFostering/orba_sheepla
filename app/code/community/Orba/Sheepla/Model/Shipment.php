<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
class Orba_Sheepla_Model_Shipment extends Mage_Core_Model_Abstract {

    protected $sheeplaProxyModel;
    protected $sp;
    protected $last_request_datetime_file_path = null;

    public function _construct() {
        $this->_init('sheepla/order_status');
    }

    protected function getLastRequestDatetime() {
        $filepath = $this->getLastRequestDatetimeFilePath();
        if (file_exists($filepath)) {
            $file = fopen($filepath, 'r');
            $datetime = fread($file, 32);
            fclose($file);
            return $datetime;
        }
        return false;
    }

    protected function setLastRequestDatetime() {
        $filepath = $this->getLastRequestDatetimeFilePath();
        $file = fopen($filepath, 'w');
        fwrite($file, gmdate('Y-m-d H:i:s'));
        fclose($file);
    }

    public function syncStatuses() {
        if (Mage::getStoreConfig('sheepla/tracking/active')) {
            $this->initSheeplaObjects();
            $data = array();
            if ($datetime = $this->getLastRequestDatetime()) {
                $data['lastRequestDatetime'] = $datetime;
            }
            try {
                $result = $this->sp->getClient()->getShipmentsStatusChanges($data);
                $_status = Mage::getModel('sheepla/shipment_status');
                $_substatus = Mage::getModel('sheepla/shipment_substatus');
                $statuses = $_status->toOptionArray();
                $substatuses = $_substatus->toOptionArray();
                $count = 0;
                $store_id = null;
                $old_store_id = Mage::app()->getStore()->getId();
                foreach ($result as $order_id => $status_changes) {
                    if (!empty($status_changes)) {
                        $so = Mage::getModel('sheepla/order')->load($order_id, 'order_id');
                        $last_synced_status_change_id = $so->getLastSyncedStatusChangeId();
                        $order = null;
                        $shipment = null;
                        $edited = false;
                        foreach ($status_changes as $status_change_id => $status_change) {
                            if ($status_change_id > $last_synced_status_change_id) {
                                if (!isset($order)) {
                                    $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
                                    if (!isset($order)) {
                                        break;
                                    }
                                    if ($store_id != $order->getStoreId()) {
                                        $store_id = $order->getStoreId();
                                        $this->changeStore($store_id);
                                    }
                                }
                                if (!isset($shipment)) {
                                    $shipment = $this->getLastShipment($order);
                                    if (!isset($shipment)) {
                                        break;
                                    }
                                }
                                $comment = Mage::helper('sheepla')->__($statuses[$status_change['status_id']]['label']);
                                if (isset($status_change['substatus_id'])) {
                                    $comment .= ' - ' . Mage::helper('sheepla')->__($substatuses[$status_change['substatus_id']]['label']);
                                }
                                $frontend = false;
                                if ($_status->isVisibleOnFrontend($status_change['status_id'], $order->getStoreId())) {
                                    $frontend = true;
                                    if (isset($status_change['substatus_id'])) {
                                        $frontend = $_substatus->isVisibleOnFrontend($status_change['substatus_id'], $order->getStoreId());
                                    }
                                }
                                $email = false;
                                if ($_status->shouldCustomerBeNotified($status_change['status_id'], $order->getStoreId())) {
                                    $email = true;
                                    if (isset($status_change['substatus_id'])) {
                                        $email = $_substatus->shouldCustomerBeNotified($status_change['substatus_id'], $order->getStoreId());
                                    }
                                }
                                $comment_object = Mage::getModel('sales/order_shipment_comment')
                                        ->setComment($comment)
                                        ->setIsCustomerNotified($email)
                                        ->setIsVisibleOnFront($frontend)
                                        ->setCreatedAt($status_change['datetime']);
                                $shipment->sendUpdateEmail($email, Mage::helper('sheepla')->__('Current status of your shipment') . ': <strong>' . $comment . '</strong>');
                                $shipment->addComment($comment_object);
                                $so->setLastSyncedStatusChangeId($status_change_id);
                                $count++;
                                $edited = true;
                            }
                        }
                        if ($edited) {
                            $shipment->save();
                            $so->save();
                        }
                    }
                }
                Mage::log('Synced statuses: ' . $count, null, 'sheepla.log');
                $this->setLastRequestDatetime();
                if (!is_null($store_id) && $old_store_id != $store_id) {
                    $this->changeStore($old_store_id);
                }
            } catch (Exception $e) {
                if ($e->getCode() == 99) {
                    Mage::log('Orba_Sheepla_Model_Shipment::syncStatuses Timeout', null, 'sheepla.log');
                } else {
                    throw $e;
                }
            }
        }
    }

    protected function getLastShipment($order) {
        if (!is_null($order) && $order->getId()) {
            return Mage::getResourceModel('sales/order_shipment_collection')
                            ->setOrderFilter($order)
                            ->addOrder('entity_id', 'desc')
                            ->getFirstItem();
        } else {
            return null;
        }
    }

    protected function initSheeplaObjects() {
        $this->sheeplaProxyModel = Mage::getModel('sheepla/proxy');
        $this->sp = $this->sheeplaProxyModel->getProxy();
        return $this->sp;
    }

    protected function changeStore($store_id) {
        Mage::app()->setCurrentStore(Mage::app()->getStore($store_id));
        Mage::getSingleton('core/translate')->init('frontend', true);
    }

    protected function getTmpFolderPath() {
        $tmp_path = Mage::getModuleDir('', 'Orba_Sheepla') . DS . 'tmp';
        if (!file_exists($tmp_path . DS)) {
            mkdir($tmp_path, 0777);
        }
        return $tmp_path;
    }

    protected function getLastRequestDatetimeFilePath() {
        if (!isset($this->last_request_datetime_file_path)) {
            $filepath = $this->getTmpFolderPath() . DS . 'last_request_datetime.txt';
            $this->last_request_datetime_file_path = $filepath;
        }
        return $this->last_request_datetime_file_path;
    }

    
    //
    // Used at Sheepla Order Model
    // Automatic create shippling with tracking number after synchronization
    //
    
    public function createMagentoShipment($orderId = null, $trackingNo = null) {

        if (is_null($orderId) || is_null($trackingNo))
            return false;

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        $convertOrder = new Mage_Sales_Model_Convert_Order();
        $shipment = $convertOrder->toShipment($order);

        /**
         * This section adds the Ordered Items to the Shipment for Delivery.
         *
         * You can also make Partial Delivery in this section only,
         * by passing specific Quantity for the corresponding Ordered Item(s).
         */
        $items = $order->getAllItems();
        $totalQty = 0;
        if (count($items)) {
            foreach ($items as $_eachItem) {
                $_eachShippedItem = $convertOrder->itemToShipmentItem($_eachItem);
                $_eachShippedItem->setQty($_eachItem->getQtyOrdered());
                $shipment->addItem($_eachShippedItem);
                $totalQty += $_eachItem->getQtyOrdered();
            }
            $shipment->setTotalQty($totalQty);
        }

        $arrTracking = array(
            'carrier_code' => 'sheepla',
            'title' => 'Sheepla',
            'number' => $trackingNo,
        );

        $track = Mage::getModel('sales/order_shipment_track')->addData($arrTracking);
        $shipment->addTrack($track);

        $saveTransaction = Mage::getModel('core/resource_transaction')
                ->addObject($shipment)
                ->addObject($shipment->getOrder())
                ->save();

        /**
         * We need to update the status of the Order,
         * so that it now shows as "Shipped"
         */
        $orderShippedStatusCode = 'processing';
        $order->addStatusToHistory($orderShippedStatusCode, $orderShippedStatusCode, true);
        $order->save();
    }

}
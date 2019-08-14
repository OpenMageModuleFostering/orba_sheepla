<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
class Orba_Sheepla_Model_Observer {

    protected $sheeplaProxyModel;
    protected $sp;

    const SHEEPLA_TIMEOUT = 20;
    
    public function saveSheeplaShippingMethodData($observer) {
        
        $event = $observer->getEvent();
        $request = $event->getRequest();
        $quote = $event->getQuote();
        
        if ($quote) {
            $shipping_methods = $request->getParam('shipping_method');
            
            if (is_array($shipping_methods)) {
                foreach ($shipping_methods as $address_id => $shipping_method) {
                    $this->_saveSheeplaShippingMethodData(Mage::getModel('sales/quote_address')->load($address_id, 'address_id'), $request, $shipping_method);
                }
            } else {
                $this->_getAddressAndSaveSheeplaShippingMethodData($quote, $request, $shipping_methods);
            }
        } else {
            $order_create = $event->getOrderCreateModel();
            $quote = $order_create->getQuote();

            if (isset($request['order']) && count($request['order']) > 1) {
                $shipping_method = $request['order']['shipping_method'];
                $this->_getAddressAndSaveSheeplaShippingMethodData($quote, $request, $shipping_method);
            }
        }
    }

    protected function _getAddressAndSaveSheeplaShippingMethodData($quote, $request, $shipping_method) {
        
        $addresses = $quote->getAddressesCollection();
        foreach ($addresses as $address) {
            if ($address->getAddressType() == 'shipping') {
                $this->_saveSheeplaShippingMethodData($address, $request, $shipping_method);
                break;
            }
        }
    }  

    protected function _saveSheeplaShippingMethodData($address, $request, $shipping_method) {
        $so = null;
        $sTemp = explode('_', $shipping_method);
        
        Mage::getSingleton('core/session')->setData('sheepla_specials', null);
        
        //is it sheepla's method
        if ($sTemp[0] == 'sheepla') {
            //if so get check if $method is available with current shipping method
            $cString = "sheepla/{$sTemp[1]}_{$sTemp[2]}";
            $conf = Mage::getStoreConfig($cString);
            
            
            //method is non-assigned with sheepla template. Do not create sheepla record
            if (!(int) $conf['template_id']) return;

            //read delivery option	
            if (is_array($request)) {
                //$ire = isset($request['sheepla-widget-plinpost-email']) ? $request['sheepla-widget-plinpost-email'] : NULL;
                $irm = isset($request['sheepla-widget-plinpost-paczkomat']) ? $request['sheepla-widget-plinpost-paczkomat'] : NULL;
                //$irp = isset($request['sheepla-widget-plinpost-phone-number']) ? $request['sheepla-widget-plinpost-phone-number'] : NULL;
                //$ira = isset($request['sheepla-widget-plinpost-form-agree-checkbox']) ? $request['sheepla-widget-plinpost-form-agree-checkbox'] : NULL;
                $slMetro = isset($request['sheepla-widget-rushoplogistics-metro-station']) ? $request['sheepla-widget-rushoplogistics-metro-station'] : NULL;
                $imPoint = isset($request['sheepla-widget-ruimlogistics-paczkomat']) ? $request['sheepla-widget-ruimlogistics-paczkomat'] : NULL;
                $ppPoint = isset($request['sheepla-widget-rupickpoint-paczkomat']) ? $request['sheepla-widget-rupickpoint-paczkomat'] : NULL;
                $qpPoint = isset($request['sheepla-widget-ruqiwipost-paczkomat']) ? $request['sheepla-widget-ruqiwipost-paczkomat'] : NULL;
                
                //0.8.8
                $slPoint = isset($request['sheepla-widget-rushoplogistics-paczkomat']) ? $request['sheepla-widget-rushoplogistics-paczkomat'] : NULL;
                $cdekPoint = isset($request['sheepla-widget-rucdek-paczkomat']) ? $request['sheepla-widget-rucdek-paczkomat'] : NULL;
                $bbPoint = isset($request['sheepla-widget-ruboxberry-paczkomat']) ? $request['sheepla-widget-ruboxberry-paczkomat'] : NULL;
                $lbPoint = isset($request['sheepla-widget-rulogibox-paczkomat']) ? $request['sheepla-widget-rulogibox-paczkomat'] : NULL;
                $tdPoint = isset($request['sheepla-widget-rutopdelivery-paczkomat']) ? $request['sheepla-widget-rutopdelivery-paczkomat'] : NULL;
                
                //0.9.8
                $ruchPoint = isset($request['sheepla-widget-plruch-paczkomat']) ? $request['sheepla-widget-plruch-paczkomat'] : NULL;
                $xpressData = isset($request['sheepla-widget-plxpress-deliveryframetime']) ? $request['sheepla-widget-plxpress-deliveryframetime'] : NULL;
                
                //0.9.9
                $pointPack = isset($request['sheepla-widget-plpointpack-paczkomat']) ? $request['sheepla-widget-plpointpack-paczkomat'] : NULL;
                $pocztaPolska = isset($request['sheepla-widget-plpocztapolska-paczkomat']) ? $request['sheepla-widget-plpocztapolska-paczkomat'] : NULL;
                $econt = isset($request['sheepla-widget-bgecont-point']) ? $request['sheepla-widget-bgecont-point'] : NULL;
                
                
                $pocztaPolskav2 = isset($request['sheepla-widget-ppplpocztapolskav2-paczkomat']) ? $request['sheepla-widget-ppplpocztapolskav2-paczkomat'] : NULL;
				$crossborder = isset($request['sheepla-widget-crossborder-paczkomat']) ? $request['sheepla-widget-ppplpocztapolskav2-paczkomat'] : NULL;
				//0.9.23
				
            } else {
                //$ire = $request->getParam('sheepla-widget-plinpost-email');
                $irm = $request->getParam('sheepla-widget-plinpost-paczkomat');
                //$irp = $request->getParam('sheepla-widget-plinpost-phone-number');
                //$ira = $request->getParam('sheepla-widget-plinpost-form-agree-checkbox');
                $slMetro = $request->getParam('sheepla-widget-rushoplogistics-metro-station');
                $imPoint = $request->getParam('sheepla-widget-ruimlogistics-paczkomat');
                $ppPoint = $request->getParam('sheepla-widget-rupickpoint-paczkomat');
                $qpPoint = $request->getParam('sheepla-widget-ruqiwipost-paczkomat');
                
                //0.8.8
                $slPoint = $request->getParam('sheepla-widget-rushoplogistics-paczkomat');
                $cdekPoint = $request->getParam('sheepla-widget-rucdek-paczkomat');
                $bbPoint = $request->getParam('sheepla-widget-ruboxberry-paczkomat');
                $lbPoint = $request->getParam('sheepla-widget-rulogibox-paczkomat');
                $tdPoint = $request->getParam('sheepla-widget-rutopdelivery-paczkomat');
                
                //0.9.8
                $ruchPoint = $request->getParam('sheepla-widget-plruch-paczkomat');
                $xpressData = $request->getParam('sheepla-widget-plxpress-deliveryframetime');
                
                //0.9.9
                $pointPack = $request->getParam('sheepla-widget-plpointpack-paczkomat');
                $pocztaPolska = $request->getParam('sheepla-widget-plpocztapolska-paczkomat');
                $econt = $request->getParam('sheepla-widget-bgecont-point');
                
				//0.9.23
                $pocztaPolskav2 = $request->getParam('sheepla-widget-ppplpocztapolskav2-paczkomat');
				$crossborder = $request->getParam('sheepla-widget-crossborder-paczkomat');
                
            }
            //$qwPoint = $_request->getParam('sheepla-widget-rupickpoint-metro-station-id');
            //save sheepla order data
            
            $so = Mage::getModel('sheepla/order');
            
            $so->setQuoteId($address->getQuoteId());
            $so->setQuoteAddressId($address->getAddressId());
            $so->setRequiresSync(1);
            $so->setIsValid(null);
            $so->setLastError(null);

            $address_id = $address->getId();

            //save delivery options
            if (is_array($slMetro)) {
                $so->setDoRuShoplogisticsMetro(strip_tags($slMetro[$address_id]));
            } else {
                $so->setDoRuShoplogisticsMetro(strip_tags($slMetro));
            }
            if (is_array($imPoint)) {
                $so->setDoRuImPoint(strip_tags($imPoint[$address_id]));
            } else {
                $so->setDoRuImPoint(strip_tags($imPoint));
            }
            if (is_array($ppPoint)) {
                $so->setDoRuPickPointPoint(strip_tags($ppPoint[$address_id]));
            } else {
                $so->setDoRuPickPointPoint(strip_tags($ppPoint));
            }
            if (is_array($qpPoint)) {
                $so->setDoRuQiwiPostMachineId(strip_tags($qpPoint[$address_id]));
            } else {
                $so->setDoRuQiwiPostMachineId(strip_tags($qpPoint));
            }
            if (is_array($irm)) {
                $so->setDoPlInpostMachineId(strip_tags($irm[$address_id]));
            } else {
                $so->setDoPlInpostMachineId(strip_tags($irm));
            }
            
            
            //added in 0.8.8
            if (is_array($slPoint)) {
                $so->setDoRuShoplogisticsPoint(strip_tags($slPoint[$address_id]));
            } else {
                $so->setDoRuShoplogisticsPoint(strip_tags($slPoint));
            }
            if (is_array($cdekPoint)) {
                $so->setDoRuCdekPoint(strip_tags($cdekPoint[$address_id]));
            } else {
                $so->setDoRuCdekPoint(strip_tags($cdekPoint));
            }
            if (is_array($bbPoint)) {
                $so->setDoRuBoxberryPoint(strip_tags($bbPoint[$address_id]));
            } else {
                $so->setDoRuBoxberryPoint(strip_tags($bbPoint));
            }
            if (is_array($lbPoint)) {
                $so->setDoRuLogiboxPoint(strip_tags($lbPoint[$address_id]));
            } else {
                $so->setDoRuLogiboxPoint(strip_tags($lbPoint));
            }
            if (is_array($tdPoint)) {
                $so->setDoRuTopdeliveryPoint(strip_tags($tdPoint[$address_id]));
            } else {
                $so->setDoRuTopdeliveryPoint(strip_tags($tdPoint));
            }
            
            //added in 0.9.8
            if (is_array($ruchPoint)) {
                $so->setDoPlRuchPoint(strip_tags($ruchPoint[$address_id]));
            } else {
                $so->setDoPlRuchPoint(strip_tags($ruchPoint));
            }
            if (is_array($xpressData)) {
                $so->setDoPlXpressData(strip_tags($xpressData[$address_id]));
            } else {
                $so->setDoPlXpressData(strip_tags($xpressData));
            }
            
            //0.9.9
            if (is_array($pointPack)) {
                $so->setDoPlPointpackPoint(strip_tags($pointPack[$address_id]));
            } else {
                $so->setDoPlPointpackPoint(strip_tags($pointPack));
            }
            if (is_array($pocztaPolska)) {
                $so->setDoPlPocztapolskaPoint(strip_tags($pocztaPolska[$address_id]));
            } else {
                $so->setDoPlPocztapolskaPoint(strip_tags($pocztaPolska));
            }
            if (is_array($econt)) {
                $so->setDoBgEcontPoint(strip_tags($econt[$address_id]));
            } else {
                $so->setDoBgEcontPoint(strip_tags($econt));
            }
            
            
            if (is_array($pocztaPolskav2)) {
                $so->setDoPlPocztapolskav2(strip_tags($pocztaPolskav2[$address_id]));
            } else {
                $so->setDoPlPocztapolskav2(strip_tags($pocztaPolskav2));
            }
			
            if (is_array($crossborder)) {
                $so->setDoCrossborderPaczkomat(strip_tags($crossborder[$address_id]));
            } else {
                $so->setDoCrossborderPaczkomat(strip_tags($crossborder));
            }
            
            //eveything seems to ok, update sheepla order data
            if ($so != null) {
                $so->setIsValid(1);
                Mage::getSingleton('core/session')->setData('sheepla_specials', $so->getData());
            }
            
        } else {
            // Someone changed their mind so we have to delete sheepla object
            $so = Mage::getModel('sheepla/order')->load($address->getId(), 'quote_address_id');
            if ($so->getId()) {
                $so->delete();
            }
        }
    }

    //save sheepla data
    public function saveSheeplaData($observer) {
        //collect needed data
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $oData = $order->getData();

        $so = null;

        $quote = Mage::getModel('sales/quote')->load($oData['quote_id']);

        if ($quote->getIsMultiShipping()) {
            $items = $order->getAllItems();
            foreach ($items as $item) {
                $quote_address_id = Mage::getModel('sales/quote_address_item')->load($item->getQuoteItemId())->getQuoteAddressId();
                break;
            }
            $so = Mage::getModel('sheepla/order')->load($quote_address_id, 'quote_address_id');
        } else {
            $so = Mage::getModel('sheepla/order')->load($oData['quote_id'], 'quote_id');
        }
        
        if(!is_array(Mage::getSingleton('core/session')->getData('sheepla_specials'))) {
            
            return;
        }
        
        $so = Mage::getModel('sheepla/order')->setData(Mage::getSingleton('core/session')->getData('sheepla_specials'));
        $so->setOrderId($order->getIncrementId());
        $so->save();
        
        Mage::getSingleton('core/session')->setData('sheepla_specials', null);

        if ($so->getId() != null) {
            
            $irm = $so->getDoPlInpostMachineId();
            if (!empty($irm)) {
                $note = Mage::helper('sheepla')->__('Delivery to Inpost pick-up machine: ') . $irm;
                $order->addStatusHistoryComment($note);
                $order->save();
            }
            $qpPoint = $so->getDoRuQiwiPostMachineId();
            if (!empty($qpPoint)) {
                $note = Mage::helper('sheepla')->__('Delivery to Qiwi Post pick-up machine: ') . $qpPoint;
                $order->addStatusHistoryComment($note);
                $order->save();
            }
            $slMetro = $so->getDoRuShoplogisticsMetro();
            if (!empty($slMetro)) {
                $note = Mage::helper('sheepla')->__('Delivery to metro station: ') . $slMetro;
                $order->addStatusHistoryComment($note);
                $order->save();
            }
            $imPoint = $so->getDoRuImPoint();
            if (!empty($imPoint)) {
                $note = Mage::helper('sheepla')->__('Delivery to IM Logistics pick-up machine: ') . $imPoint;
                $order->addStatusHistoryComment($note);
                $order->save();
            }
            $ppPoint = $so->getDoRuPickPointPoint();
            if (!empty($ppPoint)) {
                $note = Mage::helper('sheepla')->__('Delivery to Pick Point pick-up machine: ') . $ppPoint;
                $order->addStatusHistoryComment($note);
                $order->save();
            }
            $ruchPoint = $so->getDoPlRuchPoint();
            if (!empty($ruchPoint)) {
                $note = Mage::helper('sheepla')->__('Delivery to Ruch point no.  ') . $ruchPoint;
                $order->addStatusHistoryComment($note);
                $order->save();
            }
            
            $pickPointPoint = $so->getDoPlPickpointPoint();
            if (!empty($pickPointPoint)) {
                $note = Mage::helper('sheepla')->__('Delivery to PickPoint: ') . $pickPointPoint;
                $order->addStatusHistoryComment($note);
                $order->save();
            }
            
            $pocztaPolskaPoint = $so->getDoPlPocztapolskaPoint();
            if (!empty($pocztaPolskaPoint)) {
                $note = Mage::helper('sheepla')->__('Delivery to PocztaPolska point: ') . $pocztaPolskaPoint;
                $order->addStatusHistoryComment($note);
                $order->save();
            }
            
            $pocztaPolskav2 = $so->getDoPlPocztapolskav2();
            if (!empty($pocztaPolskav2)) {
                $note = Mage::helper('sheepla')->__('Delivery to PocztaPolska point: ') . $pocztaPolskav2;
                $order->addStatusHistoryComment($note);
                $order->save();
            }
            
            $econtPoint = $so->getDoBgEcontPoint();
            if (!empty($econtPoint)) {
                $note = Mage::helper('sheepla')->__('Delivery to Econt point: ') . $econtPoint;
                $order->addStatusHistoryComment($note);
                $order->save();
            }
			
			$crossborderPoint = $so->getCrossborderPaczkomat();
            if (!empty($crossborderPoint)) {
                $note = Mage::helper('sheepla')->__('Delivery to Crossborder point: ') . $crossborderPoint;
                $order->addStatusHistoryComment($note);
                $order->save();
            }
        }
    }

    public function sendSheeplaData($observer) {
        $event = $observer->getEvent();
        $orders = $event->getOrders();
        if (!empty($orders)) {
            foreach ($orders as $order) {
                $this->_sendSheeplaData($order);
            }
        } else {
            $order = $event->getOrder();
            $this->_sendSheeplaData($order);
        }
    }

    private function _sendSheeplaData($order) {
        if (preg_match('/^sheepla/', $order->getShippingMethod())) {
            $this->initSheeplaObjects();
            try {
                $sent_order = $this->sp->sendOrder($order, Orba_Sheepla_Model_Observer::SHEEPLA_TIMEOUT);
            } catch (Exception $e) {
                if ($e->getCode() == 99) {
                    Mage::log('Orba_Sheepla_Model_Observer::sendSheeplaData Timeout #' . $order->getIncrementId(), null, 'sheepla.log');
                } else {
                    throw $e;
                }
            }
            if (isset($sent_order) && !empty($sent_order)) {
                $or = array_pop($sent_order);
                if (!empty($or['orderId'])) {
                    $so = Mage::getModel('sheepla/order')->load($or['orderId'], 'order_id');
                    $so->setSyncFields($or);
                } else {
                    Mage::Log('[Sheepla][Error][Orba_Sheepla_Model_Observer::sendSheeplaData] Invalid response from sendOrder.');
                }
            }
        }
    }

    public function rememberOldSheeplaData($observer) {
        $event = $observer->getEvent();
        $order = $event->getOrder();
        if ($order->getId() && preg_match('/^sheepla/', $order->getShippingMethod())) {
            $so = Mage::getModel('sheepla/order')->load($order->getIncrementId(), 'order_id');
            $data = $so->getData();
            Mage::getSingleton('adminhtml/session_quote')->setOldSheeplaData($data);
        }
    }

    protected function initSheeplaObjects() {
        $this->sheeplaProxyModel = Mage::getModel('sheepla/proxy');
        $this->sp = $this->sheeplaProxyModel->getProxy();
        Mage::log('SheeplaProxy instance class:' . get_class($this->sp));
        return $this->sp;
    }

}
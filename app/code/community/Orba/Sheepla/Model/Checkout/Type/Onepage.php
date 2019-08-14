<?php
/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
class Orba_Sheepla_Model_Checkout_Type_Onepage  extends Mage_Checkout_Model_Type_Onepage{
		
	public function saveShippingMethod($shippingMethod){
    	 if (empty($shippingMethod)) {
            return array('error' => -1, 'message' => Mage::helper('sheepla')->__('Invalid shipping method.'));
        }
		
		$so = null;
		$sTemp = explode('_',$shippingMethod);
		
		//is it sheepla's method
		if($sTemp[0] == 'sheepla'){
			
			//if so get check if $method is available with current shipping method
			$cString =  "sheepla/{$sTemp[1]}_{$sTemp[2]}";
			$conf = Mage::getStoreConfig( $cString );
			
			$_request = Mage::app()->getRequest(); 
			
			//read delivery option
			$ire = strip_tags($_request->getParam('sheepla-widget-plinpost-email'));  
			$irm = strip_tags($_request->getParam('sheepla-widget-plinpost-paczkomat'));  
			$irp = strip_tags($_request->getParam('sheepla-widget-plinpost-phone-number'));
			$ira = strip_tags($_request->getParam('sheepla-widget-plinpost-form-agree-checkbox'));
			
			$slMetro =  strip_tags($_request->getParam('sheepla-widget-rushoplogistics-metro-station'));
			$imPoint =  strip_tags($_request->getParam('sheepla-widget-ruimlogistics-paczkomat'));
			$ppPoint =  strip_tags($_request->getParam('sheepla-widget-rupickpoint-paczkomat'));
			
			//$qwPoint =  strip_tags($_request->getParam('sheepla-widget-rupickpoint-metro-station-id'));
			
			//save sheepla order data
    		$so =  Mage::getModel('sheepla/order')->load($this->getQuote()->getId(), 'quote_id');
			
			$so->setQuoteId($this->getQuote()->getId());
			$so->setRequiresSync(1);
			$so->setIsValid(null);
			$so->setLastError(null);
			
			//save delivery options
			$so->setDoRuShoplogisticsMetro($slMetro);
			$so->setDoRuImPoint($imPoint);
			$so->setDoRuPickPointPoint($ppPoint);
			
			$so->setDoPlInpostMachineId($irm);
			$so->setDoPlInpostMobile($irp);
			$so->setDoPlInpostEmail($ire);
			
			$so->setDoPlInpostEmail($ire);
			$so->setDoPlInpostEmail($ire);
			$so->setDoPlInpostEmail($ire);
			
			
			
			$so->save();
			
			//if this is an inpost shipping add a note
			if(!empty($irm)){
					 
				$note =  Mage::helper('sheepla')->__('Delivery to Inpost pick-up machine:').$irm;
				$this->getQuote()->setCustomerNote($note); 
			        		
			}
			
			//eveything seems to ok, update sheepla order data
			if($so != null){
				$so->setIsValid(1);
				$so->save();
			}
			
		}
		
		
		return parent::saveShippingMethod($shippingMethod);
    }


};
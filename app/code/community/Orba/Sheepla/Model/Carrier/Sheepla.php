<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
class Orba_Sheepla_Model_Carrier_Sheepla extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {
    
    /**
     * unique internal shipping method identifier
     *
     * @var string [a-z0-9_]
     */
    protected $_code = 'sheepla';
    protected $sheeplaProxyModel;
    protected $sp;
    protected $storeId;
    
    /**
     * Collect rates for this shipping method based on information in $request
     *
     * @param Mage_Shipping_Model_Rate_Request $data
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
        
        // skip if not enabled
        if( !Mage::getStoreConfig( 'carriers/' . $this->_code . '/active' , $this->getStoreId() )  ) {
            return false;
        }
        
        
        
        $packageValue = $request->getBaseCurrency()->convert($request->getPackageValue(), $request->getPackageCurrency());
        $methods = array();
        
        
        
        //get sheepla module config
        $sConfig = Mage::getStoreConfig( 'sheepla' , $this->getStoreId() );
        $dpRates = false;
        $checkoutPricingHashSession = null;
        
        
        
        //if user is using dynamic pricing
        if ($sConfig['advanced']['use_dynamic_pricing']) {
            
            //support admin and user checkout
            if( Mage::getSingleton('admin/session')->isLoggedIn() ) { //admin
                $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
            } else { //user
                $quote = Mage::getSingleton('checkout/session')->getQuote();
            }
            
            
            $address = $quote->getShippingAddress();
            $addressString = $address->getCountryId() . $address->getPostcode() . $address->getCity() . $address->getStreetFull();
            
            $_items = $quote->getAllVisibleItems();
            $items = array();
            
            foreach($_items as $k => $item) {
                if($item->getOptionByCode('simple_product')) { 
                    $items[] = array(
                        'product' => $item->getOptionByCode('simple_product')->getProduct(),
                        'quoteItem' => $item
                    );
                } else {
                    $items[] = array(
                        'product' => $item->getProduct(),
                        'quoteItem' => $item
                    );
                }
            }
            $dpCacheChecksum = md5( json_encode( (array)$items ) . $quote->getItemsSummaryQty() . $addressString );
            
            $sessionStorage = Mage::getModel( 'core/session' );
            $discount = Mage::getModel('sheepla/discount');
            $discount->setQuote($quote);
            
            if( is_array( $dpCacheData = $sessionStorage->getData( 'sheepla_dynamicpricing_cache' ) ) &&
                isset( $dpCacheData[ 'checksum' ] ) &&
                $dpCacheData[ 'checksum' ] == $dpCacheChecksum ) {
                
                $availableDynamicTemplateIds = $dpCacheData[ 'availableDynamicTemplateIds' ];
                $checkoutPricingHashSession = $dpCacheData[ 'checkoutPricingHashSession' ];
                $dpRates = $dpCacheData[ 'dpRates' ];
                
            } else {
                
                try {
                    
                    $this->initSheeplaObjects();
                    $dpResponse = $this->sp->getClient()->getDynamicPricing($address, $items, $discount);
                    
                    if (array_key_exists('deliveryMethods', $dpResponse)) {
                        
                        $dpRates = $dpResponse['deliveryMethods'];
                    }
                    
                    if( array_key_exists( 'checkoutPricingHashSession' , $dpResponse ) ) {
                        
                        $checkoutPricingHashSession = $dpResponse[ 'checkoutPricingHashSession' ];
                    }
                    
                    $availableDynamicTemplateIds = array(); 
                    
                    
                    if( count( $dpRates ) ) {
                        
                        foreach( $dpRates as $rate ) {

                            $availableDynamicTemplateIds[] = $rate[ 'shipmentTemplateId' ];
                        }
                    }
                    
                        
                    $sessionStorage->setData( 'sheepla_dynamicpricing_cache' , array(
                        'checksum' => $dpCacheChecksum,
                        'availableDynamicTemplateIds' => $availableDynamicTemplateIds,
                        'checkoutPricingHashSession' => $checkoutPricingHashSession,
                        'dpRates' => $dpRates
                    ));
                    

                } catch (Exception $e) {
                    
                    $dpRates = false;
                    Mage::Log('[Sheepla][' . date('Y-m-d H:i:s') . ']' . $e->getMessage());
                }  
            }
        } 
        
        
        foreach( $sConfig as $configKey => $configRowData ) {
            
            if( !preg_match( '/^method_([0-9]+)$/' , $configKey , $matches ) ) {
                
                continue;
            }
            
            $shippingMethodNumber = $matches[ 1 ];
            $i = $shippingMethodNumber;
            
            
            if ($this->getMethodConfig('allowspecific', $i)) {
                $availableCountries = explode(',', $this->getMethodConfig('specificcountry', $i));
                if (!($availableCountries && in_array($request->getDestCountryId(), $availableCountries))) {
                    continue;
                }
            }

            $shippingName = $this->getMethodConfig('name', $i);
            $shippingPrice = $this->getMethodConfig('price', $i);
            
             if (!empty($shippingName) && $this->getMethodConfig('enabled', $i)) {
                
                //if dynamic pricing enabled and method is not at dpRates table, skip this method
                if (is_array($dpRates)) {
                    $shippingTemplateId = $this->getMethodConfig('template_id', $i);
                    if ($shippingTemplateId != '0' && !in_array($shippingTemplateId, $availableDynamicTemplateIds)) {
                        continue;
                    }
                }
               
                $method = Mage::getModel('shipping/rate_result_method');
                $method->setCarrier('sheepla');
                $method->setMethod('method_' . $i);
                $method->setMethodTitle($this->getMethodConfig('name', $i));
                //$method->setMethodDetails();
                $method->setMethodDescription($checkoutPricingHashSession);
                $method->setPrice(preg_replace('/,/','.',$shippingPrice));
                $method->setCost($this->getMethodConfig('cost', $i));
                
                //dynamic price calculate
                if ($request->getFreeShipping() === true || $request->getPackageQty() == $this->getFreeBoxes()) {
                    $method->setPrice('0.00');
                } elseif (is_array($dpRates)) {
                    foreach ($sConfig as $k => $v) {
                        if ($method->getMethod() == $k) {
                            foreach ($dpRates as $rule) {
                                if ($rule['shipmentTemplateId'] == $v['template_id']) {
                                    $method->setPrice($rule['price']);
                                }
                            }
                        }
                    }
                }
                
                $methodOrder = (int)$this->getMethodConfig('sort_order', $i);
                $methods[] = array( 'order' => $methodOrder , 'method' => clone $method );
            }
        }
        
		for($currentNum=0;$currentNum<count($methods);$currentNum++) {

			$nextNum = $currentNum+1;
			
			if(isset($methods[$nextNum])) {
				$current = $methods[$currentNum];
				$next = $methods[$nextNum];
				
				if((int)$next['order'] < (int)$current['order']){
					
					$methods[$currentNum] = $next;
					$methods[$nextNum] = $current;
					
					if($currentNum >= 1) {
						
						$currentNum -= 2;
					}
				}
			}
		}
		
		
        $flatMethodsSet = array();
        
        foreach( $methods as $method ) {
            $flatMethodsSet[] = $method[ 'method' ];
        }
        
        return $this->createShippingRate( $flatMethodsSet );
    }
        
    
    /**
     * Tworzenie obiektu shipping/rate_result dla podanych metod distawy
     * @param array $methods tablica obiektów Mage_Shipping_Model_Rate_Result_Method
     * @return Mage_Shipping_Model_Rate_Result
     */
    protected function createShippingRate( $methods) {
        
        $result = Mage::getModel('shipping/rate_result');
        
        foreach ( $methods as $method ) {
            
            $result->append( $method );
        }
        
        return $result;
    }
    

    protected function getMethodConfig($key, $mNo) {
        
        $cString = "method_{$mNo}/{$key}";
        return Mage::getStoreConfig( 'sheepla/' . $cString , $this->getStoreId() );
    }
    

    public function getAllowedMethods() {

        $methods = array();

        $sheeplaConfig = Mage::getStoreConfig( 'sheepla' , $this->getStoreId() );

        foreach( $sheeplaConfig as $configKey => $configRow ) {

            if( preg_match( '/method_([0-9]+)/' , $configKey , $matches ) ) {
                $i = $matches[ 1 ];

                if( !$this->getMethodConfig( 'enabled' , $i ) ) continue;

                $methods[ 'method_' . $i ] = $this->getMethodConfig( 'name' , $i );
            }
        }

        return $methods;
    }

    public function getTrackingInfo($trackingNo) {

        $this->initSheeplaObjects();

        $sd = $this->sp->getClient()->getShipmentDetails($trackingNo);

        $edtn = $sd[0]['edtn'];
        $ctn = $sd[0]['ctn'];
        $carrier = $sd[0]['carrier'];

        $tn = trim($trackingNo);
        if (!empty($ctn)) {
            $tn = $tn . " (Numer przewoźnika {$carrier}: {$ctn})";
        }

        $result = Mage::getModel('shipping/tracking_result');

        $status = Mage::getModel('shipping/tracking_result_status');
        $status->setCarrier('sheepla');
        $status->setCarrierTitle($sd[0]['carrier']);
        $status->setTracking($edtn);
        $status->setPopup(1);

        //tracking url
        $urlBase = 'http://panel.sheepla.pl';
        if ($sd[0]['sender']['countryCode'] == "RU") {
            $urlBase = 'http://panel.sheepla.ru';
        }

        $status->setUrl($urlBase . "/Guest/TrackShipment/" . trim($trackingNo));
        $result->append($status);


        return $status;
    }

    protected function initSheeplaObjects() {

        $this->sheeplaProxyModel = Mage::getModel('sheepla/proxy');
        $this->sp = $this->sheeplaProxyModel->getProxy();
        return $this->sp;
    }
    
    protected function getStoreId() {
        
        if( null == $this->storeId ) {
            
            if( Mage::getSingleton('admin/session')->isLoggedIn() ) { //admin
                $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
            } else { //user
                $quote = Mage::getSingleton('checkout/session')->getQuote();
            }
            
            $this->storeId = $quote->getStoreId();
        }
        
        return $this->storeId;
        
    }
}
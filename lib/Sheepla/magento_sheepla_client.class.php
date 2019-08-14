<?php

/**
 * Sheepla XML API client class is a class made for sending requests from the shop in to the sheepla application via XML API
 * and to support basic configuration screens of the shop
 * @author Orba (biuro{at}orba.pl)
 * @requirements:
 * 	- stream_context_create() 	(http://php.net/manual/en/function.stream-context-create.php) \
 * 	- file_get_contents()  		(http://php.net/manual/en/function.file-get-contents.php)
 *  - DOMDocument 				(http://php.net/manual/en/class.domdocument.php)
 *  - simplexml_load_string 	(http://php.net/manual/en/function.simplexml-load-string.php)
 *  - json_encode 				(http://php.net/manual/en/function.json-encode.php)
 *  - json_decode 				(http://php.net/manual/en/function.json-decode.php)
 *  - hash (with 'sha256' algorithm) (http://php.net/manual/en/function.hash.php)
 */
class MagentoSheeplaClient extends SheeplaClient {

    protected $timeout = 300;
    public $forceDebugFlag;

    public function __construct($config = null, $debug = false) {
        parent::__construct($config, $debug);
    }

    /**
     * If object is in debug mode it returns all debug notifications to standard output
     * @param string $data
     */
    protected function debug() {
        if ($this->debugFlag === true) {
            $arg_list = func_get_args();
            foreach ($arg_list as $v) {
                Mage::Log('[Sheepla][' . date('Y-m-d H:i:s') . ']' . print_r($v, true));
                if ($this->forceDebugFlag) {
                    echo date('Y-m-d H:i:s') . ' : <pre>' . htmlentities(print_r($v, true)) . "</pre> <br />\n\r";
                }
            }
        }
    }

    public function setForceDebug($bool) {
        $this->debugFlag = $bool;
        $this->forceDebugFlag = $bool;
    }

    public function setTimeout($timeout) {
        $this->timeout = $timeout;
    }

    protected function getTimeout() {
        return $this->timeout;
    }

    protected function send($method, $request, $timeout = null) {
        $this->debug('Preparing send method.');

        if (is_null($timeout)) {
            $timeout = $this->getTimeout();
        }

        if (!$this->isValidConfig($this->config)) {
            throw new Exception('Invalid config: ' . var_export($this->config, true));
        }

        $result = false;

        if (function_exists('curl_init') && function_exists('curl_setopt') && function_exists('curl_exec')) {
            $result = $this->sendCurl($method, $request, $timeout);
        } else {
            $function_sel = (function_exists('ini_get') ? 'ini_get' : (function_exists('get_cfg_var') ? 'get_cfg_var' : false));
            if ($function_sel !== false) {
                if (call_user_func($function_sel, 'allow_url_fopen')) {
                    $result = $this->sendStandard($method, $request, $timeout);
                } else {
                    throw new Exception('There is no function allowed to send data please add CURL or allow file_get_contents to use remote URL');
                }
            } else {
                $this->debug('I can\'t check the PHP configuration trying to send any way');
                $result = $this->sendStandard($method, $request, $timeout);
            }
        }
        
        $this->debug('received response:', $result);
        
        if ($result === false) {
            throw new Exception('No data recived from Sheepla because of timeout.', 99);
        }
        
        return $result;
    }

    protected function sendStandard($method, $request, $timeout = 10) {
        $this->debug('Using file_get_contents to send');
        $result = false;
        if (strlen($request) > 0) {
            $context = stream_context_create(array('http' => array(
                    'method' => "POST",
                    'header' => "Content-Type: text/xml; charset=utf-8",
                    'content' => $request,
                    'content-length' => strlen($request),
                    'timeout' => $timeout
            )));
        } else {
            $context = stream_context_create(array('http' => array(
                    'method' => "GET",
                    'header' => "Content-Type: text/xml; charset=utf-8",
                    'timeout' => $timeout
            )));
        }
        $this->debug('sending request to this url: ', $this->config['url'] . $method, 'the request:', $request);
        try {
            Mage::log('Start curl_exec \'' . $method . "' with request:\n\r" . $request . "\n", null, 'sheepla.log');
            $result = file_get_contents($this->config['url'] . $method, false, $context);
            Mage::log('Stop curl_exec \'' . $method . "' with result:\n\r" . $result . "\n", null, 'sheepla.log');
        } catch (Exception $e) {
            throw new Exception("The function file_get_contents doesn't exists.");
        }
        return $result;
    }

    protected function sendCurl($method, $request, $timeout = 10) {

        $this->debug('Using CURL to send');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->config['url'] . $method);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        $this->debug('sending request to this url: ', $this->config['url'] . $method, 'the request:', $request);
        Mage::log('Start curl_exec \'' . $method . "' with request:\n\r" . $request . "\n", null, 'sheepla.log');
        $result = curl_exec($ch);
        Mage::log('Stop curl_exec \'' . $method . "' with result:\n\r" . $result . "\n", null, 'sheepla.log');
        $this->clearSheeplaLog();
        return $result;
    }

    protected function createRequestDom($requestName, $includeAuth = false, $cultureId = false) {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $root = $dom->createElementNS("http://www.sheepla.pl/webapi/1_0", $requestName);
        $dom->appendChild($root);
        if ($includeAuth) {
            $authNode = $dom->createElement("authentication");
            $authNode->appendChild($dom->createElement("apiKey", $this->config['key']));
            $root->appendChild($authNode);
        }
        if ($cultureId) {
            $culture_id = $dom->createElement("cultureId", $cultureId);
            $root->appendChild($culture_id);
        }
        $this->document = $dom;
        return $dom;
    }

    /**
     * Gets shipments status changes since the particular datetime
     * @param array $data
     *      string lastRequestDatetime                  // datetime in UTC in any format
     * @return array
     */
    public function getShipmentsStatusChanges($data = array()) {
        $dom = $this->createRequestDom('getShipmentsStatusChangeRequest', true, '1045');
        $root = $dom->documentElement;
        if (isset($data['lastRequestDatetime'])) {
            $filters = $dom->createElement('filters');
            $filters->appendChild($dom->createElement('lastRequestDatetime', $this->convertDateFromMagento($data['lastRequestDatetime'])));
            $root->appendChild($filters);
        }
        $xml = $dom->saveXML();
        $response = $this->toSimpleXMLElement($this->send('getShipmentsStatusChange', $xml));
        if (isset($response->errors)) {
            throw new Exception('The response XML structure is wrong');
        }
        $results = array();
        if (isset($response->shipments) && isset($response->shipments->shipment)) {
            foreach ($response->shipments->shipment as $shipment) {
                if (isset($shipment->externalOrderId) && isset($shipment->statusHistory) && isset($shipment->statusHistory->status)) {
                    $order_id = (string) $shipment->externalOrderId;
                    $results[$order_id] = array();
                    foreach ($shipment->statusHistory->status as $status) {
                        if (isset($status->statusId) && isset($status->statusChangeId) && isset($status->statusChangedDatetime)) {
                            $status_change = array(
                                'status_id' => (int) $status->statusId,
                                'status_change_id' => (int) $status->statusChangeId,
                                'datetime' => $this->convertDateToMagento((string) $status->statusChangedDatetime)
                            );
                            if (!empty($status->subStatusId)) {
                                $status_change['substatus_id'] = (int) $status->subStatusId;
                            }
                            $results[$order_id][$status_change['status_change_id']] = $status_change;
                        }
                    }
                }
            }
        }
        return $results;
    }
    
    
    /**
     * getShipmentDetails gets shipments fill data from Sheepla by specified query. 
     * You can call getShipmentDetails with list of edtn or ctn for single shipment or call getShipmentDetails with list of externalOrdeIdList( Your shop order id ) to get all shipments assigned to thi order;
     * This metgod return flat list with data received from Sheepla;
     * 
     * Example:
     * getShipmentDetails( array( 1 , 2 ) , array( 3 , 4 ) , ( 500000001 , 500000002 ) );
     * 
     * result:
     * array(
     *      0 => array( 
     *         externalOrderId => 100000001,
     *         edtn => 1,
     *         ...
     *      ),
     *      1 => array(
     *         externalOrderId => 500000001,
     *         edtn => 2
     *         ctn => 3,
     *         ...
     *      ),
     *      2 => array(
     *         externalOrderId => 500000002,
     *         edtn => 3,
     *         ctn => 4,
     *         ...
     *      ),
     *      3 => array(
     *         externalOrderId => 500000002,
     *         edtn => 4,
     *         ctn => 5,
     *         ...
     *      )
     *);
     * 
     * 
     * @param array $edtnList
     * @param array $ctnList
     * @param attay $externalOrdeIdList
     * @param int $cultureId
     * @return array|false
     * @throws Exception
     */
    public function getShipmentDetails( $edtnList = array() , $ctnList = array() , $externalOrdeIdList = array() , $cultureId = '1045' ) {
        
        $request = $this->createRequestDom( 'getShipmentDetailsRequest' , true , $cultureId );
        $isData = false;
        
        if( count( $edtnList ) ) {
            $edtns = $request->createElement( 'edtns' );
            
            foreach( $edtnList as $edtn ) {
                
                $edtnElement = $request->createElement( 'edtn' , $edtn );
                $edtns->appendChild( $edtnElement );
            }
            
            $request->documentElement->appendChild( $edtns );
            $isData = true;
        }
        
        if( count( $ctnList ) ) {
            $ctns = $request->createElement( 'ctns' );
            
            foreach( $ctnList as $ctn ) {
                
                $ctnElement = $request->createElement( 'edtn' , $ctn );
                $ctns->appendChild( $ctnElement );
            }
            
            $request->documentElement->appendChild( $ctns );
            $isData = true;
        }
        
        if( count( $externalOrdeIdList ) ) {
            $externalOrderIds = $request->createElement( 'externalOrderIds' );
            
            foreach( $externalOrdeIdList as $externalOrderId ) {
                
                $externalOrderIdElement = $request->createElement( 'externalOrderId' , $externalOrderId );
                $externalOrderIds->appendChild( $externalOrderIdElement );
            }
            
            $request->documentElement->appendChild( $externalOrderIds );
            $isData = true;
        }
        
        if( $isData ) {
            
            try {
                
                $response = $this->toSimpleXMLElement( $this->send( 'getShipmentDetails' , $request->saveXML() ) );
                
                if ( isset( $response->errors ) ) {
                    
                    throw new Exception('The response XML structure is wrong');
                }
                
                
                if( !isset( $response->shipments->shipment ) ) {
                    return false;
                }
                
                
                $shipments = array();
                
                
                foreach( $response->shipments->shipment as $shipment ) {
                    
                    $shipments[] = $this->simpleXmlToArray( $shipment );
                }
                
                return $shipments;
                
            } catch( Exception $e ) {
                
                throw new Exception( 'Sending request failed' );
            }
        }
        
        return false;
    }
    
    protected function simpleXmlToArray( SimpleXMLElement $element ) {
        
        $result = array();
        
        if( $element->children() ) {
            
            foreach( $element->children() as $child ) {
                
                $result = array_merge( $result , $this->simpleXmlToArray( $child ) );
            } 
            
        } else {
            
            $result[ $element->getName() ] = $element->__toString();
        }
        
        return $result;
    }

    public function convertDateFromMagento($datetime) {
        $time = strtotime($datetime);
        return date('Y-m-d', $time) . 'T' . date('H:i:s', $time);
    }

    public function convertDateToMagento($datetime) {
        return date('Y-m-d H:i:s', strtotime($datetime));
    }

    public function sendStoreInfo($data) {

        $dom = $this->createRequestDom('storeInfo', false);
        $root = $dom->documentElement;
        $html = array();

        $authentication = $dom->createElement('authentication');
        $authentication->appendChild($dom->createElement('apiKey', $this->config['key']));
        $root->appendChild($authentication);

        $platform = $dom->createElement('platform');
        $platform->appendChild($dom->createElement('name', $data['platform_name']));
        $platform->appendChild($dom->createElement('version', $data['platform_version']));
        $root->appendChild($platform);

        $sheepla = $dom->createElement('module');
        $sheepla->appendChild($dom->createElement('version', $data['sheepla_version']));

        $config = $dom->createElement('config');
        foreach ($data['sheepla_config'] as $k => $v) {
            $configElement = $dom->createElement($k);
            ksort($v);
            foreach ($v as $k2 => $v2) {
                $configElement->appendChild($dom->createElement($k2, $v2));
            }
            $config->appendChild($configElement);
            unset($configElement);
        }
        $sheepla->appendChild($config);

        $root->appendChild($sheepla);

        $server = $dom->createElement('server');
        foreach ($_SERVER as $k => $v) {
            $server->appendChild($dom->createElement(strtolower($k), $v));
        }
        $root->appendChild($server);

        $xml = $dom->saveXML();
        $this->debug('Request: ', $xml);

        return 'no response from sheepla yet';

        //@TODO - log in sheepla
        $data = $this->toSimpleXMLElement($this->send('StoreInfo', $xml));

        if ((isset($data->errors) && !empty($data->errors)) || (isset($data->error) && !empty($data->error))) {
            return false;
        }
        return $html;
    }

public function getDynamicPricing($addressObj = null, $productsArr = null, $discount = null) {
        
        //validation
        
        
        if (!is_object($addressObj) && !is_array($productsArr)) {
            return false;
        }
        
        
        $street = $this->filterXmlStr($addressObj->getStreetFull());
        $city = $this->filterXmlStr($addressObj->getCity());
        $zip = $this->filterXmlStr($addressObj->getPostcode());
        
        if (is_null($city) || is_null($zip))
            return false;

        $dom = $this->createRequestDom('checkoutPricingRequest', false);
        $root = $dom->documentElement;
        $html = array();

        $authentication = $dom->createElement('authentication');
        $authentication->appendChild($dom->createElement('apiKey', $this->config['key']));
        $root->appendChild($authentication);

        $orderDate = $dom->createElement('orderDate', date('c'));
        $root->appendChild($orderDate);
        
        if(null !== $discount &&
           $discount->getType()) {
            
            $root->appendChild($dom->createElement('orderDiscountValue', $discount->getValue()));
            $root->appendChild($dom->createElement('orderDiscountType', $discount->getType()));
        }
        
        $deliveryAddress = $dom->createElement('deliveryAddress');
        $deliveryAddress->appendChild($dom->createElement('street', mb_convert_encoding($street, 'utf-8')));
        $deliveryAddress->appendChild($dom->createElement('city', mb_convert_encoding($city, 'utf-8')));
        $deliveryAddress->appendChild($dom->createElement('zipCode', mb_convert_encoding($zip, 'utf-8')));
        $deliveryAddress->appendChild($dom->createElement('countryCode', $addressObj->getCountryId()));
        $root->appendChild($deliveryAddress);
        
        
        $products = $dom->createElement('products');

        
        /** 11.06.2014 dpanek v 0.9.11 **/
        $allowedAttributes = explode(',', Mage::helper( 'sheepla/data' )->getConfigData( 'sheepla/advanced/attributes_to_send_id_dp' ));
        $allowedAttributes = array_merge(Mage::helper( 'sheepla/data' )->getRequiredAttributesList(), $allowedAttributes);
        
        $restrictAttributes = (bool)Mage::helper('sheepla/data')->getConfigData('sheepla/advanced/allow_specific_attributes_to_send_id_dp');
        
        foreach($productsArr as $mItem) {
            
            $product = $dom->createElement('product');
            
            if($restrictAttributes) {
                
                $item = $this->getDPItemInfo($mItem, $allowedAttributes);
                
            } else {
                
                $item = $this->getDPItemInfo($mItem);
            }
            
            
            /** 11.06.2014 dpanek v 0.9.11 **/
            if( $restrictAttributes ) {
                
                
                foreach( $item as $attrName => $value ) {

                    if( !in_array( $attrName , $allowedAttributes ) ) {

                        unset( $item[ $attrName ] );
                    }
                }
                
            }
            
            $attribute = $this->arrayToXMLAttribute($item, $dom, $product);
            $products->appendChild($product);
        }
        
        
        $root->appendChild($products);
        $xml = $dom->saveXML();
        
        $response = $this->send('CheckoutPricing', $xml);
        
        $data = $this->toSimpleXMLElement( $response );
        
        if ((isset($data->errors) && !empty($data->errors)) || (isset($data->error) && !empty($data->error))) {
            return false;
        } else {
            //get dp hash if exist
            if (isset($data->checkoutPricingHashSession)) {
                $html['checkoutPricingHashSession'] = (string) $data->checkoutPricingHashSession;
            }
            foreach ($data->deliveryMethods as $methods) {
                foreach ($methods as $method) {
                    $html['deliveryMethods'][] = (array) $method;
                }
            }
        }

        return $html;
    }

    protected function getDPItemInfo($itemData, $specificSet = null) {
        
        $dpItem = array();
        
        //get categories names
        $item = $itemData['product'];
        $catIds = $item->getCategoryIds();
        $catNames = array();
        foreach ($catIds as $catId) {
            
            $catNames[] = Mage::getModel('catalog/category')->load($catId)->getName();
        }
        
        $dpItem['category'] = implode(';', $catNames);
        $dpItem['price_incl_tax'] = $item->getFinalPrice();
        $dpItem['qty'] = $itemData['quoteItem']->getQty();
        
        $attributes = $item->getAttributes();
        
        foreach ($attributes as $k => $v) {
            if( is_array($specificSet) &&
                !in_array($v->getAttributeCode(),$specificSet)) {
                
                continue;
            }
            
            if ($v->getAttributeModel() == 'catalog/resource_eav_attribute' && $v->getAttributeCode() != "category_ids") { //disable category_ids
                $value = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getId(), $v->getAttributeCode(), Mage::app()->getStore());
                $allOptions = Mage::getModel('eav/config')->getAttribute('catalog_product', $v->getAttributeCode())->getSource()->getAllOptions();
                
                //assign 0 for boolean hidden by default values
                if (empty($value)) {
                    if ($v->getSourceModel() == 'eav/entity_attribute_source_boolean') {
                        $value = '0';
                    } else {
                        continue;
                    }
                }
                
                //EAV Boolean Attribute Fix
                if ($v->getSourceModel() == 'eav/entity_attribute_source_boolean') {
                    sort($allOptions);
                } else {
                    foreach ($allOptions as $opt) {
                        if ($value == $opt['value']) {
                            $value = $opt['label'];
                        }
                    }
                }
                
                $dpItem[$v->getAttributeCode()] = str_replace('"', '\"', $value);
            }
        }
        
        ksort($dpItem);

        //replace & and <> chars for the valid XML syntax
        foreach ($dpItem as $k => $v) {
            $dpItem[$k] = str_replace(array('&', '<', '>'), ' ', $v);
        }

        return $dpItem;
    }
    
    private function clearSheeplaLog() {

        $sheeplaLogFile = Mage::getBaseDir('log') . '/sheepla.log';
        /** Checking filesize and delete 30% it if needed. */
        if (filesize($sheeplaLogFile) > 1024000) {
            $contents = @file_get_contents($sheeplaLogFile);
            $cutPosition = strlen(substr($contents, 0, (int) strlen($contents) / 3)); //delete 30%
            @file_put_contents($sheeplaLogFile, substr($contents, $cutPosition, strlen($contents)));
        }
    }

    private function filterXmlStr($str) {
        return preg_replace('/&|<|>|\n|\r/', ' ', $str);
    }

    
}

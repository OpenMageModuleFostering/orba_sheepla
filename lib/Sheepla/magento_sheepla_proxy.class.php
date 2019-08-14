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
class MagentoSheeplaProxy extends SheeplaProxy {

    public $forceDebugFlag;

    public function __construct($config = null, $model = null, $client = null, $debug = false) {
        parent::__construct($config, $model, $client, $debug);
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
    
    public function sendOrder($order, $timeout = null, $forceSync = false) {
        $response = array();
        if (is_null($this->client) || is_null($this->config) || is_null($this->model)) {
            throw new Exception("Not all required values are set: " . (is_null($this->client) ? 'pleas use setClient method ' : '') . (is_null($this->model) ? 'pleas use setModel method ' : '') . (is_null($this->config) ? 'pleas use setConfig method ' : ''));
        }
        
        if ($forceSync) {
            $orders = $this->model->getForceOrder($order);
        } else {
            $orders = $this->model->getOrder($order);
        }
       
        $this->debug('Orders data to be synced: ', $orders);
        if (!empty($orders)) {
            if ($timeout) {
                $this->client->setTimeout($timeout);
            }
            $re = $this->client->createOrders($orders);
            if (isset($re['errors'])) {
                throw new Exception(print_r($re, true));
            }
            if (is_array($re)) {
                foreach ($re as $or) {
                    if (isset($or['errors']) && !empty($or['errors'])) {
                        $response[] = array(
                            'orderId' => $or['externalOrderId'],
                            'sheeplaOrderId' => 0,
                            'status' => 'error',
                            'errors' => $or['errors']
                        );
                    } else {
                        $response[] = array(
                            'orderId' => $or['externalOrderId'],
                            'sheeplaOrderId' => $or['orderId'],
                            'status' => 'ok',
                            'errors' => null
                        );
                    }
                }
            } else {
                throw new Exception('Invalid response from sheepla' . print_r($re, true));
            }
        } else {
            $this->debug('No orders to be synced');
        }
        return $response;
    }
    
    
    public function getStoreInfo( $storeCode = null ) {
        
        $moduleConfig = Mage::getStoreConfig( 'sheepla' , $storeCode );
        
        
        $data = array(
            'platform_name' => 'Magento',
            'platform_version' => Mage::getVersion(),
            'sheepla_version' => Mage::helper('sheepla')->getExtensionVersion(),
            'sheepla_config' => $moduleConfig,
        );
        $response = $this->client->sendStoreInfo($data);
        $this->debug('Response:',$response);
        return $response;
    }
}
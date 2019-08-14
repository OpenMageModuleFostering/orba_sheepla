<?php
/**
 *  Created by ORBA|we-commerce your business
 */
class Orba_Sheepla_Model_Payment {

    public function getActivePaymentMethods() {
        $payments = array();
        $config = Mage::helper('sheepla')->getConfigData('payment');
        //var_dump($config);
        //die('koniec');
        foreach ($config as $code => $method_config) {
            if (is_array($method_config)) {
                if (isset($method_config['active']) && $method_config['active']) {
                    if (isset($method_config['model'])) {
                        $method_model = Mage::getModel($method_config['model']);
                        if ($method_model) {
                            $payments[$code] = isset($method_config['title']) ? $method_config['title'] : $code;
                        }
                    }
                }
            } else {
                if ($method_config->active != '0') {
                    if (isset($method_config->model)) {
                        $method_model = Mage::getModel($method_config->model);
                        if ($method_model) {
                            $payments[$code] = isset($method_config->title) ? $method_config->title : $code;
                        }
                    }
                }
            }
        }
        $methods = array(array('value'=>'', 'label'=>Mage::helper('adminhtml')->__('--Please Select--')));
        foreach ($payments as $code => $title) {
            $methods[$code] = array(
                'label'   => $title,
                'value' => $code,
            );
        }
        
        return $methods; 
    } 
	
	public function toOptionArray(){
		return $this->getActivePaymentMethods();
	}
 
}
<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 */

class Orba_Sheepla_Block_Checkout_Onepage_Payment_Methods extends Mage_Checkout_Block_Onepage_Payment_Methods {

    /**
     * Check and prepare payment method model
     * @return bool
     */
    protected function _canUseMethod($method) {

        //shipping method

        $sm = $this->getQuote()->getShippingAddress()->getShippingMethod();
        $sTemp = explode('_', $sm);
        //is it sheepla's method
        if ($sTemp[0] == 'sheepla') {
            //if so get check if $method is available with current shipping method
            //Mage::log($method);
            $cString = "sheepla/{$sTemp[1]}_{$sTemp[2]}";
            $conf = Mage::getStoreConfig( $cString );

            //Mage::log($conf);

            if (strpos($conf['payment'], $method->getCode()) === false) {
                return false;
            }
        }


        if (!$method || !$method->canUseCheckout()) {
            return false;
        }
        return parent::_canUseMethod($method);
    }

    /**
     * Retrieve code of current payment method
     * @return mixed
     */
    public function getSelectedMethodCode() {
        if ($method = $this->getQuote()->getPayment()->getMethod()) {
            return $method;
        }
        return false;
    }

    /**
     * Payment method form html getter
     * @param Mage_Payment_Model_Method_Abstract $method
     */
    
    public function getPaymentMethodFormHtml(Mage_Payment_Model_Method_Abstract $method) {
        return $this->getChildHtml('payment.method.' . $method->getCode());
    }

}

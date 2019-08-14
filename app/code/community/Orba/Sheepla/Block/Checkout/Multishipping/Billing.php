<?php

class Orba_Sheepla_Block_Checkout_Multishipping_Billing extends Mage_Checkout_Block_Multishipping_Billing {

    protected function _canUseMethod($method) {
        if (!$method->canUseForMultishipping()) {
            return false;
        }
        $addresses = $this->getQuote()->getAddressesCollection();
        foreach ($addresses as $address) {
            $sTemp = explode('_', $address->getShippingMethod());
            //is it sheepla's method
            if ($sTemp[0] == 'sheepla') {
                //if so get check if $method is available with current shipping method
                $cString = "sheepla/{$sTemp[1]}_{$sTemp[2]}";
                $conf = Mage::getStoreConfig($cString);
                if (strpos($conf['payment'], $method->getCode()) === false) {
                    return false;
                }
            }
        }
        return parent::_canUseMethod($method);
    }

}
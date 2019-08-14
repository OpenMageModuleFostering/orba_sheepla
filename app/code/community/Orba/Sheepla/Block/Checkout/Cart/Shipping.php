<?php

class Orba_Sheepla_Block_Checkout_Cart_Shipping extends Mage_Checkout_Block_Cart_Shipping
{
    public function getCarrierName($carrierCode)
    {
        if ($carrierCode == 'sheepla') {
            $sheeplaConfig = Mage::getStoreConfig($carrierCode);
            return $sheeplaConfig['advanced']['carrier_label'];
        }
        if ($name = Mage::getStoreConfig('carriers/'.$carrierCode.'/title')) {
            return $name;
        }
        return $carrierCode;
    }
}

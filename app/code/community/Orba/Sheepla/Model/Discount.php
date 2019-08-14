<?php

class Orba_Sheepla_Model_Discount extends Mage_Core_Model_Abstract {
    
    protected $quote = null;
    protected $calculated = false;
    protected $type;
    protected $value;
    
    public function setQuote($quote) {
        $this->quote = $quote;
        $this->calculated = false;
    }
    
    protected function calculate() {
        if(!$this->quote) {
            throw new \Exception("No quote data, use setQuote previously");
        }
        
        if(!$this->calculated) {
            
            $totalItemsInCart = Mage::helper('checkout/cart')->getItemsCount(); //total items in cart
            $totals = $this->quote->getTotals();
            if(isset($totals['discount']) &&
               $totals['discount']->getValue()) {
                $this->type = 'fixed';
                $this->value = abs($totals['discount']->getValue());
            }
        }
    }
    
    public function getType() {
        $this->calculate();
        return $this->type;
    }
    
    public function getValue() {
        $this->calculate();
        return $this->value;
    }
}
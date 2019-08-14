<?php

class Orba_Sheepla_Helper_Debuger extends Mage_Core_Helper_Abstract {
    
    protected $data = array();
    
    public function log( $name ) {
        
        $this->data[ $name ] = microtime( true );
    }
    
    
    public function compare( $name1 , $name2 ) {
        $keyExists = true;
        
        $result = "Comparing $name1 and $name2\n\r";
        
        if( !isset( $this->data[ $name1 ] ) ) {
            
            $result .= "key $name1 not exists\n\r";
            $keyExists = false;
        }
        
        if( !isset( $this->data[ $name2 ] ) ) {
            
            $result .= "key $name2 not exists\n\r";
            $keyExists = false;
        }
        
        if( $keyExists ) {
            
            $result .= "$name1 = {$this->data[ $name1 ]}\n\r"
                    . "$name2 = {$this->data[ $name2 ]}\n\r"
                    . "difference = " . ( round( floatval( $this->data[ $name2 ] - $this->data[ $name1 ] ) , 4 ) ) . "\n\r";
        } else {
            
            $result .= '\n\r ';
        }
        
        return $result;
    }
}
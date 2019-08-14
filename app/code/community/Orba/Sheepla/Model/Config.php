<?php

class Orba_Sheepla_Model_Config extends Mage_Adminhtml_Model_Config {
    
    public function _initSectionsAndTabs() {
        parent::_initSectionsAndTabs();
        if(Mage::getVersion() < '1.7') {
            
            $config = Mage::getConfig()->loadModulesConfiguration('system.xml')
                                       ->applyExtends();

            Mage::dispatchEvent('adminhtml_init_system_config', array('config' => $config));
        }
    }
}
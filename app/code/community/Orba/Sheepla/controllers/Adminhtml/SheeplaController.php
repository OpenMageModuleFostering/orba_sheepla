<?php

class Orba_Sheepla_Adminhtml_SheeplaController extends Mage_Adminhtml_Controller_Action {
    
    protected $sheeplaProxyModel;
    protected $sheeplaDataModel;
    protected $sp;


    public function indexAction(){
            // "Fetch" display
    $this->loadLayout();

            $block = $this->getLayout()->createBlock(
            'Mage_Core_Block_Template',
            'my_block_name_here',
            array('template' => 'sheepla/shipments.phtml')
    );


//        $block->setChild(
//            'store_switcher',
//            $this->getLayout()->createBlock('adminhtml/store_switcher')
//                              ->setSwitchUrl($this->getUrl('*/*/*', array('_current'=>true, '_query'=>false, 'store'=>null)))
//                              ->setTemplate('store/switcher/enhanced.phtml')
//        );

    $this->getLayout()->getBlock('content')->append($block);

    // "Output" display
    $this->renderLayout();

    }
}
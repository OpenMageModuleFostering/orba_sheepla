<?php

/**
 * Created by ORBA|we-commerce your business -> http://orba.pl
 * 
 */
class Orba_Sheepla_Model_Template {

    //lazy loading container
    protected static $templates = null;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {

        if (is_null(Orba_Sheepla_Model_Template::$templates)) {

            $templates = array();
            $result = array();
            //get proxy model
            try {
                $proxy = Mage::getModel('sheepla/proxy')->getProxy();

                //and pass data model instance to it
                $templates = $proxy->getClient()->getShipmentTemplates();

                foreach ($templates as $t) {
                    $result[] = array('value' => $t['id'], 'label' => $t['name']);
                }
            } catch (Exception $e) {
                Mage::log('[Sheepla][Exception]' . $e->getMessage());
            }

            $result[] = array('value' => 0, 'label' => Mage::helper('sheepla/data')->__('None - Method not automated by Sheepla'));

            Orba_Sheepla_Model_Template::$templates = $result;
        }

        //return magento formated array of options
        return Orba_Sheepla_Model_Template::$templates;
    }

}

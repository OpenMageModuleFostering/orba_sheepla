<?php

class Orba_Sheepla_Model_ObserverAdmin {
    
    /**
     * Obiekt pomocniczy do edycji sekcji
     * @var SheeplaSectionConfigurator 
     */
    protected $sheeplaSectionConfigurator;
    
    /**
     * Zawier jedną z trzech wartości store,website,default i jest ustawiana w zależności od tego jaka konfiguracja jest edytowana
     * @var string 
     */
    protected $configMode;
    
    /**
     * Kod sklepu - storeCode
     * @var string 
     */
    protected $store;
    
    /**
     * Kod websitu - websiteCode
     * @var string
     */
    protected $website;
    
    /**
     * Zawiera listę metod dostaw, które w obecnie edytowanej konfiguracji jest metodą podstawową i niema konfiguracji nadrzędnych
     * @var array
     */
    protected $basicConfig = array( 'sheepla_method_0' );
    
    /** 
     * Informacja czy sekcja została przetworzona
     * @var bool
     */
    protected static $sectionInited;
    
    /**
     * Oznaczam pola konfiguracji tak aby nie można było ustawić im domyślnych wartości jeśli są bazowe w edytowanej konfiguracji
     * @param Varien_Event_Observer $observer
     */
    public function onAdminhtmlBlockHtmlBefore( $observer ) {
        
        $observerData = $observer->getData();
        
        if( isset( $observerData[ 'block' ] ) &&
            'sheepla' == $observerData[ 'block' ]->getSectionCode() ) {
            
            $block = $observerData[ 'block' ];
            
            // sprawdzam wszystkie fieldsety
            foreach( $block->getForm()->getElements() as $fieldset ) {
                
                if( in_array( $fieldset->getId() , $this->basicConfig ) ) {
                
                    foreach( $fieldset->getElements() as $element ) {

                        $element->canUseWebsiteValue = 0;
                        $element->canUseDefaultValue = 0;
                    }
                }
            }
        }
    }
    
    /**
     * Ustawiam wartości sort order zgodnie z kolejnością z jaką przychodzą w post
     * @param Varien_Event_Observer $observer
     */
    public function onAdminhtmlInitSystemConfig( $observer ) {
        
        $current = Mage::app()->getRequest()->getParam('section');
        $website = Mage::app()->getRequest()->getParam('website');
        $store   = Mage::app()->getRequest()->getParam('store');
        
        if(null == $current ||
           'sheepla' !== $current ||
           true == self::$sectionInited) {
            
            return;
        }
        
        /** dla magento v < 1.7 **/
        if(Mage::getVersion() < '1.7') {
            
            $cfg = Mage::getSingleton('adminhtml/config_data')
                ->setSection($current)
                ->setWebsite($website)
                ->setStore($store);

            $section = Mage::getConfig()->getNode( 'sections/sheepla' );

            $config = Mage::getConfig()->loadModulesConfiguration('system.xml')
                                       ->applyExtends();
            $section = $config->getNode( 'sections/sheepla' );
            
        } else {
            
            $observerData = $observer->getData();
            $config = $observerData[ 'config' ];
            $section = $config->getNode( 'sections/sheepla' );
        }
        
        self::$sectionInited = true;
        $this->prepareSection( $section );
    }
    
    
    /**
     * Zmieniam dene post
     */
    public function onControllerActionPredispatchAdminhtmlSystemConfigSave( $observer ) {
        
        $this->getMethodDataFromRequest();
    }
    
    
    /**
     * Edytuje sekcję i dodaję do niej pola dla poszczególnej konfiguracji
     * @param $section
     */
    protected function prepareSection( $section ) {
        
        $this->website = Mage::App()->getRequest()->getParam( 'website' );
        $this->store = Mage::App()->getRequest()->getParam( 'store' );
        
        if( $this->store ) {
            
            $this->configMode = 'store';
            
        } elseif( $this->website ) {
            
            $this->configMode = 'website';
            
        } else {
            
            $this->configMode = 'default';
        }	
        
        
        /** dla magento v < 1.7 **/
        if(Mage::getVersion() < '1.7') {
            $current = Mage::app()->getRequest()->getParam('section');
            $website = Mage::app()->getRequest()->getParam('website');
            $store   = Mage::app()->getRequest()->getParam('store');


            $configFields = Mage::getSingleton('adminhtml/config');

            $sections     = $configFields->getSections($current);
            $section      = $sections->$current;
        }
        
        
        // ustawiam SheeplaSectionConfigurator
        $this->sheeplaSectionConfigurator = new SheeplaSectionConfigurator( $section );
        $this->sheeplaSectionConfigurator->setMethodPattern( new Mage_Core_Model_Config_Element($section->groups->method_pattern->asXml()));
        $this->sheeplaSectionConfigurator->clearMethods();
        
        // tworzę xml-a dla formularza
        $sortOrder = 1;
        foreach( $this->getMethodFormData() as $methodId => $methodName ) {
            
            if( !strlen( $methodName ) ) {
                
		$methodName = Mage::helper( 'sheepla' )->__( 'Not named ( <b style="color:red">will be removed after save</b> )' );
            }
            
            $this->sheeplaSectionConfigurator->addMethodFromPattern( $methodId , $methodName , $sortOrder++ );
        }
        
        if( Mage::App()->getRequest()->isPost() &&
            Mage::App()->getRequest()->getPost( 'groups' , false ) &&
            'sheepla' == Mage::App()->getRequest()->getParam( 'section' , '' ) ) {
            
            $formData = Mage::App()->getRequest()->getPost( 'groups' );
            
            
            $storedData = $this->getMethodDataFromDatabase();
            
            // usunięcie elementuów, które są w bazie a niema ich w przesłanych danych - zostały usunięte
            $configDataModel = Mage::getModel( 'core/config_data' );
            
            $scopeId = (int)Mage::getConfig()->getNode('stores/' . $this->store . '/system/store/id');
            
            foreach( $storedData as $storedMethodId => $storedMethodName ) {
                
                // sprawdzam czy usówana metoda nie ma wpisu w konfiguracji nadrzędnej
                // jeśli ma - nie mogę jej usunąć i wyświetlam kmunikat
                if( 'store' == $this->configMode ) {

                    $parentName = $this->getParentMethodName( $storedMethodId , 'website' );

                } elseif( 'website' == $this->configMode ) {

                    $parentName = $this->getParentMethodName( $storedMethodId , 'default' );
                    
                } else {
                    
                    // będąc w demyślnej konfiguracj nie trzeba szukać rodzica...
                    $parentName = null;
                }
                
                if( !isset( $formData[ $storedMethodId ] ) ||
                    ( !isset( $formData[ $storedMethodId ][ 'fields' ][ 'name' ][ 'value' ] ) &&
                      !strlen( $parentName ) ) ) {
                    
                    
                    if( null !== $parentName &&
                        strlen( $parentName ) ) {
                        
                        Mage::getSingleton( 'core/session' )->addError( Mage::helper( 'sheepla' )->__( "Method '%s' cant be removed, remove it from parent configuration or mark as disabled" , $storedMethodName ) );
                        continue;
                    }
                    
                    try {
                        
                        $collection = $configDataModel->getCollection()
                                                      ->addFieldToFilter( 'path' , array( 'like' => "sheepla/$storedMethodId/%" ) );
                        
                        foreach( $collection as $configElement ) {
                            
                            $configElement->delete();
                        }
                        
                    } catch( Exception $e ) {
                        
                        Mage::log( __METHOD__ . ". Cant delete data for sheepla/$storedMethodId" , null , 'sheepla.log' );
                    }
                }
            }
        }
        
        $this->sheeplaSectionConfigurator->addMethodFromPattern( 'method_0' , Mage::helper( 'sheepla' )->__( 'New Shipping' ) , 1 );
        
    }
    
    /**
     * Pobiera dane dotyczące metod dostawy odpowiednie dla danej konfiguracji z bazy lub z requestu
     * @return array
     */
    protected function getMethodFormData() {
        
        if( Mage::App()->getRequest()->isPost() ) {
            
            return $this->getMethodDataFromRequest();
        }
        
        return $this->getMethodDataFromDatabase();
    }
    
    /**
     * Pobiera dane metod dostaw odpowiednie dla aktualnie edytowanej konfiguracji z bazy
     * @return array
     */
    protected function getMethodDataFromDatabase() {
        
        try {
            
            if( 'website' == $this->configMode ) {
                
                $sheeplaConfigData = Mage::App()->getConfig()->getNode( 'websites/' . $this->website . '/sheepla' );
                if( is_object( $sheeplaConfigData ) ) {
                    
                    $data = array();
                    foreach( $sheeplaConfigData->children() as $sheeplaConfigDataRowKey => $sheeplaCongihDataRowValue ) {
                        
                        $data[ $sheeplaConfigDataRowKey ] = $sheeplaCongihDataRowValue->asArray();
                    }
                    
                    $sheeplaConfigData = $data;
                }
                
            } elseif( 'store' == $this->configMode ) {
                
                $sheeplaConfigData = Mage::getStoreConfig( 'sheepla' , $this->store );
                
            } else {
                
                $sheeplaConfigData = Mage::getStoreConfig( 'sheepla' );
            }
            
        } catch( Exception $e ) {
            $sheeplaConfigData = array();
        }
        
        
        $methods = array();
        foreach( $sheeplaConfigData as $key => $value ) {
            
            if( !preg_match( '/^method_[0-9]+$/' , $key ) ||
                'method_0' == $key ) {
                
                continue;
            }
            
            
            $sortOrder = $value[ 'sort_order' ];
            $name = $value[ 'name' ];
            
            
            if( null == $name ) {
                
                $name = $this->getMethodNameFromConfig( $key );
                
                if( null == $name ) {
                    
                    $name = $this->getParentMethodName( $key );
                }
            }
            
            
            // sprawdzam czy metoda ma nadrzędną konfogurację - jeśli tak to uznaczam ją jako podstawową
            if( null == $this->getParentMethodName( $key ) ) {
                
                $this->basicConfig[] = 'sheepla_' . $key;
            }
            
            
            $data = array( 'order' => $sortOrder , 'value' => $name );

            $pushed = false;

            $tmpMethods = $methods;
            $methods = array();

            foreach( $tmpMethods as $methodId => $method ) {

                if( false === $pushed &&
                    $method[ 'order' ] > $data[ 'order' ] ) {

                    $pushed = true;
                    $methods[ $key ] = $data;
                }

                $methods[ $methodId ] = $method;
            }

            if( !$pushed ) {

                $methods[ $key ] = $data;
            }
        }
        
        $tmpMethods = $methods;
        $methods = array();
        
        
        foreach( $tmpMethods as $methodId => $method ) {
            
            $methods[ $methodId ] = $method[ 'value' ];
        }
        
        return $methods;
    }
    
    /**
     * Pobiera dane metod dostaw odpowiednie dla aktualnie edytowanej konfiguracji z requestu
     * @return array
     */
    protected function getMethodDataFromRequest() {
        
        if( false == Mage::App()->getRequest()->getPost( 'groups' , false ) ) {
            
            return array();
        }
        
        $methods = array();
        
        
        $postData = Mage::App()->getRequest()->getPost();
        
        if(isset($postData['groups']['method_pattern'])) {
            unset($postData['groups']['method_pattern']);
        }
        
        $methodOrderCounter = 0;
        
        foreach( $postData[ 'groups' ] as $key => $value ) {
            
            
            if( preg_match( '/^method_[0-9]+$/' , $key ) ) {
                
                // przypisanie kolejności zgodnej z kolejnością z, którą mam dane w poście
                $postData[ 'groups' ][ $key ][ 'fields' ][ 'sort_order' ] = array( 'value' => $methodOrderCounter++ );

                // jeśli zaznaczony jest "user default"
                $parentConfigName = $this->getParentMethodName( $key ); //  Mage::getStoreConfig( "sheepla/$key/name" , Mage::App()->getRequest()->getParam( 'store' ) );
                
                if( isset( $value[ 'fields' ][ 'name' ][ 'value' ] ) &&
                    strlen( $value[ 'fields' ][ 'name' ][ 'value' ] ) ) {
                    
                    $name = $value[ 'fields' ][ 'name' ][ 'value' ];
                    
                } elseif( isset( $value[ 'fields' ][ 'name' ][ 'inherit' ] ) ) {
                    
                    $name = '';
                    
                } elseif( null !== $parentConfigName ) {
                    
                    $name = $parentConfigName;
                    
                } else {
                    
                    unset( $postData[ 'groups' ][ $key ] );
                    unset( $postData[ 'config_state' ][ $key ] );
                    
                    continue;
                }
                
                
                preg_match( '/(method_[0-9]+)/' , $key , $methodId );
                $methodId = $methodId[ 1 ];
                
                
                $methods[ $key ] = $name;
            }
        }
        
        Mage::App()->getRequest()->setPost( $postData );
        
        return $methods;
    }
    
    /**
     * Zwraca nazwę dla podanej metody dostawy z konfiguracji nadrzędnej jeśli taka istnieje
     * @param string $methodId identyfikator metody dostawy np method_1
     * @param (string|null) $currentConfigLevel typ konfiguracji dla, której chcemy szukać rodzica
     * @return (string|null)
     */
    protected function getParentMethodName( $methodId , $currentConfigLevel = null ) {
        
        if( null == $currentConfigLevel ) {
            
            if( 'store' == $this->configMode ) {
                
                $currentConfigLevel = 'website';
            } elseif( 'website' == $this->configMode ) {
                
                $currentConfigLevel = 'default';
            }
        }
        
            
        if( 'website' == $currentConfigLevel ) {
            
             $name = Mage::getConfig()->getNode( 'websites/' . $this->website . '/sheepla/' . $methodId . '/name' );
             
             
             if( strlen( $name ) ) {
                 
                 return $name;
                 
             } else {
                 
                 return $this->getParentMethodName( $methodId , 'default' );
             }
             
        } elseif( 'default' == $currentConfigLevel )  {
            
            $name = Mage::getStoreConfig( "sheepla/$methodId/name" );
            
            if( strlen( $name ) ) {
                 
                return $name;
            }
            
            return null;
        } 
        
        return null;
    }
    
    /**
     * Pomocnicza metoda zwracająca nazwę metody dostawy z konfiguracji podanej w $currentConfigLevel
     * @param string $methodId identyfikator metody dostawy np method_1
     * @param (string|null) $currentConfigLevel typ konfiguracji dla, której chcemy szukać rodzica
     * @return (string|null)
     */
    protected function getMethodNameFromConfig( $methodId , $currentConfigLevel = null ) {
        
        if( null == $currentConfigLevel ) {
            
            $currentConfigLevel = $this->configMode;
        }
        
        
        if( 'store' == $currentConfigLevel ) {
            
            $name = Mage::getStoreConfig( "sheepla/$methodId/name" , $this->store );
            
            if( strlen( $name ) ) {
                
                return $name; 
            }
            
            return null;
            
        } elseif( 'website' == $currentConfigLevel ) {
            
             $name = Mage::getConfig()->getNode( 'websites/' . $this->website . '/sheepla/' . $methodId . '/name' );
             
             if( strlen( $name ) ) {
                 
                 return $name;
             } 
             
             return null;
             
        } elseif( 'default' == $currentConfigLevel )  {
            
            $name = Mage::getStoreConfig( "sheepla/$methodId/name" );
            
            if( strlen( $name ) ) {
                 
                return $name;
            }
            
            return null;
        } 
        
        return null;
    }
}

/**
 * Obiekt pomocniczy do edycji xml-a sekcji
 */
class SheeplaSectionConfigurator {
    
    protected $methodPattern;
    protected $section;
    
    public function __construct( Mage_Core_Model_Config_Element $section ) {
        
        $this->section = $section;
    }
    
    public function setMethodPattern( Mage_Core_Model_Config_Element $pattern ) {
        
        $this->methodPattern = $pattern;
    }
    
    public function clearMethods() {
        
        foreach( $this->section->groups->children() as $nodeName => $child ) { 
            
            if( preg_match( '/method/' , $nodeName ) ) {
                $methodPatternAsDom = dom_import_simplexml($child);
                $methodPatternAsDom->parentNode->removeChild($methodPatternAsDom);
            }
        }
    }
    
    public function addMethodFromPattern( $name , $label , $sortOrder ) {
        
        $methodElement = new Mage_Core_Model_Config_Element( "<$name></$name>" );
        
        foreach( $this->methodPattern->children() as $k => $child ) {
            
            $methodElement->appendChild( $child );
        }
        
        $methodElement->fields->sort_order->value = $sortOrder;
        $methodElement->sort_order = $sortOrder;
        $methodElement->label = trim( $label );
        
        $this->section->groups->appendChild( $methodElement );
    }
}
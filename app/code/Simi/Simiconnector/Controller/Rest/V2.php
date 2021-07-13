<?php

/**
 *
 * Copyright © 2016 Simicommerce. All rights reserved.
 */

namespace Simi\Simiconnector\Controller\Rest;

class V2 extends Action
{
    /**
     * Flush cache storage
     *
     */
    public function execute()
    {
        parent::execute();
        ob_start();
        try {
            $result = $this->_getServer()
                ->init($this)->run();
            $this->_printData($result);
        } catch (\Exception $e) {
            $results = [];
            $result = [];
            if (is_array($e->getMessage())) {
                $messages = $e->getMessage();
                foreach ($messages as $message) {
                    $result[] = [
                        'code' => $e->getCode(),
                        'message' => $message,
                    ];
                }
            } else {
                $result[] = [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ];
            }
            $results['errors'] = $result;
            $this->_printData($results);
        }
        ob_end_flush();
    }

    private function _getServer()
    {
        $serverModel = $this->simiObjectManager->get('Simi\Simiconnector\Model\Server');
        $serverModel->eventManager = $this->_eventManager;
        return $serverModel;
    }

    private function _noCache(){
        $data = $this->_getServer()->getData();
        switch ($data['resource']) {
            case 'orders':              
            case 'customers':                
            case 'addresses':
            // case 'storeviews':
            case 'quoteitems':
            case 'sociallogins':
                # code...
                break;
            default:
                # code...
                return false;
                break;
        }
        return true;
    }
    private function _printData($result)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');        
        try {
            $this->getResponse()->setHeader('Content-Type', 'application/json');
            $data = $this->_getServer()->getData();
            if((isset($data['resource']) && $this->_noCache()) 
                || (isset($_GET['email']) && $_GET['email'])
                || $customerSession->isLoggedIn()){    
                $this->getResponse()->setNoCacheHeaders();
            }else{           
                if(isset($data['resource']) && ($data['resource'] != 'products' && $data['resource'] != 'categories'
                    && $data['resource'] != 'categorytrees')){
                    header("X-Magento-Tags: cms_b");
                    if($data['resource'] == 'homes'){
                        header("X-Magento-Tags: cms_b,cms_b_homes");
                        if(isset($data['resourceid']) && $data['resourceid'] == 'lite'){
                            header("X-Magento-Tags: cms_b,cms_b_homelite");
                        }
                    }
                }                                
                $this->getResponse()->setPublicHeaders($objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue('system/full_page_cache/ttl'));
            }            
            $this->setData($result);
            $this->_eventManager
                ->dispatch('SimiconnectorRest', ['object' => $this, 'data' => $result]);
            $this->data = $this->getData();
            return $this->getResponse()->setBody(json_encode($this->data));
        } catch (\Exception $e) {
            return;
        }
    }

    private function getData()
    {
        return $this->data;
    }

    private function setData($data)
    {
        $this->data = $data;
    }
}

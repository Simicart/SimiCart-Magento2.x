<?php
/**
 *
 * Copyright © 2016 Simicommerce. All rights reserved.
 */
namespace Simi\Simiconnector\Controller\Rest;

class Action extends \Magento\Framework\App\Action\Action
{

    protected $_data;

    public function preDispatch()
    {
        $enable = (int)$this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue('simiconnector/general/enable');
        /*
         
        if (!$enable) {
            echo 'Connector was disabled!';
            @header("HTTP/1.0 503");
            exit();
        }
        
        if (!$this->isHeader()) {
            echo 'Connect error!';
            @header("HTTP/1.0 401 Unauthorized");
            exit();
        }
         * 
         */

    }

    protected function _getServer(){
        $serverModel =  $this->_objectManager->get('Simi\Simiconnector\Model\Server');
        $serverModel->eventManager = $this->_eventManager;
        return $serverModel;
    }

    protected function _printData($result){
        @header("Content-Type: application/json");
        $this->setData($result);
        $this->_eventManager->dispatch($this->getRequest()->getFullActionName(), array('object' => $this, 'data' => $result));
        $this->_data = $this->getData();
        return $this->getResponse()->setBody(json_encode($this->_data));
    }

    protected function isHeader() {
        if (!function_exists('getallheaders')) {
            function getallheaders() {
                $head = array();
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                        $head[$name] = $value;
                    } else if ($name == "CONTENT_TYPE") {
                        $head["Content-Type"] = $value;
                    } else if ($name == "CONTENT_LENGTH") {
                        $head["Content-Length"] = $value;
                    }
                }
                return $head;
            }
        }

        $head = getallheaders();
        // token is key
        $keySecret = md5 ($this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue('simiconnector/general/secret_key'));
        $token = "";
        foreach ($head as $k => $h) {
            if ($k == "Authorization" || $k == "TOKEN"
                || $k == "Token") {
                $token = $h;
            }
        }
        if (strcmp($token, 'Bearer '.$keySecret) == 0)
            return true;
        else
            return false;
    }

    public function getData() {
        return $this->_data;
    }

    public function setData($data) {
        $this->_data = $data;
    }
    
    public function execute()
    {
        $this->preDispatch();
    }
}
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
        parent::preDispatch();

    }

    protected function _getServer(){
        $serverModel =  $this->_objectManager->get('Simi\Simiconnector\Model\Server');
        $serverModel->eventManager = $this->_eventManager;
        return $serverModel;
    }

    protected function _printData($result){
        header("Content-Type: application/json");
        $this->setData($result);
        $this->_eventManager->dispatch($this->getRequest()->getFullActionName(), array('object' => $this, 'data' => $result));
        $this->_data = $this->getData();
        echo json_encode($this->_data);
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
        $keyModel = 1; //use Secret key for storeview here (from configuration)
        $token = "";
        foreach ($head as $k => $h) {
            if ($k == "Authorization" || $k == "TOKEN"
                || $k == "Token") {
                $token = $h;
            }
        }
        if (strcmp($token, 'Bearer '.$keyModel->getKeySecret()) == 0)
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
        
    }
}
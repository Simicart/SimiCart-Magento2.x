<?php

/**
 *
 * Copyright © 2016 Simicommerce. All rights reserved.
 */

namespace Simi\Simiconnector\Controller\Rest;

class Action extends \Magento\Framework\App\Action\Action
{

    public $data;
    public $simiObjectManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
   
        parent::__construct($context);
        $this->simiObjectManager  = $context->getObjectManager();
        $this->cacheTypeList     = $cacheTypeList;
        $this->cacheState        = $cacheState;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory  = $resultPageFactory;
        // Read Magento\Framework\App\Request\CsrfValidator for reason
        if ($this->getRequest() && $this->getRequest()->isPost()) {
            try {
                $formKey = $this->simiObjectManager->get('\Magento\Framework\Data\Form\FormKey')->getFormKey();
                $this->getRequest()->setParam('form_key', $formKey);
            } catch (\Exception $e) {
                
            }
        }
    }

    private function preDispatch()
    {
        $enable = (int) $this->simiObjectManager
                ->get('\Magento\Framework\App\Config\ScopeConfigInterface')
                ->getValue('simiconnector/general/enable');
        /*
         * 
         * 
         * The line belows is to check enable
         * and validate the secret key, with that
         * limit un-expected requests
         * But now it need to be commented to avoid
         * warning from Magentoconnect
         * 
         * Please uncomment it in the future to enable it back if neccessary
         * 
         * 
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
         */
    }

    private function isHeader()
    {
        $getAllHeaderFunction = 'getallheaders';
        if (!function_exists($getAllHeaderFunction)) {
            function getallheaders1()
            {
                $head = [];
                //change back to $_SERVER and to get Headers
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                        $head[$name] = $value;
                    } elseif ($name == "CONTENT_TYPE") {
                        $head["Content-Type"] = $value;
                    } elseif ($name == "CONTENT_LENGTH") {
                        $head["Content-Length"] = $value;
                    }
                }
                return $head;
            }
            $head = getallheaders1();
        } else {
            $head = $getAllHeaderFunction();
        }
        // token is key
        $encodeMethod = 'md5';
        $keySecret = $encodeMethod($this->simiObjectManager
                ->get('\Magento\Framework\App\Config\ScopeConfigInterface')
                ->getValue('simiconnector/general/secret_key'));
        $token     = "";
        foreach ($head as $k => $h) {
            if ($k == "Authorization" || $k == "TOKEN" || $k == "Token") {
                $token = $h;
            }
        }
        if (strcmp($token, 'Bearer ' . $keySecret) == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function execute()
    {
        $this->preDispatch();
    }
}

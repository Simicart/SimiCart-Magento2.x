<?php

/**
 *
 * Copyright © 2016 Simicommerce. All rights reserved.
 */

namespace Simi\Simiconnector\Controller\Rest;

class V2 extends Action
{

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    public $cacheTypeList;

    /**
     * @var \Magento\Framework\App\Cache\StateInterface
     */
    public $cacheState;

    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     */
    public $cacheFrontendPool;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public $resultPageFactory;

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
            $result  = [];
            if (is_array($e->getMessage())) {
                $messages = $e->getMessage();
                foreach ($messages as $message) {
                    $result[] = [
                        'code'    => $e->getCode(),
                        'message' => $message,
                    ];
                }
            } else {
                $result[] = [
                    'code'    => $e->getCode(),
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
        $serverModel               = $this->simiObjectManager->get('Simi\Simiconnector\Model\Server');
        $serverModel->eventManager = $this->_eventManager;
        return $serverModel;
    }

    private function _printData($result)
    {
        try {
            $this->getResponse()->setHeader('Content-Type', 'application/json');
            $this->setData($result);
            $this->_eventManager
                    ->dispatch('SimiconnectorRestV2', ['object' => $this, 'data' => $result]);
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

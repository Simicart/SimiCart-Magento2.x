<?php

namespace MobileApp\Connector\Controller\Config\Save\Store;

class View extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $params = $this->_getParams();
        if(isset($params['store_id'])){
            $store = $this->storeManager->getStore($params['store_id']);
            $this->storeManager->setCurrentStore($store->getCode());
            $this->_objectManager->create('Magento\Framework\Locale\Resolver')->emulate($params['store_id']);
            $outputData = ['status' => 'SUCCESS', 'message' => ['SUCCESS']];
        }else{
            $outputData = ['status' => 'FAIL', 'message' => ['FAIL']];
        }
        /** @param \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData($outputData);
    }
}

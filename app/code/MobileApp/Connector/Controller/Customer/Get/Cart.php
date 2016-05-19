<?php

namespace MobileApp\Connector\Controller\Customer\Get;

class Cart extends \MobileApp\Connector\Controller\Connector
{

    /**
     *
     * @return void
     */
    public function execute()
    {
        $dataItems = $this->_getCartItems();
        $outputData = ['data' => array_values($dataItems), 'status' => 'SUCCESS', 'message' => ['SUCCESS']];

        /** @param \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData($outputData);
    }


}

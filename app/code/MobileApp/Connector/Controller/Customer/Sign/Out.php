<?php

namespace MobileApp\Connector\Controller\Customer\Sign;

class Out extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $session = $this->_objectManager->get('Magento\Customer\Model\Session');
        try{
            $session->logout();
            $outputData = ['status' => 'SUCCESS', 'message' => ['SUCCESS']];
        }catch (\Exception $e){
            $outputData = ['status' => 'FAIL', 'message' => [$e->getMessage()]];
        }

        /** @param \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData($outputData);
    }
}

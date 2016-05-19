<?php

namespace MobileApp\Connector\Controller\Checkout\Update\Paypal;

class Payment extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $params = $this->_getParams();
        $status = $params['payment_status'];
        $orderId = $params['invoice_number'];

        $order = $this->_objectManager
            ->create('Magento\Sales\Model\Order')
            ->loadByIncrementId($orderId);
        if($status == 2){
            $order->cancel();
        }else{

        }
        $outputData = $this->getOutputData($params, $serviceClassName, $serviceMethodName);
    }
}

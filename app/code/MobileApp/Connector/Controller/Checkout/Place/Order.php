<?php

namespace MobileApp\Connector\Controller\Checkout\Place;

class Order extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Sales\Api\OrderRepositoryInterface';
        $this->_serviceMethodName = 'get';
        $this->_params = ['id' => $this->_placeOrder()];
        return parent::execute();
    }

    /*
     * Place order
     *
     * @return string
     */
    protected function _placeOrder(){
        $serviceClassName = 'Magento\Quote\Api\CartManagementInterface';
        $serviceMethodName = 'placeOrder';
        $data = $this->getRequest()->getParam('data');
        $params = $data?json_decode($data, true):[];

        $cart = $this->_objectManager->get('Magento\Checkout\Model\Cart');
        $quoteId = $cart->getQuote()->getId();
        $inputData = ['paymentMethod' => ['method' => $params['payment_method']], 'cartId' => $quoteId];

        return $this->getOutputData($inputData, $serviceClassName, $serviceMethodName);
    }

    /*
     * Format data
     *
     * @param $data array
     * @return array
     */
    protected function _formatData($data) {

        if(isset($data['message']))
            return ['status' => 'FAIL', 'message' => [$data['message']]];

        $result = [
            'invoice_number' => $data['increment_id'],
            'payment_method' => $data['payment']['additional_information'][0]
        ];

        return ['data' => $result, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}

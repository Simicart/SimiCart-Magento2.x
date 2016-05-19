<?php

namespace MobileApp\Connector\Controller\Checkout\Save\Payment;

class Method extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Quote\Api\CartTotalManagementInterface';
        $this->_serviceMethodName = 'collectTotals';

        $params = $this->_getParams();

        $cart = $this->_objectManager->get('Magento\Checkout\Model\Cart');
        $quoteId = $cart->getQuote()->getId();


        $this->_params = ['paymentMethod' => ['method' => $params['payment_method']], 'cartId' => $quoteId];
        return parent::execute();
    }

    /*
     * Format data
     *
     * @param $data array
     * @return array
     */
    protected function _formatData($data){
        $result['fee'] = [
            'tax_price' => $data['tax_amount'],
            'coupon_code' => '',
            'discount' => $data['discount_amount'],
            'grand_total' => $data['grand_total'],
            'sub_total' => $data['subtotal_incl_tax'],
            'v2' => [
                'subtotal_excl_tax' => $data['subtotal'],
                'subtotal_incl_tax' => $data['subtotal_incl_tax'],
                'grand_total_excl_tax' => $data['base_grand_total'],
                'grand_total_incl_tax' => $data['grand_total'],
                'tax' =>  $data['tax_amount'],
                'discount' => $data['discount_amount'],
            ]
        ];
        if(isset($data['message']))
            return ['status' => 'FAIL', 'message' => [$data['message']]];
        return ['data' => $result, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}

<?php

namespace MobileApp\Connector\Controller\Checkout\Set;

class Coupon extends \MobileApp\Connector\Controller\Checkout\Checkout
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Checkout\Api\PaymentInformationManagementInterface';
        $this->_serviceMethodName = 'getPaymentInformation';

        $cart = $this->_objectManager->get('Magento\Checkout\Model\Cart');
        $quoteId = $cart->getQuote()->getId();

        if($this->_setCoupon($quoteId)){
            $this->_params = ['cartId' => $quoteId];
            return parent::execute();
        }
        return;
    }

    /*
     * Place order
     *
     * @param $cartId string
     * @return string
     */
    protected function _setCoupon($cartId){
        $serviceClassName = 'Magento\Quote\Api\CouponManagementInterface';
        $params = $this->_getParams();
        $couponCode = $params['coupon_code'];
        $serviceMethodName = ($couponCode)?'set':'remove';

        $inputData = ['couponCode' => $params['coupon_code'], 'cartId' => $cartId];
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

        $result = [];
        foreach($data['payment_methods'] as $paymentMethod) {
            $result['payment_method_list'][] = [
                'title' => $paymentMethod['title'],
                'payment_method' => $paymentMethod['code'],
                'show_type' => $this->_getShowType($paymentMethod['code'])
            ];
        }

        $result['fee'] = [
            'discount' => $data['totals']['discount_amount'],
            'tax' => $data['totals']['tax_amount'],
            'sub_total' => $data['totals']['subtotal'],
            'grand_total' => $data['totals']['grand_total'],
        ];

        return ['data' => $result, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}

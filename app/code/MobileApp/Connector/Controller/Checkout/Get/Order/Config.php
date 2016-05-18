<?php

namespace MobileApp\Connector\Controller\Checkout\Get\Order;

class Config extends \MobileApp\Connector\Controller\Checkout\Checkout
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Checkout\Api\ShippingInformationManagementInterface';
        $this->_serviceMethodName = 'saveAddressInformation';

        $params = $this->_getParams();

        $cart = $this->_objectManager->get('Magento\Checkout\Model\Cart');
        $quoteId = $cart->getQuote()->getId();

        $billingAddress = $this->_convertAddress($params['billingAddress']);
        $shippingAddress = $this->_convertAddress($params['shippingAddress']);

        $shippingMethods = $this->_getShippingMethods($shippingAddress, $quoteId);
        $this->_shippingMethods = $shippingMethods;

        $addressInformation = [
            'billing_address' => $billingAddress,
            'shipping_address' => $shippingAddress,
            'shipping_carrier_code' => isset($shippingMethods[0])?$shippingMethods[0]['s_carrier_code']:'',
            'shipping_method_code' => isset($shippingMethods[0])?$shippingMethods[0]['s_method_code']:'',
        ];

        $this->_params = ['addressInformation' => $addressInformation, 'cartId' => $quoteId];
        return parent::execute();
    }

    /*
     * Format data
     *
     * @param $data array
     * @return array
     */
    protected function _formatData($data){
        $result['shipping_method_list'] = $this->_shippingMethods;

        $i = 0;
        foreach($data['payment_methods'] as $paymentMethod){
            $result['payment_method_list'][$i] = [
                'title' => $paymentMethod['title'],
                'payment_method' => $paymentMethod['code'],
                'show_type' => $this->_getShowType($paymentMethod['code'])
            ];
            unset($paymentMethod['title']);
            unset($paymentMethod['code']);
            foreach($paymentMethod as $code => $value){
                $result['payment_method_list'][$i][$code] = $value;
            }
            $i++;
        }

        $result['fee'] = [
            'tax_price' => $data['totals']['tax_amount'],
            'coupon_code' => '',
            'discount' => $data['totals']['discount_amount'],
            'grand_total' => $data['totals']['grand_total'],
            'sub_total' => $data['totals']['subtotal_incl_tax'],

        ];
        if(isset($data['message']))
            return ['status' => 'FAIL', 'message' => [$data['message']]];
        return ['data' => $result, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }

    /*
     * Convert address
     *
     * @param $address array
     * @param $countries array
     * @return array
     */
    protected function _convertAddress($address){
        $name = $this->_parseCustomerName($address['name']);
        return [
            'firstname' => $name['firstname'],
            'lastname' => $name['lastname'],
            'street'=> [$address['street']],
            'city'=> $address['city'],
            'region'=> $address['state_name'],
            'regionId'=> $address['state_id'],
            'regionCode'=> $address['state_code'],
            'postcode'=> $address['zip'],
            'countryId'=> $address['country_code'],
            'telephone'=> $address['phone'],
            'saveInAddressBook' => false,
            'customerAddressId' => isset($address['address'])?$address['address']:'',
            'company' => isset($address['company'])?$address['company']:''
        ];
    }

    /*
     * Get shipping method from shipping address
     *
     * @param $shippingAddress array
     * @return array
     */
    protected function _getShippingMethods($shippingAddress, $cartId){
        $serviceClassName = 'Magento\Quote\Api\ShippingMethodManagementInterface';
        $serviceMethodName = 'estimateByAddress';
        $address = [
            'country_id' => $shippingAddress['countryId'],
            'region_id' => $shippingAddress['regionId'],
            'region' => $shippingAddress['region'],
            'postcode' => $shippingAddress['postcode'],
        ];

        $params = ['address' => $address, 'cartId' => $cartId];
        $outputData = $this->getOutputData($params, $serviceClassName, $serviceMethodName);

        $shippingMethods = [];
        $i = 0;
        foreach($outputData as $shippingMethod){
            $shippingMethods[] = [
                's_method_id' => $shippingMethod['method_code'],
                's_method_code' => $shippingMethod['method_code'],
                's_method_title' => $shippingMethod['method_title'],
                's_method_fee' => $shippingMethod['price_excl_tax'],
                's_carrier_code' => $shippingMethod['carrier_code'],
                's_method_selected' => !$i?true:false,
            ];
            $i++;
        }
        return $shippingMethods;
    }
}

<?php

namespace MobileApp\Connector\Controller\Checkout\Save\Shipping;

class Method extends \MobileApp\Connector\Controller\Checkout\Get\Order\Config
{
    /*
     * Format data
     *
     * @param $data array
     * @return array
     */
    protected function _formatData($data){
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
            'v2' => [
                'subtotal_excl_tax' => $data['totals']['subtotal'],
				'subtotal_incl_tax' => $data['totals']['subtotal_incl_tax'],
				'grand_total_excl_tax' => $data['totals']['base_grand_total'],
				'grand_total_incl_tax' => $data['totals']['grand_total'],
				'tax' =>  $data['totals']['tax_amount'],
				'discount' => $data['totals']['discount_amount'],
            ]
        ];

        if(isset($data['message']))
            return ['status' => 'FAIL', 'message' => [$data['message']]];
        return ['data' => $result, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}

<?php

namespace MobileApp\Connector\Controller\Customer\Get\Order;

class Detail extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Sales\Api\OrderRepositoryInterface';
        $this->_serviceMethodName = 'get';

        $data = $this->getRequest()->getParam('data');
        $params = $data?json_decode($data, true):[];

        $this->_params = ['id' => $params['order_id']];
        return parent::execute();
    }

    /*
     * Format data
     *
     * @param $data array
     * @return array
     */
    protected function _formatData($data){
        $items = $this->_parseOrderItems($data['entity_id']);

        $serviceClassName = 'Magento\Directory\Api\CountryInformationAcquirerInterface';
        $serviceMethodName = 'getCountriesInfo';
        $output = $this->getOutputData([], $serviceClassName, $serviceMethodName);
        foreach($output as $item){
            $countries[$item['id']] = $item['full_name_english'];
        }

        $billingAddress = $this->_formatAddress($data['billing_address'], $countries, $data['customer_email']);

        //currency
        $currencyCode = $data['store_currency_code'];
        $currency = $this->_objectManager->get('Magento\Directory\Model\Currency')->load($currencyCode);
        $currencySymbol = $currency->getCurrencySymbol();

        //shipping address
        $serviceClassName = 'Magento\Sales\Api\OrderAddressRepositoryInterface';
        $serviceMethodName = 'getList';
        $criteria['filter_groups'] = [
            [
                'filters' => [
                    [
                        'field' => 'parent_id',
                        'value' => $data['entity_id'],
                    ],
                ]
            ],
            [
                'filters' => [
                    [
                        'field' => 'address_type',
                        'value' => 'shipping',
                    ],
                ]
            ]
        ];

        $output = $this->getOutputData(['searchCriteria' => $criteria], $serviceClassName, $serviceMethodName);
        $shippingAddress = $this->_formatAddress($output['items'][0], $countries, $data['customer_email']);

        $order = [
            'order_id' => $data['increment_id'],
            'order_date' => $data['created_at'],
            'order_code' => $data['protect_code'],
            'order_total' => $data['grand_total'],
            'order_subtotal' => $data['subtotal_incl_tax'],
            'order_gift_code' => '',
            'order_note' => '',
            'order_items' => array_values($items),
            'payment_method' => $data['payment']['additional_information'][0],
            'card_4digits' => $data['payment']['cc_last4']?:'',
            'shipping_method' => $data['shipping_description'],
            'shippingAddress' => $shippingAddress,
            'billingAddress' => $billingAddress,
            'total_v2' => [
                'subtotal_excl_tax' => $data['subtotal'],
                'subtotal_incl_tax' => $data['subtotal_incl_tax'],
                'shipping_hand_excl_tax' => $data['shipping_amount'],
                'shipping_hand_incl_tax' => $data['shipping_incl_tax'],
                'discount' => $data['discount_amount'],
                'grand_total_excl_tax' => $data['base_grand_total'],
                'grand_total_incl_tax' => $data['grand_total'],
                'currency_symbol' => $currencySymbol
            ],
        ];

        return ['data' => $order, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }

    /*
     * Format address
     *
     * @param $address array
     * @param $country array
     * @param $email string
     * @return array
     */
    protected function _formatAddress($address, $countries, $email){
        return [
            'name' => $address['firstname']. ' ' .$address['lastname'],
            'street' => implode(', ', $address['street']),
            'city' => $address['city'],
            'state_name' => isset($address['region'])?$address['region']:'',
            'state_code' => isset($address['region_code'])?$address['region_code']:'',
            'zip' => $address['postcode'],
            'country_name' => $countries[$address['country_id']],
            'country_code' => $address['country_id'],
            'phone' => $address['telephone'],
            'email' => $email
        ];
    }

    /*
     * Get order items
     *
     * @param $orderId string
     * @return array
     */
    protected function _getOrderItems($orderId){
        $serviceClassName = 'Magento\Sales\Api\OrderItemRepositoryInterface';
        $serviceMethodName = 'getList';
        $searchCriteria['filterGroups'][]['filters'][] = [
            'field' => 'order_id',
            'value' => $orderId
        ];
        $params = ['searchCriteria' => $searchCriteria];
        $outputData = $this->getOutputData($params, $serviceClassName, $serviceMethodName);
        return $outputData['items'];
    }

    /*
     * Parse items
     *
     * @param $items array
     * @return array
     */
    protected function _parseOrderItems ($orderId){
        $items = $this->_getOrderItems($orderId);

        $skus = [];
        //format items
        foreach($items as $item){
            if(!isset($item['parent_item'])){
                $skus[] = $item['sku'];
                $itemsData[$item['sku']] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'product_price' => $item['price_incl_tax'],
                    'product_qty' => $item['qty_ordered'],
                    'product_sku' => $item['sku'],
                    'product_type' => $item['product_type'],
                ];

                $productImages = $this->_getProductImages($skus);
                foreach($productImages as $sku => $productImage){
                    $itemsData[$sku]['product_image'] = $productImage;
                }

                $itemsData[$item['sku']]['options'] = [];

                if($item['product_type'] == 'configurable'){
                    $itemsData[$item['sku']]['options'] = $item['product_option']['extension_attributes']['configurable_item_options'];
                }

                if(isset($item['product_option']['extension_attributes']['custom_options'])){
                    foreach($item['product_option']['extension_attributes']['custom_options'] as $customOption){
                        $itemsData[$item['sku']]['custom_options'][$customOption['option_id']] = $customOption['option_value'];
                    }
                }

            }else{
                if($itemsData[$item['parent_item']['sku']]['product_type'] == 'bundle'){
                    $sku = $itemsData[$item['parent_item']['sku']]['product_sku'];
                    $sku = str_replace('-'.$item['sku'], '', $sku);
                    $itemsData[$item['parent_item']['sku']]['product_sku'] = $sku;

                    $itemsData[$item['parent_item']['sku']]['options'][$item['sku']] = [
                        'option_value' => $item['qty_ordered'].' x '.$item['name'],
                        'option_price' => $item['price_incl_tax']
                    ];
                }
            }
        }

        foreach($itemsData as &$item){
            $options = [];
            //configurable
            if($item['product_type'] == 'configurable'){
                $optionsData = [];
                foreach($item['options'] as $option){
                    $optionsData[$option['option_id']] = $option['option_value'];
                }
                $attributes = $this->_getAttributes(array_keys($optionsData));

                foreach($attributes as $attribute){
                    foreach($attribute['options'] as $attributeOption){
                        if(in_array($attributeOption['value'], $optionsData)){
                            break;
                        }
                    }
                    $options[] = [
                        'option_title' => $attribute['default_frontend_label'],
                        'option_value' => $attributeOption['label'],
                        'option_price' => 0,
                    ];
                }
                $item['options'] = $options;

            }
            //bundle
            elseif($item['product_type'] == 'bundle'){
                $product = $this->_getProduct($item['product_sku']);
                foreach($product['extension_attributes']['bundle_product_options'] as $bundleOption){
                    foreach($bundleOption['product_links'] as $bundleItem){
                        if(in_array($bundleItem['sku'], array_keys($item['options']))){
                            $item['options'][$bundleItem['sku']]['option_title'] = $bundleOption['title'];
                            break;
                        }
                    }
                }
                $item['options'] = array_values($item['options']);//remove sku key
            }

            //custom option
            if(isset($item['custom_options'])){
                $product = $this->_getProduct($item['product_id'], 'id');
                foreach($product['options'] as $customOption){
                    if(in_array($customOption['option_id'], array_keys($item['custom_options']))){
                        foreach($customOption['values'] as $optionValue){
                            if($optionValue['option_type_id'] == $item['custom_options'][$customOption['option_id']]){
                                $item['options'][] = [
                                    'option_title' => $customOption['title'],
                                    'option_value' => $optionValue['title'],
                                    'option_price' => 0
                                ];
                                unset($item['custom_options']);
                                break;
                            }
                        }

                    }
                }
            }
        }
        return $itemsData;
    }

    /*
     * Get product
     *
     * @param $sku string
     * @param $type string
     * @return array
     */
    protected function _getProduct($sku, $type = 'sku'){
        $serviceClassName = 'Magento\Catalog\Api\ProductRepositoryInterface';
        $serviceMethodName = 'get';

        if($type == 'id')
            $sku = $this->_objectManager->get('Magento\Catalog\Model\Product')->load($sku)->getSku();
        $params = ['sku' => $sku];
        $product = $this->getOutputData($params, $serviceClassName, $serviceMethodName);
        return $product;
    }
}

<?php

namespace MobileApp\Connector\Controller\Checkout\Add\To;

class Cart extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Quote\Api\CartItemRepositoryInterface';
        $this->_serviceMethodName = 'save';
        $data = $this->getRequest()->getParam('data');
        $params = $data?json_decode($data, true):[];

        $cart = $this->_objectManager->get('Magento\Checkout\Model\Cart');
        $quoteId = $cart->getQuote()->getId();

        $bundleOptions = [];
        foreach($params['options'] as $option){
            $bundleOptions[] = [
                'optionId' => $option['option_id'],
                'optionQty' => isset($option['option_qty'])?$option['option_qty']:'',
                'optionSelections' => [
                    isset($option['option_type_id'])?$option['option_type_id']:''
                ]
            ];
        }

        $configurableOptions = [];
        foreach($params['options'] as $option){
            $configurableOptions[] = [
                'optionId' => $option['option_id'],
                'optionValue' => isset($option['option_value'])?$option['option_value']:''
            ];
        }

        $customOptions = $configurableOptions;

        $inputData['cartItem'] = [
            'sku' => $params['product_id'],
            'qty' => isset($params['product_qty'])?$params['product_qty']:1,
            'quoteId' => $quoteId,
            'productOption' => [
                'extensionAttributes' => [
                    'customOptions' => $customOptions,
                    'bundleOptions' => $bundleOptions,
                    'configurableItemOptions' => $configurableOptions
                ]
            ]
        ];
        $this->_params = $inputData;

        return parent::execute();
    }

    /*
     * Format data
     *
     * @param $data array
     * @return array
     */
    protected function _formatData($data){
        if(isset($data['message']))
            return ['status' => 'FAIL', 'message' => [$data['message']]];

        $dataItems = $this->_getCartItems();
        $outputData = ['data' => array_values($dataItems), 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
        return $outputData;
    }
}

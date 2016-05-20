<?php

namespace MobileApp\Connector\Controller\Checkout\Edit;

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
        $params = $this->_getParams();

        $cart = $this->_objectManager->get('Magento\Checkout\Model\Cart');
        $quoteId = $cart->getQuote()->getId();

        $items = [];
        $productSkus = [];
        foreach($params['cart_items'] as $itemData){
            $inputData['cartItem'] = [
                'itemId' => $itemData['cart_item_id'],
                'qty' => $itemData['product_qty'],
                'quoteId' => $quoteId
            ];

            $outputData = $this->getOutputData($inputData, $this->_serviceClassName, $this->_serviceMethodName);

            $productSkus[] = $outputData['sku'];
            $items[$outputData['sku']] = [
                'cart_item_id' => $outputData['item_id'],
                'product_id' => $outputData['sku'],
                'stock_status' => true,
                'product_name' => $outputData['name'],
                'product_price' => $outputData['price'],
                'product_qty' => $outputData['qty'],
                'options' => [],
            ];
        }

        $productImages = $this->_getProductImages($productSkus);
        foreach($productImages as $sku => $image){
            $items[$sku]['product_image'] = $image;
        }

        $outputData = ['data' => $items, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];

        /** @param \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData($outputData);
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
        return ['data' => ['user_id' => $data['id']], 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}

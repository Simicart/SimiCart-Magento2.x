<?php

namespace MobileApp\Connector\Controller\Catalog\Get\Product;

class Detail extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Catalog\Api\ProductRepositoryInterface';
        $this->_serviceMethodName = 'get';

        $data = $this->getRequest()->getParam('data');
        $params = $data?json_decode($data, true):[];

        $sku = isset($params['product_id'])?$params['product_id']:null;
        $this->_params = ['sku' => $sku];

        return parent::execute();
    }

    /*
     * Format data
     *
     * @var $data array
     * @return array
     */
    protected function _formatData($data){
        return $data;
        $ratingOfNumbers = $this->_getRatingOfNumbers($data['id']);
        $product = [
            'product_id' => $data['sku'],
            'product_name' => $data['name'],
            'product_type' => $data['type_id'],
            'product_regular_price' => isset($data['price'])?$data['price']:'',
            'product_price' => isset($data['price'])?$data['price']:'',
            '5_star_number' => $ratingOfNumbers[4],
            '4_star_number' => $ratingOfNumbers[3],
            '3_star_number' => $ratingOfNumbers[2],
            '2_star_number' => $ratingOfNumbers[1],
            '1_star_number' => $ratingOfNumbers[0],
        ];

        $product = array_merge($product, $this->_getProductReview($data['id']));

        $attributeCodes = [];
        foreach($data['custom_attributes'] as $attribute){
            $attributeCodes[$attribute['attribute_code']] = $attribute['value'];
            if($attribute['attribute_code'] == 'description')
                $product['product_description'] = $attribute['value'];
            if($attribute['attribute_code'] == 'short_description')
                $product['product_short_description'] = $attribute['value'];
            if($attribute['attribute_code'] == 'special_price')
                $product['product_price'] = $attribute['value'];
        }

        if($data['type_id'] == 'configurable'){
            $options = $this->_getConfigurableOptions($data['extension_attributes']['configurable_product_options']);
            $product['options'] = $options['options'];
            $product['children'] = $this->_getConfigurableChildrenProducts($data['sku'], $options['attribute_codes']);
        }elseif($data['type_id'] == 'bundle') {
            $product['options'] = $this->_getBundleOptions($data['extension_attributes']['bundle_product_options']);
        }elseif($data['type_id'] == 'grouped'){
            $product['children'] = $this->_getGroupedChildrenProducts($data['product_links']);
        }else {
            $product['options'] = $this->_getCustomOptions($data['sku'], $data['price']);
        }


        $product['product_attributes'] = $this->_getAttributeData($attributeCodes);

        $stock = $data['extension_attributes']['stock_item'];
        $product['max_qty'] = $stock['max_sale_qty'] > $stock['qty']?$stock['qty']:$stock['max_sale_qty'];
        $product['stock_status'] = $stock['is_in_stock'];

        foreach($data['media_gallery_entries'] as $media){
            if($media['media_type'] = 'image')
                $product['product_images'][] = $this->_getImageUrl($media['file']);
        }

        return ['data' => $product, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }


    /*
     * Get product attributes
     *
     * @var $attributesCodes array
     * @return array
     */
    protected function _getAttributeData($attributeCodes){
        $serviceClassName = 'Magento\Catalog\Api\ProductAttributeRepositoryInterface';
        $serviceMethodName = 'getList';

        /*foreach($attributeCodes as $code => $value){
            $filters[] = [
                'field' => 'attribute_code',
                'value' => $code,
            ];
        }*/

        $searchCriteria = [];
        $searchCriteria['pageSize'] = 100;
        $searchCriteria['filterGroups'][] = [
            'filters' => [
                [
                    'field' => 'is_visible_on_front',
                    'value' => 1,
                ],
            ],
        ];

        $params = ['searchCriteria' => $searchCriteria];
        $output = $this->getOutputData($params, $serviceClassName, $serviceMethodName);

        $productAttributes = [];
        $codes = array_keys($attributeCodes);
        foreach($output['items'] as $item){
            if(in_array($item['attribute_code'], $codes)){
                if(in_array($item['frontend_input'], ['boolean', 'select', 'multiselect'])){
                    $selectedOptionIds = explode(',', $attributeCodes[$item['attribute_code']]);
                    $selectedOptionLabels = [];
                    foreach($item['options'] as $option){
                        if(in_array($option['value'], $selectedOptionIds)){
                            $selectedOptionLabels[] = $option['label'];
                        }
                    }

                    $productAttributes[] = [
                        'title' => $item['default_frontend_label'],
                        'value' => implode(', ', $selectedOptionLabels)
                    ];
                }else {
                    $productAttributes[] = [
                        'title' => $item['default_frontend_label'],
                        'value' => $attributeCodes[$item['attribute_code']],
                    ];
                }
            }
        }

        return $productAttributes;
    }

    /*
     * Get options
     *
     * @param $sku string
     * @param $price float
     * @param $productType string
     * @return array
     */
    protected function _getCustomOptions($sku, $price){
        $serviceClassName = 'Magento\Catalog\Api\ProductCustomOptionRepositoryInterface';
        $serviceMethodName = 'getList';
        $params = ['sku' => $sku];
        $outputData = $this->getOutputData($params, $serviceClassName, $serviceMethodName);
        $options = [];
        foreach($outputData as $option){
            foreach($option['values'] as $value){
                $options[] = [
                    'option_id' => $option['option_id'],
                    'option_value' => $value['title'],
                    'option_price' => ($value['price_type']=='percent')?$value['price']*$price/100:$value['price'],
                    'option_title' => $option['title'],
                    'position' => $option['sort_order']*10+$value['sort_order'],
                    'option_type_id' => $value['option_type_id'],
                    'option_type' => $option['type']=='drop_down'?'select':$option['type'],
                    'is_required' => $option['is_require'],
                    'dependence_option_ids' => [],
                ];
            }
        }
        return $options;
    }

    /*
     * Get children products of configuration product
     *
     * @param $sku string
     * @param $attributeCodes array
     * @return array
     */
    protected function _getConfigurableChildrenProducts($sku, $attributeCodes){
        $serviceClassName = 'Magento\ConfigurableProduct\Api\LinkManagementInterface';
        $serviceMethodName = 'getChildren';
        $params = ['sku' => $sku];
        $output = $this->getOutputData($params, $serviceClassName, $serviceMethodName);

        $products = [];
        foreach($output as $product){
            if($product['status'] == 1){
                $attributes = [];
                foreach($product['custom_attributes'] as $attribute){
                    if(in_array($attribute['attribute_code'], $attributeCodes)){
                        $attributes[] = $attribute['value'];
                    }
                }

                $serviceClassName = 'Magento\CatalogInventory\Api\StockRegistryInterface';
                $serviceMethodName = 'getStockItemBySku';
                $params = ['productSku' => $product['sku']];
                $stock = $this->getOutputData($params, $serviceClassName, $serviceMethodName);

                $products[] = [
                    'price' => $product['price'],
                    'qty' => $stock['qty'],
                    'is_stock' => $stock['is_in_stock'],
                    'attributes' => $attributes
                ];
            }
        }
        return $products;
    }

    /*
     * Get children products of grouped product
     *
     * @param $items array
     * @return array
     */
    protected function _getGroupedChildrenProducts($items){
        $skus = [];
        foreach($items as $item){
            if($item['link_type'] == 'associated'){
                $skus = $item['linked_product_sku'];
            }
        }
        $products =  $this->_getProducts($skus);

        $serviceClassName = 'Magento\CatalogInventory\Api\StockRegistryInterface';
        $serviceMethodName = 'getStockItemBySku';

        $items = [];
        foreach($products as $item){
            $params = ['productSku' => $item['sku']];
            $stock = $this->getOutputData($params, $serviceClassName, $serviceMethodName);
            $items[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'sku' =>  $item['sku'],
                'price' => $item['price'],
                'qty' => $stock['qty'],
                'is_stock' => $stock['is_in_stock'],
            ];
        }
        return $items;
    }

    /*
     * Get configurable options
     *
     * @param $configurableOptions array
     * @return array
     */
    protected function _getConfigurableOptions($configurableOptions){
        $attributeIds = [];
        foreach($configurableOptions as $configurableOption){
            $attributeIds[] = $configurableOption['attribute_id'];
        }

        $attributes = $this->_getAttributes($attributeIds);

        $attributesData = [];
        $attributeCodes = [];
        foreach($attributes as $attribute){
            $attributeCodes[] = $attribute['attribute_code'];
            $optionValues = [];
            foreach($attribute['options'] as $option){
                $optionValues[$option['value']] = $option['label'];
            }
            $attributesData[$attribute['attribute_id']] = [
                'default_frontend_label'    => $attribute['default_frontend_label'],
                'frontend_input'            => $attribute['frontend_input'],
                'options'                   => $optionValues
            ];
        }

        $options = [];
        foreach($configurableOptions as $configurableOption){
            foreach($configurableOption['values'] as $index => $optionValue){
                $attributeId = $configurableOption['attribute_id'];
                $options[] = [
                    'option_id' => $attributeId,
                    'option_title' => $attributesData[$attributeId]['default_frontend_label'],
                    'option_value' => $attributesData[$attributeId]['options'][$optionValue['value_index']],
                    'option_price' => 0,
                    'option_type' => $attributesData[$attributeId]['frontend_input'],
                    'position' => $configurableOption['position']*10+$index,
                    'is_required' => true,
                    'option_type_id' => $optionValue['value_index']
                ];
            }
        }

        return ['options' => $options, 'attribute_codes' => $attributeCodes];

    }

    /*
     * Get bundle options
     *
     * @param $bundleOptions array
     * @return array
     */
    protected function _getBundleOptions($bundleOptions){
        $skus = [];
        foreach($bundleOptions as $bundleOption){
            foreach($bundleOption['product_links'] as $product){
                $skus[] = $product['sku'];
            }
        }

        $products =  $this->_getProducts($skus);

        $serviceClassName = 'Magento\CatalogInventory\Api\StockRegistryInterface';
        $serviceMethodName = 'getStockItemBySku';

        $items = [];
        foreach($products as $item){
            $params = ['productSku' => $item['sku']];
            $stock = $this->getOutputData($params, $serviceClassName, $serviceMethodName);
            $items[$item['sku']] = [
                'name' => $item['name'],
                'price' => $item['price'],
                'qty' => $stock['qty'],
                'is_stock' => $stock['is_in_stock'],
            ];
        }

        $options = [];
        foreach($bundleOptions as $bundleOption){
            foreach($bundleOption['product_links'] as $product){
                if($items[$product['sku']]['qty'] > 0 && $items[$product['sku']]['is_stock']){
                    $options[] = [
                        'option_id' => $product['id'],
                        'option_value' => $items[$product['sku']]['name'],
                        'option_price' => $items[$product['sku']]['price'],
                        'option_title' => $bundleOption['title'],
                        'position' => $bundleOption['position']*10+$product['position'],
                        'option_type_id' => $bundleOption['option_id'],
                        'option_type' => $bundleOption['type'],
                        'is_required' => $bundleOption['required'],
                        'dependence_option_ids' => [],
                    ];
                }
            }
        }
        return $options;
    }


    /*
     * Get rating of numbers
     *
     * @param $productId string
     * @return array
     */
    protected function _getRatingOfNumbers($productId){
        $reviews = $this->_objectManager->create('\Magento\Review\Model\Review')
            ->getResourceCollection()
            ->addStoreFilter($this->storeManager->getStore()->getId())
            ->addEntityFilter('product', $productId)
            ->setDateOrder()
            ->addRateVotes();
        /**
         * Getting numbers ratings/reviews
         */
        $star = array();
        $star[0] = 0;
        $star[1] = 0;
        $star[2] = 0;
        $star[3] = 0;
        $star[4] = 0;
        $star[5] = 0;
        if (count($reviews) > 0) {
            foreach ($reviews->getItems() as $review) {
                $star[5]++;
                $y = 0;
                foreach ($review->getRatingVotes() as $vote) {
                    $y += ($vote->getPercent() / 20);
                }
                $x = (int) ($y / count($review->getRatingVotes()));
                $z = $y % 3;
                $x = $z < 5 ? $x : $x + 1;
                if ($x == 1) {
                    $star[0]++;
                } elseif ($x == 2) {
                    $star[1]++;
                } elseif ($x == 3) {
                    $star[2]++;
                } elseif ($x == 4) {
                    $star[3]++;
                } elseif ($x == 5) {
                    $star[4]++;
                } elseif ($x == 0) {
                    $star[5]--;
                }
            }
        }
        return $star;
    }

}

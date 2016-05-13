<?php

namespace MobileApp\Connector\Controller\Catalog;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Webapi\ErrorProcessor;
use Magento\Framework\Webapi\Request;
use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magento\Framework\Webapi\Rest\Response as RestResponse;
use Magento\Framework\Webapi\Rest\Response\FieldsFilter;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Webapi\Controller\Rest\ParamsOverrider;
use Magento\Webapi\Controller\Rest\Router;
use Magento\Webapi\Controller\Rest\Router\Route;
use Magento\Webapi\Model\Rest\Swagger\Generator;

class Products extends \MobileApp\Connector\Controller\Connector
{
    const XML_PATH_RANGE_STEP = 'catalog/layered_navigation/price_range_step';
    const MIN_RANGE_POWER = 10;
    /**
     *
     * @return json
     */
    public function execute()
    {
        $data = $this->getRequest()->getParam('data');
        $params = $data?json_decode($data, true):[];

        $collection = $this->_objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
        $collection->addAttributeToSelect('*')
                ->addStoreFilter()
                ->addAttributeToFilter('status', 1)
                ->setVisibility([3, 4])
                ->addFinalPrice();
        //echo $collection->getSelect();die();

        $collection = $this->_paging($collection, $params);
        $collection = $this->_sort($collection, $params);
        $collection = $this->_filter($collection, $params);
        //$collection->load();

        $outputData = $this->_getData($collection, $params);

        /** @param \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData($outputData);
    }


    /*
     * Paging
     *
     * @param $collection \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @param $params array
     * @return array
     */
    protected function _paging($collection, $params){
        $pageSize = isset($params['limit'])?$params['limit']:self::DEFAULT_PAGE_SIZE;
        $offset = round(isset($params['offset'])?$params['offset']:0/$pageSize)+1;
        $collection->setPage($offset, $pageSize);
        return $collection;
    }

    /*
     *  Sort field
     *
     * @param $collection \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @param $params array
     * @return array
     */
    protected function _sort($collection, $params){
        $option = isset($params['sort_option'])?$params['sort_option']:0;
        switch($option){
            case 3:
                $field = 'name';
                $direction = 'ASC';
                break;
            case 4:
                $field = 'name';
                $direction = 'DESC';
                break;
            case 1:
                $field = 'price';
                $direction = 'ASC';
                break;
            case 2:
                $field = 'price';
                $direction = 'DESC';
                break;
            default:
                $field = 'id';
                $direction = 'DESC';
                break;
        }

        $collection->addAttributeToSort($field, $direction);
        return $collection;
    }


    /*
     * Parse filter
     *
     * @param $collection \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @var $params array
     * @return array
     */

    protected function _filter($collection, $params)
    {
        if (isset($params['filter']))
            foreach ($params['filter'] as $key => $value) {
                if ($key == 'price') {
                    $value = explode('-', $value);
                    $select = $collection->getSelect();
                    if ($value[0] > 0) {
                        $select->where('price_index.final_price >= ' . $value[0]);
                        //$select->where('price_index.min_price >= ' . $value[0]);
                    }

                    if ($value[1] > 0) {
                        $select->where('price_index.final_price < ' . $value[1]);
                        //$select->where('price_index.min_price < ' . $value[1]);
                    }
                } else {
                    $collection->addAttributeToFilter($key, ['finset' => $value]);
                }
            }

        //category
        $categoryId = isset($params['category_id'])?$params['category_id']:
            $this->storeManager->getStore()->getRootCategoryId();
        $category = $this->_objectManager
            ->create('Magento\Catalog\Model\Category')
            ->load($categoryId);

        $this->category = $category;
        $collection->addCategoryFilter($category);

        //related products
        if(isset($params['product_id'])){
            $product = $this->_objectManager
                ->create('Magento\Catalog\Model\Product')->load($params['product_id']);
            $allIds = $product->getRelatedProducts()->getAllIds();
            $collection->addFieldToFilter('entity_id', ['in' => $allIds]);
        }

        //search
        if(isset($params['key_word'])){
            $searchCollection = $this->_objectManager
                ->create('Magento\CatalogSearch\Model\ResourceModel\Fulltext\SearchCollection');
            $searchCollection->addSearchFilter($params['key_word']);
            foreach($searchCollection as $item){
                $ids[] = $item->getId();
            }
            $collection->addFieldToFilter('entity_id', ['in' => $ids]);
        }

        return $collection;
    }


    protected function _getLayerNavigator($collection, $params){
        $attributeCollection = $this->_objectManager
            ->create('Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection');
        $attributeCollection->addIsFilterableFilter()
                ->addVisibleFilter()
                ->addFieldToFilter('is_visible_on_front', 1);

        $allProductIds = $collection->getAllIds();
        $layerFilters = [];
        foreach($attributeCollection as $attribute){
            $attributeOptions = [];
            $attributeValues = $collection->getAllAttributeValues($attribute->getAttributeCode());
            foreach($attributeValues as $productId => $optionIds){
                if(in_array($productId, $allProductIds)){
                    $optionIds = explode(',', $optionIds[0]);
                    foreach($optionIds as $optionId){
                        if(isset($attributeOptions[$optionId]))
                            $attributeOptions[$optionId]++;
                        else
                            $attributeOptions[$optionId] = 0;
                    }
                }
            }

            $options = $attribute->getSource()->getAllOptions();
            $filters = [];
            foreach($options as $option){
                if($option['value'] && isset($attributeOptions[$option['value']]) && $attributeOptions[$option['value']]){
                    $option['count'] = $attributeOptions[$option['value']];
                    $filters[] = $option;
                }
            }

            if(count($filters)){
                $layerFilters[] = [
                    'attribute' => $attribute->getAttributeCode(),
                    'title' => $attribute->getDefaultFrontendLabel(),
                    'filter' => $filters,
                ];
            }
        }


        $priceRanges = $this->_getPriceRanges($collection);
        $filters = [];
        $totalCount = 0;
        $maxIndex = max(array_keys($priceRanges['counts']));
        foreach($priceRanges['counts'] as $index => $count){
            if($index === '' || $index == 1){
                $index = 1;
                $totalCount += $count;
            }else{
                $totalCount = $count;
            }

            if(isset($params['filter']['price'])){
                $prices = explode('-', $params['filter']['price']);
                $fromPrice = $prices[0];
                $toPrice = $prices[1];
            }else{
                $fromPrice = $priceRanges['range']*($index-1);
                $toPrice = $index == $maxIndex?'':$priceRanges['range']*($index);
            }

            if($index >= 1){
                $filters[$index] = [
                    'value' => $fromPrice.'-'.$toPrice,
                    'label' => $this->_renderRangeLabel($fromPrice, $toPrice),
                    'count' => (int)($totalCount)
                ];
            }
        }

        $layerFilters[] = [
            'attribute' => 'price',
            'title' => __('Price'),
            'filter' => array_values($filters),
        ];

        // category
        $childrenCategories = $this->category->getChildrenCategories();
        $collection->addCountToCategories($childrenCategories);
        $filters = [];
        foreach($childrenCategories as $childCategory){
            if($childCategory->getProductCount()){
                $filters[] = [
                    'label' => $childCategory->getName(),
                    'value' => $childCategory->getId(),
                    'count' => $childCategory->getProductCount()
                ];
            }
        }

        $layerFilters[] = [
            'attribute' => 'category_id',
            'title' => __('Categories'),
            'filter' => ($filters),
        ];

        return $layerFilters;
    }

    /*
     * Get price range filter
     *
     * @param @collection \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @return array
     */
    protected function _getPriceRanges($collection){
        $maxPrice = $collection->getMaxPrice();
        $index = 1;
        do {
            $range = pow(10, strlen(floor($maxPrice)) - $index);
            $counts = $collection->getAttributeValueCountByRange('price', $range);
            $index++;
        } while ($range > self::MIN_RANGE_POWER && count($counts) < 2);

        return ['range' => $range, 'counts' => $counts];
    }

    /*
     * Show price filter label
     *
     * @param $fromPrice int
     * @param $toPrice int
     * @return string
     */
    protected function _renderRangeLabel($fromPrice, $toPrice)
    {
        $helper = $this->_objectManager->create('Magento\Framework\Pricing\Helper\Data');
        $formattedFromPrice = $helper->currency($fromPrice, true, false);
        if ($toPrice === '') {
            return __('%1 and above', $formattedFromPrice);
        } elseif ($fromPrice == $toPrice) {
            return $formattedFromPrice;
        } else {
            if ($fromPrice != $toPrice) {
                $toPrice -= .01;
            }

            return __('%1 - %2', $formattedFromPrice, $helper->currency($toPrice, true, false));
        }
    }

    /*
     * Format data
     *
     * @var $data array
     * @return array
     */
    protected function _getData($collection, $params){
        $formattedItems = [];
        foreach($collection as $item){
            $formattedItem = [
                'product_id' => $item->getSku(),
                'product_name' => $item->getName(),
                'product_image' => $this->_getImageUrl($item->getImage()),
                'product_type' => $item->getTypeId(),
                'product_regular_price' => $item->getPrice(),
                'product_price' => $item->getFinalPrice(),
                'stock_status' => $item->getQuantityAndStockStatus()?true:false,
                'is_show_price' => true,
                'show_price_v2' => [
                    'product_regular_price' => $item->getPrice(),
                    'special_price_label' => 'Price',
                    'product_price' => $item->getFinalPrice(),
                    'minimal_price_label' => 'Min Price',
                    'minimal_price' => $item->getMinimalPrice(),
                ],
            ];

            $formattedItem = array_merge($formattedItem, $this->_getProductReview($item->getId()));
            $formattedItems[] = $formattedItem;
        }

        $navigator = $this->_getLayerNavigator($collection, $params);
        return ['data' => $formattedItems, 'layerednavigation' => $navigator, 'status' => 'SUCCESS', 'message' => [$collection->getSize()]];
    }
}

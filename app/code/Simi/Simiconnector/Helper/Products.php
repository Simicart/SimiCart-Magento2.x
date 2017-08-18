<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Products extends \Magento\Framework\App\Helper\AbstractHelper
{

    public $simiObjectManager;
    public $storeManager;
    public $builderQuery;
    public $data        = [];
    public $sortOrders = [];
    public $category;
    public $productStatus;
    public $productVisibility;

    const XML_PATH_RANGE_STEP = 'catalog/layered_navigation/price_range_step';
    const MIN_RANGE_POWER     = 10;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager
    ) {

        $this->simiObjectManager = $simiObjectManager;
        $this->scopeConfig      = $this->simiObjectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->storeManager     = $this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $this->productStatus     = $productStatus;
        $this->productVisibility = $productVisibility;
        parent::__construct($context);
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @return product collection.
     *
     */
    public function getBuilderQuery()
    {
        return $this->builderQuery;
    }

    public function getProduct($product_id)
    {
        $this->builderQuery = $this->simiObjectManager->create('Magento\Catalog\Model\Product')->load($product_id);
        if (!$this->builderQuery->getId()) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Resource cannot callable.'), 6);
        }
        return $this->builderQuery;
    }

    /**
     *
     */
    public function setCategoryProducts($category)
    {
        $this->category = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')->load($category);
        $this->setLayers(0);
        return $this;
    }

    /**
     * @param int $is_search
     * @param int $category
     * set Layer and collection on Products
     */
    public function setLayers($is_search = 0)
    {
        $data       = $this->getData();
        $controller = $data['controller'];
        $parameters = $data['params'];

        if (isset($parameters[\Simi\Simiconnector\Model\Api\Apiabstract::FILTER])) {
            $filter = $parameters[\Simi\Simiconnector\Model\Api\Apiabstract::FILTER];
            if ($is_search == 1) {
                $controller->getRequest()->setParam('q', (string) $filter['q']);
            }
            if (isset($filter['layer'])) {
                $filter_layer = $filter['layer'];
                $params       = [];
                foreach ($filter_layer as $key => $value) {
                    $params[(string) $key] = (string) $value;
                }
                $controller->getRequest()->setParams($params);
            }
        }

        $collection         = $this->simiObjectManager
            ->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
        $collection->addAttributeToSelect('*')
            ->addStoreFilter()
            ->addAttributeToFilter('status', 1)
            ->addFinalPrice();
        $collection         = $this->_filter($collection, $parameters);
        $this->builderQuery = $collection;
    }

    public function _filter($collection, $params)
    {
        if (isset($params['filter']['layer'])) {
            foreach ($params['filter']['layer'] as $key => $value) {
                if ($key == 'price') {
                    $value  = explode('-', $value);
                    $select = $collection->getSelect();
                    $whereFunction = 'where';
                    if ($value[0] > 0) {
                        $select->$whereFunction('price_index.final_price >= ' . $value[0]);
                    }
                    if ($value[1] > 0) {
                        $select->$whereFunction('price_index.final_price < ' . $value[1]);
                    }
                } else {
                    $collection->addAttributeToFilter($key, ['finset' => $value]);
                }
            }
        }

        //category
        if ($this->category) {
            $collection->addCategoryFilter($this->category);
        }

        //related products
        if (isset($params['filter']['related_to_id'])) {
            $product = $this->getProduct($params['filter']['related_to_id']);
            $allIds  = [];
            foreach ($product->getRelatedProducts() as $relatedProduct) {
                $allIds[] = $relatedProduct->getId();
            }
            $collection->addFieldToFilter('entity_id', ['in' => $allIds]);
        }

        //search
        if (isset($params['filter']['q'])) {
            $this->getSearchProducts($collection, $params);
        } else {
            $collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
            $collection->setVisibility($this->productVisibility->getVisibleInSiteIds());
        }

        $data       = $this->getData();
        $controller = $data['controller'];

        return $collection;
    }

    public function getSearchProducts(&$collection, $params)
    {
        $searchCollection = $this->simiObjectManager
            ->create('Magento\CatalogSearch\Model\ResourceModel\Fulltext\SearchCollection');
        $searchCollection->addSearchFilter($params['filter']['q']);
        $ids              = [];
        foreach ($searchCollection as $item) {
            $ids[] = $item->getId();
        }
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($ids) > 0) {
            $collection->addFieldToFilter('entity_id', ['in' => $ids]);
        }

        $collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
        $collection->setVisibility(['3', '4']);
    }

    public function getLayerNavigator($collection = null)
    {
        if (!$collection) {
            $collection = $this->builderQuery;
        }
        $data       = $this->getData();
        $params = $data['params'];

        $attributeCollection = $this->simiObjectManager
            ->create('Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection');
        $attributeCollection->addIsFilterableFilter()
            ->addVisibleFilter()
            ->addFieldToFilter('is_visible_on_front', 1);

        $allProductIds = $collection->getAllIds();
        $arrayIDs      = [];
        foreach ($allProductIds as $allProductId) {
            $arrayIDs[$allProductId] = '1';
        }
        $layerFilters = [];

        $titleFilters = [];
        $this->filterByAtribute($collection, $attributeCollection, $titleFilters, $layerFilters, $arrayIDs);

        $this->filterByPriceRange($layerFilters, $collection, $params);

        // category
        if ($this->category) {
            $childrenCategories = $this->category->getChildrenCategories();
            $collection->addCountToCategories($childrenCategories);
            $filters            = [];
            foreach ($childrenCategories as $childCategory) {
                if ($childCategory->getProductCount()) {
                    $filters[] = [
                        'label' => $childCategory->getName(),
                        'value' => $childCategory->getId(),
                        'count' => $childCategory->getProductCount()
                    ];
                }
            }

            $layerFilters[] = [
                'attribute' => 'category_id',
                'title'     => __('Categories'),
                'filter'    => ($filters),
            ];
        }

        $selectedFilters   = [];
        $selectableFilters = [];
        foreach ($layerFilters as $layerFilter) {
            if (($this->simiObjectManager
                        ->get('Simi\Simiconnector\Helper\Data')
                        ->countArray($layerFilter['filter']) == 1) && ($this->simiObjectManager
                        ->get('Simi\Simiconnector\Helper\Data')->countCollection($collection) > 1)) {
                $layerFilter['label'] = $layerFilter['filter'][0]['label'];
                $layerFilter['value'] = $layerFilter['filter'][0]['value'];
                unset($layerFilter['filter']);
                $selectedFilters[]    = $layerFilter;
            } else {
                $selectableFilters[] = $layerFilter;
            }
        }
        $layerArray = ['layer_filter' => $selectableFilters];
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($selectedFilters) > 0) {
            $layerArray['layer_state'] = $selectedFilters;
        }
        return $layerArray;
    }

    public function filterByAtribute($collection, $attributeCollection, &$titleFilters, &$layerFilters, $arrayIDs)
    {
        foreach ($attributeCollection as $attribute) {
            $attributeOptions = [];
            $attributeValues  = $collection->getAllAttributeValues($attribute->getAttributeCode());
            if (($attribute->getData('is_visible') != '1') || ($attribute->getData('is_filterable') != '1')
                || ($attribute->getData('is_visible_on_front') != '1')
                || (in_array($attribute->getDefaultFrontendLabel(), $titleFilters))) {
                continue;
            }
            foreach ($attributeValues as $productId => $optionIds) {
                if (isset($arrayIDs[$productId]) && ($arrayIDs[$productId] != null)) {
                    $optionIds = explode(',', $optionIds[0]);
                    foreach ($optionIds as $optionId) {
                        if (isset($attributeOptions[$optionId])) {
                            $attributeOptions[$optionId] ++;
                        } else {
                            $attributeOptions[$optionId] = 0;
                        }
                    }
                }
            }

            $options = $attribute->getSource()->getAllOptions();
            $filters = [];
            foreach ($options as $option) {
                if ($option['value'] && isset($attributeOptions[$option['value']])
                    && $attributeOptions[$option['value']]) {
                    $option['count'] = $attributeOptions[$option['value']];
                    $filters[]       = $option;
                }
            }

            if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($filters) >= 1) {
                $titleFilters[] = $attribute->getDefaultFrontendLabel();
                $layerFilters[] = [
                    'attribute' => $attribute->getAttributeCode(),
                    'title'     => $attribute->getDefaultFrontendLabel(),
                    'filter'    => $filters,
                ];
            }
        }
    }

    public function filterByPriceRange(&$layerFilters, $collection, $params)
    {
        $priceRanges = $this->_getPriceRanges($collection);
        $filters     = [];
        $totalCount  = 0;
        $maxIndex    = 0;
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($priceRanges['counts']) > 0) {
            $maxIndex = max(array_keys($priceRanges['counts']));
        }
        foreach ($priceRanges['counts'] as $index => $count) {
            if ($index === '' || $index == 1) {
                $index = 1;
                $totalCount += $count;
            } else {
                $totalCount = $count;
            }
            if (isset($params['layer']['price'])) {
                $prices    = explode('-', $params['layer']['price']);
                $fromPrice = $prices[0];
                $toPrice   = $prices[1];
            } else {
                $fromPrice = $priceRanges['range'] * ($index - 1);
                $toPrice   = $index == $maxIndex ? '' : $priceRanges['range'] * ($index);
            }

            if ($index >= 1) {
                $filters[$index] = [
                    'value' => $fromPrice . '-' . $toPrice,
                    'label' => $this->_renderRangeLabel($fromPrice, $toPrice),
                    'count' => (int) ($totalCount)
                ];
            }
        }

        $layerFilters[] = [
            'attribute' => 'price',
            'title'     => __('Price'),
            'filter'    => array_values($filters),
        ];
    }
    /*
     * Get price range filter
     *
     * @param @collection \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @return array
     */

    public function _getPriceRanges($collection)
    {
        $maxPrice = $collection->getMaxPrice();
        $index    = 1;
        do {
            $range  = pow(10, strlen(floor($maxPrice)) - $index);
            $counts = $collection->getAttributeValueCountByRange('price', $range);
            $index++;
        } while ($range > self::MIN_RANGE_POWER && $this->simiObjectManager
            ->get('Simi\Simiconnector\Helper\Data')->countArray($counts) < 2);

        return ['range' => $range, 'counts' => $counts];
    }

    /*
     * Show price filter label
     *
     * @param $fromPrice int
     * @param $toPrice int
     * @return string
     */

    public function _renderRangeLabel($fromPrice, $toPrice)
    {
        $helper             = $this->simiObjectManager->create('Magento\Framework\Pricing\Helper\Data');
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

    public function getImageProduct($product, $file = null, $width = null, $height = null)
    {
        if (!($width === null) && !($height === null)) {
            if ($file) {
                return $this->simiObjectManager->get('Magento\Catalog\Helper\Image')
                    ->init($product, 'product_page_image_medium')
                    ->setImageFile($file)
                    ->resize($width, $height)
                    ->getUrl();
            }
            return $this->simiObjectManager->get('Magento\Catalog\Helper\Image')
                ->init($product, 'product_page_image_medium')
                ->setImageFile($product->getFile())
                ->resize($width, $height)
                ->getUrl();
        }
        if ($file) {
            return $this->simiObjectManager->get('Magento\Catalog\Helper\Image')
                ->init($product, 'product_page_image_medium')
                ->setImageFile($file)
                ->resize(600, 600)
                ->getUrl();
        }
        return $this->simiObjectManager->get('Magento\Catalog\Helper\Image')
            ->init($product, 'product_page_image_medium')
            ->setImageFile($product->getFile())
            ->resize(600, 600)
            ->getUrl();
    }

    public function setStoreOrders($block_list, $block_toolbar, $is_search = 0)
    {
        if (!$block_toolbar->isExpanded()) {
            return;
        }
        $sort_orders = [];

        if ($sort = $block_list->getSortBy()) {
            $block_toolbar->setDefaultOrder($sort);
        }
        if ($dir = $block_list->getDefaultDirection()) {
            $block_toolbar->setDefaultDirection($dir);
        }

        $availableOrders = $block_toolbar->getAvailableOrders();

        if ($is_search == 1) {
            unset($availableOrders['position']);
            $availableOrders = array_merge([
                'relevance' => __('Relevance')
            ], $availableOrders);

            $block_toolbar->setAvailableOrders($availableOrders)
                ->setDefaultDirection('desc')
                ->setSortBy('relevance');
        }

        foreach ($availableOrders as $_key => $_order) {
            if ($block_toolbar->isOrderCurrent($_key)) {
                if ($block_toolbar->getCurrentDirection() == 'desc') {
                    $sort_orders[] = [
                        'key'       => $_key,
                        'value'     => $_order,
                        'direction' => 'asc',
                        'default'   => '0'
                    ];

                    $sort_orders[] = [
                        'key'       => $_key,
                        'value'     => $_order,
                        'direction' => 'desc',
                        'default'   => '1'
                    ];
                } else {
                    $sort_orders[] = [
                        'key'       => $_key,
                        'value'     => $_order,
                        'direction' => 'asc',
                        'default'   => '1'
                    ];
                    $sort_orders[] = [
                        'key'       => $_key,
                        'value'     => $_order,
                        'direction' => 'desc',
                        'default'   => '0'
                    ];
                }
            } else {
                $sort_orders[] = [
                    'key'       => $_key,
                    'value'     => $_order,
                    'direction' => 'asc',
                    'default'   => '0'
                ];

                $sort_orders[] = [
                    'key'       => $_key,
                    'value'     => $_order,
                    'direction' => 'desc',
                    'default'   => '0'
                ];
            }
        }
        $this->sortOrders = $sort_orders;
    }

    public function getStoreQrders()
    {
        if (!$this->sortOrders) {
            $block_toolbar = $this->simiObjectManager->get('Magento\Catalog\Block\Product\ProductList\Toolbar');
            $block_list    = $this->simiObjectManager->get('Magento\Catalog\Block\Product\ListProduct');
            $this->setStoreOrders($block_list, $block_toolbar, 0);
        }
        return $this->sortOrders;
    }
}

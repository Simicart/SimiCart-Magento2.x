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
    public $filteredAttributes = [];
    public $is_search = 0;

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

    public function loadCategoryWithId($id)
    {
        $categoryModel    = $this->simiObjectManager
            ->create('\Magento\Catalog\Model\Category')->load($id);
        return $categoryModel;
    }

    public function loadAttributeByKey($key)
    {
        return $this->simiObjectManager
            ->create('Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection')
            ->getItemByColumnValue('attribute_code', $key);
    }

    /**
     * @param int $is_search
     * @param int $category
     * set Layer and collection on Products
     */
    public function setLayers($is_search = 0)
    {
        $this->is_search = $is_search;
        $data       = $this->getData();
        $controller = isset($data['controller'])?$data['controller']:null;
        $parameters = $data['params'];

        if (isset($parameters[\Simi\Simiconnector\Model\Api\Apiabstract::FILTER])) {
            $filter = $parameters[\Simi\Simiconnector\Model\Api\Apiabstract::FILTER];
            if ($is_search == 1 && $controller) {
                $controller->getRequest()->setParam('q', (string) $filter['q']);
            }
            if (isset($filter['layer']) && $controller) {
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

        $fields = '*';
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }
        $collection->addAttributeToSelect($fields)
            ->addStoreFilter()
            ->addAttributeToFilter('status', 1)
            ->addFinalPrice();
        $collection         = $this->_filter($collection, $parameters);
        if (!$this->scopeConfig->getValue('cataloginventory/options/show_out_of_stock')) {
            $this->simiObjectManager->get('Magento\CatalogInventory\Helper\Stock')
                ->addInStockFilterToCollection($collection);
        }
        $this->builderQuery = $collection;
    }

    public function _filter($collection, $params)
    {
        $cat_filtered = false;
        
        //category
        if (!$cat_filtered && $this->category) {
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


        if (isset($params['filter']['layer'])) {
            $this->filterCollectionByAttribute($collection, $params, $cat_filtered);
        }
        
        return $collection;
    }

    public function filterCollectionByAttribute($collection, $params, &$cat_filtered)
    {
        foreach ($params['filter']['layer'] as $key => $value) {
            if ($key == 'price') {
                $value  = explode('-', $value);
                $select = $collection->getSelect();
                $whereFunction = 'where';
                if ($value[0] > 0) {
                    $this->filteredAttributes[$key] = $value;
                    $minPrice = $value[0];
                    $select->$whereFunction('price_index.final_price >= ' . $minPrice . " OR ( price_index.final_price = '0.0000' AND price_index.min_price >=" . $minPrice . ')');
                }
                if ($value[1] > 0) {
                    $this->filteredAttributes[$key] = $value;
                    $maxPrice = $value[1];
                    $select->$whereFunction('price_index.final_price < ' . $maxPrice . " OR ( price_index.final_price = '0.0000' AND price_index.min_price >=" . $maxPrice . ')');
                }
            } else {
                if ($key == 'category_id') {
                    $cat_filtered = true;
                    if ($this->category) {
                        if (is_array($value)) {
                            $value[] = $this->category->getId();
                        } else {
                            $value = [$this->category->getId(), $value];
                        }
                    }
                    $this->filteredAttributes[$key] = $value;
                    $collection->addCategoriesFilter(['in' => $value]);
                }elseif ($key == 'size' || $key == 'color') {
                    $this->filteredAttributes[$key] = $value;                    
                    # code...
                    $productIds = [];
                    $collectionChid         = $this->simiObjectManager
                        ->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
                  
                    $collectionChid->addAttributeToSelect('*')
                        ->addStoreFilter()
                        ->addAttributeToFilter('status', 1)
                        ->addFinalPrice();
                    if (is_array($value)) {
                        $insetArray = array();
                        foreach ($value as $child_value) {
                            $insetArray[] = array('finset'=> array($child_value));
                        }
                        $collectionChid->addAttributeToFilter($key, $insetArray);
                    } else
                        $collectionChid->addAttributeToFilter($key, ['finset' => $value]);
                    $collectionChid->getSelect()
                        ->joinLeft(
                            array('link_table' => 'catalog_product_super_link'),
                            'link_table.product_id = e.entity_id',
                            array('product_id', 'parent_id')
                        );

                    $collectionChid->getSelect()->group('link_table.parent_id');

                    foreach ($collectionChid as $product) {
                        $productIds[] = $product->getParentId();
                    }

                    $collection->addAttributeToFilter('entity_id', array('in' => $productIds));                                        
                } else {
                    $this->filteredAttributes[$key] = $value;
                    if (is_array($value)) {
                        $insetArray = array();
                        foreach ($value as $child_value) {
                            $insetArray[] = array('finset'=> array($child_value));
                        }
                        $collection->addAttributeToFilter($key, $insetArray);
                    } else
                        $collection->addAttributeToFilter($key, ['finset' => $value]);
                }
            }
        }
    }

    public function getSearchProducts(&$collection, $params)
    {
        $searchCollection = $this->simiObjectManager
            ->create('Magento\CatalogSearch\Model\ResourceModel\Fulltext\SearchCollection');
        $searchCollection->addSearchFilter($params['filter']['q']);
        $collection = $searchCollection;
        $collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
        $collection->addAttributeToSelect('*')
            ->addStoreFilter()
            ->addAttributeToFilter('status', 1)
            ->addFinalPrice();
    }

    public function getLayerNavigator($collection = null, $params = null)
    {
        if (!$collection) {
            $collection = $this->builderQuery;
        }
        if (!$params) {
            $data       = $this->getData();
            $params = isset($data['params'])?$data['params']:array();
        }
        $attributeCollection = $this->simiObjectManager
            ->create('Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection');
        $attributeCollection
            ->addIsFilterableFilter()
            //->addVisibleFilter() //cody comment out jun152019
            //->addFieldToFilter('used_in_product_listing', 1) //cody comment out jun152019
            //->addFieldToFilter('is_visible_on_front', 1) //cody comment out jun152019
        ;
        if ($this->is_search)
            $attributeCollection->addFieldToFilter('is_filterable_in_search', 1);


        $allProductIds = $collection->getAllIds();
        $arrayIDs      = [];
        foreach ($allProductIds as $allProductId) {
            $arrayIDs[$allProductId] = '1';
        }
        $layerFilters = [];

        $titleFilters = [];
        $this->_filterByAtribute($collection, $attributeCollection, $titleFilters, $layerFilters, $arrayIDs);

        if ($this->simiObjectManager
            ->get('Magento\Framework\App\ProductMetadataInterface')
            ->getEdition() != 'Enterprise')
            $this->_filterByPriceRange($layerFilters, $collection, $params);

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

        $paramArray = (array)$params;
        $selectedFilters = $this->_getSelectedFilters();
        $selectableFilters = count($allProductIds)?
            $this->_getSelectableFilters($collection, $paramArray, $selectedFilters, $layerFilters):
            array()
        ;

        $layerArray = ['layer_filter' => $selectableFilters];
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($selectedFilters) > 0) {
            $layerArray['layer_state'] = $selectedFilters;
        }

        return $layerArray;
    }

    public function _getSelectedFilters()
    {
        $selectedFilters   = [];
        foreach ($this->filteredAttributes as $key => $value) {
            if (($key == 'category_id') && is_array($value) &&
                ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($value)>=2)) {
                $value = $value[1];
                $category = $this->loadCategoryWithId($value);
                $selectedFilters[] = [
                    'value'=>$value,
                    'label'=>$category->getName(),
                    'attribute' => 'category_id',
                    'title'     => __('Categories'),
                ];
                continue;
            }
            if (($key == 'price') && is_array($value) &&
                ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($value)>=2)) {
                $selectedFilters[] = [
                    'value'=> implode('-', $value),
                    'label'=> $this->_renderRangeLabel($value[0], $value[1]),
                    'attribute' => 'price',
                    'title'     => __('Price')
                ];
                continue;
            }

            $attribute = $this->loadAttributeByKey($key);
            if (is_array($value)) {
                $value = $value[0];
            }
            if ($attribute)
                foreach ($attribute->getSource()->getAllOptions() as $layerFilter) {
                    if ($layerFilter['value'] == $value) {
                        $layerFilter['attribute'] = $key;
                        $layerFilter['title'] = $attribute->getDefaultFrontendLabel();
                        $selectedFilters[]    = $layerFilter;
                    }
                }
        }
        return $selectedFilters;
    }

    public function _getSelectableFilters($collection, $paramArray, $selectedFilters, $layerFilters)
    {
        $selectableFilters = [];
        if (is_array($paramArray) && isset($paramArray['filter'])) {
            foreach ($layerFilters as $layerFilter) {
                $filterable = true;
                foreach ($selectedFilters as $key => $value) {
                    if ($layerFilter['attribute'] == $value['attribute']) {
                        $filterable = false;
                        break;
                    }
                }
                if ($filterable) {
                    $selectableFilters[] = $layerFilter;
                }
            }
        }
        return $selectableFilters;
    }

    public function _filterByAtribute($collection, $attributeCollection, &$titleFilters, &$layerFilters, $arrayIDs)
    {
        $childProductsIds      = [];
        if ($arrayIDs && count($arrayIDs)) {
            $childProducts = $this->simiObjectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection')
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('type_id', 'simple');
            $select = $childProducts->getSelect();
            $select->joinLeft(
                    array('link_table' => 'catalog_product_super_link'),
                    'link_table.product_id = e.entity_id',
                    array('product_id', 'parent_id')
                );
            $select = $childProducts->getSelect();
            $select->where("link_table.parent_id IN (".implode(',', array_keys($arrayIDs)).")");
            foreach ($childProducts->getAllIds() as $allProductId) {
                $childProductsIds[$allProductId] = '1';
            }
        }

        foreach ($attributeCollection as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $attributeOptions = [];
            $attributeValues  = $collection->getAllAttributeValues($attribute->getAttributeCode());
            if (in_array($attribute->getDefaultFrontendLabel(), $titleFilters)) {
                continue;
            }
            foreach ($attributeValues as $productId => $optionIds) {
                if (isset($optionIds[0]) &&
                    (
                        (isset($arrayIDs[$productId]) && ($arrayIDs[$productId] != null)) ||
                        (isset($childProductsIds[$productId]) && ($childProductsIds[$productId] != null))
                    )
                ) {
                    $optionIds = explode(',', $optionIds[0]);
                    foreach ($optionIds as $optionId) {
                        if (isset($attributeOptions[$optionId])) {
                            $attributeOptions[$optionId] ++;
                        } else {
                            $attributeOptions[$optionId] = 1;
                        }
                    }
                }
            }

            $options = $attribute->getSource()->getAllOptions();
            $filters = [];
            foreach ($options as $option) {
                if (isset($option['value']) && isset($attributeOptions[$option['value']])
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

    public function _filterByPriceRange(&$layerFilters, $collection, $params)
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
        if ($this->simiObjectManager
                ->get('Simi\Simiconnector\Helper\Data')
                ->countArray($filters) >= 1) {
            $layerFilters[] = [
                'attribute' => 'price',
                'title'     => __('Price'),
                'filter'    => array_values($filters),
            ];
        }
    }
    /*
     * Get price range filter
     *
     * @param @collection \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @return array
     */

    public function _getPriceRanges($collection)
    {
        $collection->addPriceData();
        $maxPrice = $collection->getMaxPrice();

        $index    = 1;
        $counts = [];
        do {
            $range  = pow(10, strlen(floor($maxPrice)) - $index);
            $counts = $collection->getAttributeValueCountByRange('price', $range);
            $index++;
        } while ($range > self::MIN_RANGE_POWER && count($counts) < 2 && $index <= 2);

        //re-forming array
        if (isset($counts[''])) {
            $counts[0] = $counts[''];
            unset($counts['']);
            $newCounts = [];
            foreach ($counts as $key => $count) {
                $newCounts[$key+1] = $counts[$key];
            }
            $counts = $newCounts;
        }
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
    
    public function getImageProduct($product, $file = null, $width = 600, $height = 600)
    {
        $file = $file ?: $product->getFile() ?: $product->getImage();
        if (!$file || $file === 'no_selection') {
            $imageHelper = $this->simiObjectManager->get('Magento\Catalog\Helper\Image');
            $placeholderImageUrl = $imageHelper->getDefaultPlaceholderUrl('image');
            return $placeholderImageUrl;
        }
        return $this->simiObjectManager->get('Magento\Catalog\Helper\Image')
            ->init($product, 'product_page_image_medium')
            ->setImageFile($file)
            ->keepFrame(FALSE)
            ->resize($width, $height)
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
                ->setDefaultDirection('asc')
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
            $data = $this->getData();
            if (isset($data['params']['order']) && isset($data['params']['dir'])) {
                $block_list->setSortBy($data['params']['order']);
                $block_list->setDefaultDirection($data['params']['dir']);
            }
            $this->setStoreOrders($block_list, $block_toolbar, $this->is_search);
        }
        return $this->sortOrders;
    }
}

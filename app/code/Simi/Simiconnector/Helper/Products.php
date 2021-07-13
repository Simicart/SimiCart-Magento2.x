<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Bundle\Model\ResourceModel\Selection as BundleSelection;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link as GroupedProductLink;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Api\Data\ProductInterface;
use \Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use \Magento\Bundle\Model\Product\Type as BundleType;

class Products extends \Magento\Framework\App\Helper\AbstractHelper
{

    public $simiObjectManager;
    public $storeManager;
    public $builderQuery;
    public $data = [];
    public $sortOrders = [];
    public $category;
    public $productStatus;
    public $productVisibility;
    public $filteredAttributes = [];
    public $is_search = 0;
    public $bundleSelection;
    public $groupedProductLink;
    public $metadataPool;
    public $productCollectionFactory;

    const XML_PATH_RANGE_STEP = 'catalog/layered_navigation/price_range_step';
    const MIN_RANGE_POWER = 10;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        BundleSelection $bundleSelection,
        GroupedProductLink $groupedProductLink,
        MetadataPool $metadataPool,
        GroupedType $groupType,
        BundleType $bundleType,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager
    )
    {

        $this->simiObjectManager = $simiObjectManager;
        $this->scopeConfig = $this->simiObjectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->storeManager = $this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->bundleSelection = $bundleSelection;
        $this->groupedProductLink = $groupedProductLink;
        $this->metadataPool = $metadataPool;
        $this->groupType = $groupType;
        $this->bundleType = $bundleType;
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
        $categoryModel = $this->simiObjectManager
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
        $data = $this->getData();
        $controller = isset($data['controller']) ? $data['controller'] : null;
        $parameters = $data['params'];

        if (isset($parameters[\Simi\Simiconnector\Model\Api\Apiabstract::FILTER])) {
            $filter = $parameters[\Simi\Simiconnector\Model\Api\Apiabstract::FILTER];
            if ($is_search == 1 && $controller) {
                $controller->getRequest()->setParam('q', (string)$filter['q']);
            }
            if (isset($filter['layer']) && $controller) {
                $filter_layer = $filter['layer'];
                $params = [];
                foreach ($filter_layer as $key => $value) {
                    $params[(string)$key] = (string)$value;
                }
                $controller->getRequest()->setParams($params);
            }
        }

        $collection = $this->simiObjectManager
            ->create('Magento\Catalog\Model\ResourceModel\Product\Collection');

        $fields = '*';
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }
        $collection->addAttributeToSelect($fields)
            ->addStoreFilter()
            ->addAttributeToFilter('status', 1)
            ->addFinalPrice();
        $collection = $this->_filter($collection, $parameters);
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
            $allIds = [];
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
        $pIdsToFilter = false;
        foreach ($params['filter']['layer'] as $key => $value) {
            if ($key == 'price') {
                $value = explode('-', $value);
                $priceFilter = array();
                if (isset($value[0]))
                    $priceFilter['from'] = $value[0];
                if (isset($value[0]))
                    $priceFilter['to'] = $value[1];
                $collection->addFieldToFilter('price', $priceFilter);
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
                } else {
                    $this->filteredAttributes[$key] = $value;
                    # code...
                    $productIds = [];
                    $collectionChid = $this->productCollectionFactory->create();

                    $collectionChid->addAttributeToSelect('*')
                        ->addStoreFilter()
                        ->addAttributeToFilter('status', 1);
                    if ($pIdsToFilter) {
                        $collectionChid->addFieldToFilter('entity_id', ['in' => $pIdsToFilter]);
                    }
                    if (is_array($value)) {
                        $insetArray = array();
                        foreach ($value as $child_value) {
                            $insetArray[] = array('finset' => array($child_value));
                        }
                        $collectionChid->addAttributeToFilter($key, $insetArray);
                    } else
                        $collectionChid->addAttributeToFilter($key, ['finset' => $value]);
                    $collectionChid->getSelect()
                        ->joinLeft(
                            array('link_table' => $collection->getResource()->getTable('catalog_product_super_link')),
                            'link_table.product_id = e.entity_id',
                            array('product_id', 'parent_id')
                        );

                    foreach ($collectionChid as $product) {
                        // check for group products
                        if (
                            $this->groupType->getParentIdsByChild($product->getId())
                            && is_array($this->groupType->getParentIdsByChild($product->getId()))
                            && count($this->groupType->getParentIdsByChild($product->getId()))
                        ) {
                            $productIds = array_merge($productIds, $this->groupType->getParentIdsByChild($product->getId()));
                        }
                        // check for bundle products
                        if (
                            $this->bundleType->getParentIdsByChild($product->getId())
                            && is_array($this->bundleType->getParentIdsByChild($product->getId()))
                            && count($this->bundleType->getParentIdsByChild($product->getId()))
                        ) {
                            $productIds = array_merge($productIds, $this->bundleType->getParentIdsByChild($product->getId()));
                        }
                        $productIds[] = $product->getParentId() ? $product->getParentId() : $product->getId();
                    }

                    foreach ($collectionChid as $product) {
                        $productIds[] = $product->getParentId();
                    }
                    if (!$pIdsToFilter)
                        $pIdsToFilter = [];
                    $pIdsToFilter = array_merge($pIdsToFilter, $productIds);
                    /*
                    If the site has no configurable/bundle and grouped product, comment the lines above of this closure and 
                    uncomment the lines below
                    */
                    /*
                    $this->filteredAttributes[$key] = $value;
                    if (is_array($value)) {
                        $insetArray = array();
                        foreach ($value as $child_value) {
                            $insetArray[] = array('finset' => array($child_value));
                        }
                        $collection->addAttributeToFilter($key, $insetArray);
                    } else
                        $collection->addAttributeToFilter($key, ['finset' => $value]);
                    */
                }
            }
            if ($pIdsToFilter)
                $collection->addAttributeToFilter('entity_id', array('in' => $pIdsToFilter));
        }
    }

    public function getSearchProducts(&$collection, $params)
    {
        //$searchCollection = $this->simiObjectManager
        //    ->create('Magento\CatalogSearch\Model\ResourceModel\Fulltext\SearchCollection');
        $searchCollection = $this->simiObjectManager
            ->create('Magento\Search\Model\SearchCollectionInterface'); // For magento 2.4
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
            $data = $this->getData();
            $params = isset($data['params']) ? $data['params'] : array();
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

        $attributeCollection->addFieldToFilter('attribute_code', ['nin' => ['price']]);


        $allProductIds = $collection->getAllIds();
        $arrayIDs = [];
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
            $filters = [];
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
                'title' => __('Categories'),
                'filter' => ($filters),
            ];
        }

        $paramArray = (array)$params;
        $selectedFilters = $this->_getSelectedFilters();
        $selectableFilters = count($allProductIds) ?
            $this->_getSelectableFilters($collection, $paramArray, $selectedFilters, $layerFilters) :
            array();

        $layerArray = ['layer_filter' => $selectableFilters];
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($selectedFilters) > 0) {
            $layerArray['layer_state'] = $selectedFilters;
        }

        return $layerArray;
    }

    public function _getSelectedFilters()
    {
        $selectedFilters = [];
        foreach ($this->filteredAttributes as $key => $value) {
            if (($key == 'category_id') && is_array($value) &&
                ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($value) >= 2)) {
                $value = $value[1];
                $category = $this->loadCategoryWithId($value);
                $selectedFilters[] = [
                    'value' => $value,
                    'label' => $category->getName(),
                    'attribute' => 'category_id',
                    'title' => __('Categories'),
                ];
                continue;
            }
            if (($key == 'price') && is_array($value) &&
                ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($value) >= 2)) {
                $selectedFilters[] = [
                    'value' => implode('-', $value),
                    'label' => $this->_renderRangeLabel($value[0], $value[1]),
                    'attribute' => 'price',
                    'title' => __('Price')
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
                        $selectedFilters[] = $layerFilter;
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
        
        $childProductsIds = [];
        if ($arrayIDs && count($arrayIDs)) {
            $resource = $collection->getResource();
            //configurable products
            $childProducts = $this->productCollectionFactory->create()
                ->addAttributeToSelect('*');
            $select = $childProducts->getSelect();
            $select->joinLeft(
                array('link_table' => $resource->getTable('catalog_product_super_link')),
                'link_table.product_id = e.entity_id',
                array('product_id', 'parent_id')
            );
            $select = $childProducts->getSelect();
            $select->where("link_table.parent_id IN (" . implode(',', array_keys($arrayIDs)) . ")");
            foreach ($childProducts->getAllIds() as $allProductId) {
                $childProductsIds[$allProductId] = '1';
            }
            //bundle products
            $connection = $this->bundleSelection->getConnection();
            $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
            $select = $connection->select()->from(
                ['tbl_selection' => $this->bundleSelection->getMainTable()],
                ['product_id', 'parent_product_id', 'option_id']
            )->join(
                ['e' => $resource->getTable('catalog_product_entity')],
                'e.entity_id = tbl_selection.product_id AND e.required_options=0',
                []
            )->join(
                ['parent' => $resource->getTable('catalog_product_entity')],
                'tbl_selection.parent_product_id = parent.' . $linkField
            )->join(
                ['tbl_option' => $resource->getTable('catalog_product_bundle_option')],
                'tbl_option.option_id = tbl_selection.option_id',
                ['required']
            )->where(
                'parent.entity_id IN (' . implode(',', array_keys($arrayIDs)) . ')'
            );
            foreach ($connection->fetchAll($select) as $row) {
                if (isset($row['product_id']))
                    $childProductsIds[$row['product_id']] = '1';
            }
            //grouped products
            $connection = $this->groupedProductLink->getConnection();
            $bind = [':link_type_id' => GroupedProductLink::LINK_TYPE_GROUPED];
            $select = $connection->select()->from(
                ['l' => $this->groupedProductLink->getMainTable()],
                ['linked_product_id']
            )->join(
                ['cpe' => $this->groupedProductLink->getTable('catalog_product_entity')],
                sprintf(
                    'cpe.%s = l.product_id',
                    $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField()
                )
            )->where(
                'cpe.entity_id IN (' . implode(',', array_keys($arrayIDs)) . ')'
            )->where(
                'link_type_id = :link_type_id'
            );

            $select->join(
                ['e' => $this->groupedProductLink->getTable('catalog_product_entity')],
                'e.entity_id = l.linked_product_id AND e.required_options = 0',
                []
            );
            foreach ($connection->fetchAll($select, $bind) as $row) {
                if (isset($row['linked_product_id']))
                    $childProductsIds[$row['linked_product_id']] = '1';
            }
        }
        $childAndParentIds = array_merge(array_keys($childProductsIds), array_keys($arrayIDs));

        foreach ($attributeCollection as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $attributeOptions = [];
            $attributeValues = $collection->getAllAttributeValues($attributeCode, $collection, $childAndParentIds);
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
                            $attributeOptions[$optionId]++;
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
                    $filters[] = $option;
                }
            }

            if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($filters) >= 1) {
                $titleFilters[] = $attribute->getDefaultFrontendLabel();
                $layerFilters[] = [
                    'attribute' => $attribute->getAttributeCode(),
                    'title' => $attribute->getDefaultFrontendLabel(),
                    'filter' => $filters,
                ];
            }
        }
    }

    public function _filterByPriceRange(&$layerFilters, $collection, $params)
    {
        $priceRanges = $this->_getPriceRanges($collection);
        $filters = [];
        $totalCount = 0;
        $maxIndex = 0;
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
                $prices = explode('-', $params['layer']['price']);
                $fromPrice = $prices[0];
                $toPrice = $prices[1];
            } else {
                $fromPrice = $priceRanges['range'] * ($index - 1);
                $toPrice = $index == $maxIndex ? '' : $priceRanges['range'] * ($index);
            }

            if ($index >= 1) {
                $filters[$index] = [
                    'value' => $fromPrice . '-' . $toPrice,
                    'label' => $this->_renderRangeLabel($fromPrice, $toPrice),
                    'count' => (int)($totalCount)
                ];
            }
        }
        if ($this->simiObjectManager
                ->get('Simi\Simiconnector\Helper\Data')
                ->countArray($filters) >= 1) {
            $layerFilters[] = [
                'attribute' => 'price',
                'title' => __('Price'),
                'filter' => array_values($filters),
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

        $index = 1;
        $counts = [];
        do {
            $range = pow(10, strlen(floor($maxPrice)) - $index);
            $counts = $collection->getAttributeValueCountByRange('price', $range);
            $index++;
        } while ($range > self::MIN_RANGE_POWER && count($counts) < 2 && $index <= 2);

        //re-forming array
        if (isset($counts[''])) {
            $counts[0] = $counts[''];
            unset($counts['']);
            $newCounts = [];
            foreach ($counts as $key => $count) {
                $newCounts[$key + 1] = $counts[$key];
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
        $helper = $this->simiObjectManager->create('Magento\Framework\Pricing\Helper\Data');
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
                        'key' => $_key,
                        'value' => $_order,
                        'direction' => 'asc',
                        'default' => '0'
                    ];

                    $sort_orders[] = [
                        'key' => $_key,
                        'value' => $_order,
                        'direction' => 'desc',
                        'default' => '1'
                    ];
                } else {
                    $sort_orders[] = [
                        'key' => $_key,
                        'value' => $_order,
                        'direction' => 'asc',
                        'default' => '1'
                    ];
                    $sort_orders[] = [
                        'key' => $_key,
                        'value' => $_order,
                        'direction' => 'desc',
                        'default' => '0'
                    ];
                }
            } else {
                $sort_orders[] = [
                    'key' => $_key,
                    'value' => $_order,
                    'direction' => 'asc',
                    'default' => '0'
                ];

                $sort_orders[] = [
                    'key' => $_key,
                    'value' => $_order,
                    'direction' => 'desc',
                    'default' => '0'
                ];
            }
        }
        $this->sortOrders = $sort_orders;
    }

    public function getStoreQrders()
    {
        if (!$this->sortOrders) {
            $block_toolbar = $this->simiObjectManager->get('Magento\Catalog\Block\Product\ProductList\Toolbar');
            $block_list = $this->simiObjectManager->get('Magento\Catalog\Block\Product\ListProduct');
            $data = $this->getData();
            if (isset($data['params']['order']) && isset($data['params']['dir'])) {
                $block_list->setSortBy($data['params']['order']);
                $block_list->setDefaultDirection($data['params']['dir']);
            }
            $this->setStoreOrders($block_list, $block_toolbar, $this->is_search);
        }
        return $this->sortOrders;
    }

    /**
     * Return all attribute values as array in form:
     * array(
     *   [entity_id_1] => array(
     *          [store_id_1] => store_value_1,
     *          [store_id_2] => store_value_2,
     *          ...
     *          [store_id_n] => store_value_n
     *   ),
     *   ...
     * )
     *
     * @param string $attribute attribute code
     * @param Magento\Catalog\Model\ResourceModel\Product\Collection $collection product collection
     * @param array $childAndParentIds id of product to filter
     * @return array
     */
    public function getAllAttributeValues($attribute, $collection, $childAndParentIds)
    {
        /** @var $select \Magento\Framework\DB\Select */
        $select = clone $collection->getSelect();
        $attribute = $collection->getEntity()->getAttribute($attribute);

        $fieldMainTable = $collection->getConnection()->getAutoIncrementField($collection->getMainTable());
        $fieldJoinTable = $attribute->getEntity()->getLinkField();
        $select->reset()
            ->from(
                ['cpe' => $collection->getMainTable()],
                ['entity_id']
            )->join(
                ['cpa' => $attribute->getBackend()->getTable()],
                'cpe.' . $fieldMainTable . ' = cpa.' . $fieldJoinTable,
                ['store_id', 'value']
            )->where('attribute_id = ?', (int)$attribute->getId())
            ->where('cpe.entity_id IN (?)', $childAndParentIds);

        $data = $collection->getConnection()->fetchAll($select);
        $res = [];

        foreach ($data as $row) {
            $res[$row['entity_id']][$row['store_id']] = $row['value'];
        }

        return $res;
    }
}

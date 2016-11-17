<?php

/**
 * Connector data helper
 */
namespace Simi\Simiconnector\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Products extends \Magento\Framework\App\Helper\AbstractHelper
{   
    protected $_objectManager;
    protected $_storeManager;
    
    protected $builderQuery;
    protected $_data = array();
    protected $_sortOrders = array();
    
    public $category;
    public $productStatus;
    public $productVisibility;

    const XML_PATH_RANGE_STEP = 'catalog/layered_navigation/price_range_step';
    const MIN_RANGE_POWER = 10;
    
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\File\Size $fileSize,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\Filesystem\Io\File $ioFile,
        \Magento\Store\Model\StoreManagerInterface $storeManager,    
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Framework\Image\Factory $imageFactory
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_scopeConfig = $this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->_storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        parent::__construct($context);
    }
    

    public function setData($data)
    {
        $this->_data = $data;
    }

    public function getData()
    {
        return $this->_data;
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
		$this->builderQuery = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
        if(!$this->builderQuery->getId())
			throw new \Exception(__('Resource cannot callable.'), 6);
		return $this->builderQuery;
    }

    /**
     *
     */
    public function setCategoryProducts($category)
    {
        $this->category = $this->_objectManager->create('\Magento\Catalog\Model\Category')->load($category);
        $this->setLayers(0, $this->category);
        return $this;
    }

    /**
     * @param int $is_search
     * @param int $category
     * set Layer and collection on Products
     */
    public function setLayers($is_search = 0, $category = 0)
    {
        $data = $this->getData();
        $controller = $data['controller'];
        $parameters = $data['params'];
        
        if (isset($parameters[\Simi\Simiconnector\Model\Api\Apiabstract::FILTER])) {
            $filter = $parameters[\Simi\Simiconnector\Model\Api\Apiabstract::FILTER];
            if ($is_search == 1) {
                $controller->getRequest()->setParam('q', (string)$filter['q']);
            }
            if (isset($filter['layer'])) {
                $filter_layer = $filter['layer'];
                $params = array();
                foreach ($filter_layer as $key => $value) {
                    $params[(string)$key] = (string)$value;
                }
                $controller->getRequest()->setParams($params);
            }
        }
		
        $collection = $this->_objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
        $collection->addAttributeToSelect('*')
            ->addStoreFilter()
            ->addAttributeToFilter('status', 1)
            ->addFinalPrice();
        $collection = $this->_filter($collection, $parameters);
        $this->builderQuery = $collection;
                
    }
    
    protected function _filter($collection, $params)
    {
        if (isset($params['filter']['layer']))
            foreach ($params['filter']['layer'] as $key => $value) {
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
        if ($this->category) {
            $collection->addCategoryFilter($this->category);
        }
        
        //related products
        if(isset($params['filter']['related_to_id'])){
            $product = $this->getProduct($params['filter']['related_to_id']);
            $allIds = array();
            foreach ($product->getRelatedProducts() as $relatedProduct) {
                $allIds[] = $relatedProduct->getId();
            }
            if (count($allIds) > 0)
                $collection->addFieldToFilter('entity_id', ['in' => $allIds]);
        }

        //search
        if(isset($params['filter']['q'])){
            $searchCollection = $this->_objectManager
                ->create('Magento\CatalogSearch\Model\ResourceModel\Fulltext\SearchCollection');
            $searchCollection->addSearchFilter($params['filter']['q']);
            $ids = array();
            foreach($searchCollection as $item){
                $ids[] = $item->getId();
            }
            if (count($ids)>0)
                $collection->addFieldToFilter('entity_id', ['in' => $ids]);

                $collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
                $collection->setVisibility(array('3', '4'));
				//$collection->getVisibleInSearchIds($this->productVisibility->getVisibleInSiteIds());
        }
        else {
            $collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
            $collection->setVisibility($this->productVisibility->getVisibleInSiteIds());
        }
        
        $data = $this->getData();
        $controller = $data['controller'];
            
        return $collection;
    }
    
    
    public function getLayerNavigator($collection = null, $parameters = null)
    {   
        if(!$collection)
            $collection = $this->builderQuery;
        
        $attributeCollection = $this->_objectManager
            ->create('Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection');
        $attributeCollection->addIsFilterableFilter()
                ->addVisibleFilter()
                ->addFieldToFilter('is_visible_on_front', 1);
        
        $allProductIds = $collection->getAllIds();
		$arrayAhah = array();
		foreach ($allProductIds as $allProductId) {
			$arrayAhah[$allProductId] = '1';
		}
        $layerFilters = [];
        $i = 0;
			
		$titleFilters = array();
        foreach($attributeCollection as $attribute){
            $attributeOptions = [];
            $attributeValues = $collection->getAllAttributeValues($attribute->getAttributeCode());
			if ($attribute->getData('is_visible') != '1')
				continue;
			if ($attribute->getData('is_filterable') != '1')
				continue;
			if ($attribute->getData('is_visible_on_front') != '1')
				continue;
			if ($attribute->getData('used_in_product_listing') != '1')
				continue;
			/*
			if ($attribute->getData('is_global') != '2')
				continue;
			*/
			if (in_array($attribute->getDefaultFrontendLabel(), $titleFilters))
				continue;
            foreach($attributeValues as $productId => $optionIds){
                if(isset($arrayAhah[$productId]) && ($arrayAhah[$productId]!= null)){
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
            
            if(count($filters) > 1){
				$titleFilters[] = $attribute->getDefaultFrontendLabel();
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
        $maxIndex = 0;
        if(count($priceRanges['counts'])>0)
            $maxIndex = max(array_keys($priceRanges['counts']));
        foreach($priceRanges['counts'] as $index => $count){
            if($index === '' || $index == 1){
                $index = 1;
                $totalCount += $count;
            }else{
                $totalCount = $count;
            }
            if(isset($params['layer']['price'])){
                $prices = explode('-', $params['layer']['price']);
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
        if ($this->category) {
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
        }
        
        $selectedFilters = array();
        $selectableFilters = array();
        foreach ($layerFilters as $layerFilter){
            if ((count($layerFilter['filter']) == 1) &&($collection->count()>1)) 
            {
                $layerFilter['label'] = $layerFilter['filter'][0]['label'];
                $layerFilter['value'] = $layerFilter['filter'][0]['value'];
                unset($layerFilter['filter']);
                $selectedFilters[] = $layerFilter;
            }
            else 
                $selectableFilters[] = $layerFilter;
        }
        $layerArray = array('layer_filter'=>$selectableFilters);
        if (count($selectedFilters)>0) {
            $layerArray['layer_state']=$selectedFilters;
        }
        return $layerArray;
		
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
    
    public function getImageProduct($product, $file = null, $width = null, $height = null)
    {
        if (!is_null($width) && !is_null($height)) {
            if ($file) {
                return $this->_objectManager->get('Magento\Catalog\Helper\Image')
                    ->init($product, 'product_page_image_medium')
                    ->setImageFile($file)
                    ->resize($width, $height)
                    ->getUrl();
            
            }
            return $this->_objectManager->get('Magento\Catalog\Helper\Image')
                ->init($product, 'product_page_image_medium')
                ->setImageFile($product->getFile())
                ->resize($width, $height)
                ->getUrl();
            
        }
        if ($file) {
                return $this->_objectManager->get('Magento\Catalog\Helper\Image')
                    ->init($product, 'product_page_image_medium')
                    ->setImageFile($file)
                    ->resize(600, 600)
                    ->getUrl();
            
            }
        return $this->_objectManager->get('Magento\Catalog\Helper\Image')
            ->init($product, 'product_page_image_medium')
            ->setImageFile($product->getFile())
            ->resize(600, 600)
            ->getUrl();
    }
    
    public function setStoreOrders($block_list, $block_toolbar, $is_search = 0)
    {
        if (!$block_toolbar->isExpanded()) return;
        $sort_orders = array();

        if ($sort = $block_list->getSortBy()) {
            $block_toolbar->setDefaultOrder($sort);
        }
        if ($dir = $block_list->getDefaultDirection()) {
            $block_toolbar->setDefaultDirection($dir);
        }
        
        $availableOrders = $block_toolbar->getAvailableOrders();
        
        if ($is_search == 1) {
            unset($availableOrders['position']);
            $availableOrders = array_merge(array(
                'relevance' => __('Relevance')
            ), $availableOrders);

            $block_toolbar->setAvailableOrders($availableOrders)
                ->setDefaultDirection('desc')
                ->setSortBy('relevance');
        }

        foreach ($availableOrders as $_key => $_order) {
            if ($block_toolbar->isOrderCurrent($_key)) {
                if ($block_toolbar->getCurrentDirection() == 'desc') {
                    $sort_orders[] = array(
                        'key' => $_key,
                        'value' => $_order,
                        'direction' => 'asc',
                        'default' => '0'
                    );

                    $sort_orders[] = array(
                        'key' => $_key,
                        'value' => $_order,
                        'direction' => 'desc',
                        'default' => '1'
                    );
                } else {
                    $sort_orders[] = array(
                        'key' => $_key,
                        'value' => $_order,
                        'direction' => 'asc',
                        'default' => '1'
                    );
                    $sort_orders[] = array(
                        'key' => $_key,
                        'value' => $_order,
                        'direction' => 'desc',
                        'default' => '0'
                    );
                }
            } else {
                $sort_orders[] = array(
                    'key' => $_key,
                    'value' => $_order,
                    'direction' => 'asc',
                    'default' => '0'
                );

                $sort_orders[] = array(
                    'key' => $_key,
                    'value' => $_order,
                    'direction' => 'desc',
                    'default' => '0'
                );
            }
        }
        $this->_sortOrders = $sort_orders;
    }
    
    public function getStoreQrders()
    {
        if (!$this->_sortOrders) {
            $block_toolbar = $this->_objectManager->get('Magento\Catalog\Block\Product\ProductList\Toolbar');
            $block_list = $this->_objectManager->get('Magento\Catalog\Block\Product\ListProduct');
            $this->setStoreOrders($block_list, $block_toolbar, 0);
        }
        return $this->_sortOrders;
    }

}


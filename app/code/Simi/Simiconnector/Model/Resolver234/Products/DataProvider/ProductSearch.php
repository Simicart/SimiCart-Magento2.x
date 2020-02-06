<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Simi\Simiconnector\Model\Resolver234\Products\DataProvider;

use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionPostProcessor;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierFactory;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;

/**
 * Product field data provider for product search, used for GraphQL resolver processing.
 */
class ProductSearch
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProductSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionPreProcessor;

    /**
     * @var CollectionPostProcessor
     */
    private $collectionPostProcessor;

    /**
     * @var SearchResultApplierFactory;
     */
    private $searchResultApplierFactory;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionPreProcessor
     * @param CollectionPostProcessor $collectionPostProcessor
     * @param SearchResultApplierFactory $searchResultsApplierFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ProductSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionPreProcessor,
        CollectionPostProcessor $collectionPostProcessor,
        SearchResultApplierFactory $searchResultsApplierFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionPreProcessor = $collectionPreProcessor;
        $this->collectionPostProcessor = $collectionPostProcessor;
        $this->searchResultApplierFactory = $searchResultsApplierFactory;
    }

    /**
     * Get list of product data with full data set. Adds eav attributes to result set from passed in array
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param SearchResultInterface $searchResult
     * @param array $attributes
     * @return SearchResultsInterface
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria,
        SearchResultInterface $searchResult,
        array $attributes = [],
        array $args //simiconnector changing
    ): SearchResultsInterface {
        /** @var Collection $collection */
        // $collection = $this->collectionFactory->create();

        // //Join search results
        // $this->getSearchResultsApplier($searchResult, $collection, $this->getSortOrderArray($searchCriteria))->apply();

        // $this->collectionPreProcessor->process($collection, $searchCriteria, $attributes);
        // $collection->load();
        // $this->collectionPostProcessor->process($collection, $attributes);

        /*
         * simiconnector changing
        */
        $collection = null;
        $this->simiObjectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Products');
        $params = array(
            'filter' => array()
        );
        /*
         * apply filter
         */
        $is_search = 0;
        //filter by search query
        if ($args && isset($args['search']) && $args['search']) {
            $is_search = 1;
            $helper->is_search = 1;
            $params['filter']['q'] = $args['search'];
            $helper->getSearchProducts($collection, $params);
            if (!isset($args['sort'])) {
                $collection->setOrder('relevance', 'desc');
            }
        }
        //filter by category
        if ($args && isset($args['filter']['category_id']['eq'])) {
            $category = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')
                ->load($args['filter']['category_id']['eq']);
            $collection = $category->getProductCollection();
        } else if (!$is_search || !$collection) {
            $category = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')
                ->load($this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface')->getStore()->getRootCategoryId());
            $collection = $category->getProductCollection();
        }
        $helper->builderQuery = $collection;

        $collection->addAttributeToSelect('*')->addFinalPrice();
        //filter by graphql attribute filter (excluded search and category)
        if ($args && isset($args['filter'])) {
            foreach ($args['filter'] as $attr=>$value) {
                if ($attr != 'category_id' && $attr != 'q') {
                    $collection->addAttributeToFilter($attr, $value);
                }
            }
        }

        //filter product by simi_filter
        if ($args && isset($args['simiFilter']) && $simiFilter = json_decode($args['simiFilter'], true)) {
            $cat_filtered = false;
            if (isset($simiFilter['cat'])) {
                $simiFilter['category_id'] = $simiFilter['cat'];
                unset($simiFilter['cat']);
            }
            $params['filter']['layer'] = $simiFilter;
            $helper->filterCollectionByAttribute($collection, $params, $cat_filtered);
        }
        //To remove the filtered attribute to get all available filters (including the filtered values)
        $helper->filteredAttributes = [];

        //get simi_filter options
        if ($simiProductFilters = $helper->getLayerNavigator($collection, $params)) {
            $simiFilterOptions = array();
            if (isset($simiProductFilters['layer_filter'])) {
                foreach ($simiProductFilters['layer_filter'] as $layer_filter) {
                    if (isset($layer_filter['filter']) && $count = count($layer_filter['filter'])) {
                        $filtersubOptions = array();
                        foreach ($layer_filter['filter'] as $filtersubOption) {
                            $filtersubOption['value_string'] = (string) $filtersubOption['value'];
                            $filtersubOptions[] = $filtersubOption;
                        }
                        $simiFilterOptions[] = array(
                            'name' => $layer_filter['title'],
                            'filter_items_count' => $count,
                            'request_var' => $layer_filter['attribute'],
                            'filter_items' => $filtersubOptions,
                        );
                    }
                }
            }

            if (count($simiFilterOptions)) {
                $registry = $this->simiObjectManager->get('\Magento\Framework\Registry');
                $registry->register('simiProductFilters', json_encode($simiFilterOptions));
            }
        }

        if (in_array('media_gallery_entries', $attributes)) {
            $collection->addMediaGalleryData();
        }
        if (in_array('options', $attributes)) {
            $collection->addOptionsToResult();
        }

        //simi add pagination + sort
        if (isset($args['currentPage']) && isset($args['pageSize'])) {
            $collection->setPageSize($args['pageSize']);
            $collection->setCurPage($args['currentPage']);
        }
        if (isset($args['sort'])) {
            foreach ($args['sort'] as $atr=>$dir) {
                $collection->setOrder($atr, $dir);
            }
        }


        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($searchResult->getTotalCount());
        return $searchResults;
    }

    /**
     * Create searchResultApplier
     *
     * @param SearchResultInterface $searchResult
     * @param Collection $collection
     * @param array $orders
     * @return SearchResultApplierInterface
     */
    private function getSearchResultsApplier(
        SearchResultInterface $searchResult,
        Collection $collection,
        array $orders
    ): SearchResultApplierInterface {
        return $this->searchResultApplierFactory->create(
            [
                'collection' => $collection,
                'searchResult' => $searchResult,
                'orders' => $orders
            ]
        );
    }

    /**
     * Format sort orders into associative array
     *
     * E.g. ['field1' => 'DESC', 'field2' => 'ASC", ...]
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     */
    private function getSortOrderArray(SearchCriteriaInterface $searchCriteria)
    {
        $ordersArray = [];
        $sortOrders = $searchCriteria->getSortOrders();
        if (is_array($sortOrders)) {
            foreach ($sortOrders as $sortOrder) {
                $ordersArray[$sortOrder->getField()] = $sortOrder->getDirection();
            }
        }

        return $ordersArray;
    }
}

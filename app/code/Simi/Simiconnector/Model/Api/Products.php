<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Products extends Apiabstract
{

    public $layer             = [];
    public $allow_filter_core = false;
    public $helperProduct;
    public $sortOrders        = [];
    public $detail_info;
    public $is_search = 0;

    /*
     * incase the collection doens't containt the full information product
     * need to get product model again on detail calculating
     */
    public $reload_detail_product = false;

    /**
     * override
     */
    public function setBuilderQuery()
    {

        $data                 = $this->getData();
        $parameters           = $data['params'];
        $this->helperProduct = $this->simiObjectManager->get('\Simi\Simiconnector\Helper\Products');
        $this->helperProduct->setData($data);

        if ($data['resourceid']) {
            $this->builderQuery = $this->helperProduct->getProduct($data['resourceid']);
        } else {
            if (isset($parameters[self::FILTER])) {
                $filter = $parameters[self::FILTER];
                if (isset($filter['cat_id'])) {
                    $this->setFilterByCategoryId($filter['cat_id']);
                } elseif (isset($filter['q'])) {
                    $this->is_search = 1;
                    $this->setFilterByQuery();
                } elseif (isset($filter['related_to_id'])) {
                    $this->setFilterByRelated();
                } elseif (isset($filter['spot_product'])) {
                    $this->setFilterBySpot($filter['spot_product']);
                } else {
                    $this->setFilterByCategoryId($this->storeManager->getStore()->getRootCategoryId());
                }
            } else {
                //all products
                $this->setFilterByCategoryId($this->storeManager->getStore()->getRootCategoryId());
            }
        }
    }

    /**
     * @param $info
     * @param $all_ids
     * @param $total
     * @param $page_size
     * @param $from
     * @return array
     * override
     */
    public function getList($info, $all_ids, $total, $page_size, $from)
    {
        return [
            'all_ids'             => $all_ids,
            $this->getPluralKey() => $info,
            'total'               => $total,
            'page_size'           => $page_size,
            'from'                => $from,
            'layers'              => $this->layer,
            'orders'              => $this->sortOrders,
        ];
    }

    /**
     * @return collection
     * override
     */
    public function filter()
    {
        if (!$this->FILTER_RESULT) {
            return;
        }
        $data       = $this->data;
        $parameters = $data['params'];
        if ($this->allow_filter_core) {
            $query = $this->builderQuery;
            $this->_whereFilter($query, $parameters);
        }
        $this->_order($parameters);

        return null;
    }

    /**
     * @return array
     * @throws \Exception
     * override
     */
    public function index()
    {
        $collection = $this->builderQuery;
        if (!$this->is_search)
            $this->filter();
        $data       = $this->getData();
        $parameters = $data['params'];
        $page       = 1;
        if (isset($parameters[self::PAGE]) && $parameters[self::PAGE]) {
            $page = $parameters[self::PAGE];
        }

        $limit = self::DEFAULT_LIMIT;
        if (isset($parameters[self::LIMIT]) && $parameters[self::LIMIT]) {
            $limit = $parameters[self::LIMIT];
        }

        $offset = $limit * ($page - 1);
        if (isset($parameters[self::OFFSET]) && $parameters[self::OFFSET]) {
            $offset = $parameters[self::OFFSET];
        }
        $collection->setPageSize($offset + $limit);

        $all_ids = [];
        $info    = [];
        $total   = $collection->getSize();

        if ($offset > $total) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Invalid method.'), 4);
        }

        $fields = [];
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }

        $check_limit  = 0;
        $check_offset = 0;

        $image_width = isset($parameters['image_width'])?$parameters['image_width']:null;
        $image_height = isset($parameters['image_height'])?$parameters['image_height']:null;

        foreach ($collection as $entity) {
            if (++$check_offset <= $offset) {
                continue;
            }
            if (++$check_limit > $limit) {
                break;
            }
            if ($this->reload_detail_product || $this->is_search) {
                $entity = $this->loadProductWithId($entity->getId());
            }
            $info_detail = $entity->toArray($fields);

            $images       = [];
            if (!$entity->getData('media_gallery'))
                $entity = $this->simiObjectManager
                    ->create('Magento\Catalog\Model\Product')->load($entity->getId());
            $media_gallery = $entity->getMediaGallery();
            foreach ($media_gallery['images'] as $image) {
                if ($image['disabled'] == 0) {
                    $images[] = [
                        'url'      => $this->helperProduct
                            ->getImageProduct($entity, $image['file'], $image_width, $image_height),
                        'position' => $image['position'],
                    ];
                    break;
                }
            }
            if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($images) == 0) {
                $images[]     = [
                    'url'      => $this->helperProduct
                        ->getImageProduct($entity, null, $image_width, $image_height),
                    'position' => 1,
                ];
            }

            $ratings      = $this->simiObjectManager->get('\Simi\Simiconnector\Helper\Review')
                ->getRatingStar($entity->getId());
            $total_rating = $this->simiObjectManager->get('\Simi\Simiconnector\Helper\Review')
                ->getTotalRate($ratings);
            $avg          = $this->simiObjectManager->get('\Simi\Simiconnector\Helper\Review')
                ->getAvgRate($ratings, $total_rating);

            $info_detail['images']        = $images;
            $info_detail['app_prices']    = $this->simiObjectManager->get('\Simi\Simiconnector\Helper\Price')
                ->formatPriceFromProduct($entity);
            $info_detail['app_reviews']      = $this->simiObjectManager
                ->get('\Simi\Simiconnector\Helper\Review')
                ->getProductReviews($entity->getId(), false);
            $info_detail['product_label'] = $this->simiObjectManager->get('\Simi\Simiconnector\Helper\Simiproductlabel')
                ->getProductLabel($entity);
            $info[]                       = $info_detail;

            $all_ids[] = $entity->getId();
        }
        return $this->getList($info, $all_ids, $total, $limit, $offset);
    }

    /**
     * @return array
     * override
     */
    public function show()
    {
        $entity     = $this->builderQuery;
        $data       = $this->getData();
        $parameters = $data['params'];
        $fields     = [];
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }
        $info          = $entity->toArray($fields);
        $media_gallery = $entity->getMediaGallery();
        $images        = [];
        $image_width = isset($parameters['image_width'])?$parameters['image_width']:null;
        $image_height = isset($parameters['image_height'])?$parameters['image_height']:null;

        foreach ($media_gallery['images'] as $image) {
            if ($image['disabled'] == 0) {
                $images[] = [
                    'url'      => $this->helperProduct
                        ->getImageProduct($entity, $image['file'], $image_width, $image_height),
                    'position' => $image['position'],
                ];
            }
        }
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($images) == 0) {
            $images[] = [
                'url'      => $this->helperProduct
                    ->getImageProduct($entity, null, $image_width, $image_height),
                'position' => 1,
            ];
        }

        $registry = $this->simiObjectManager->get('\Magento\Framework\Registry');
        if (!$registry->registry('product') && $entity->getId()) {
            $registry->register('product', $entity);
            $registry->register('current_product', $entity);
        }
        $layout      = $this->simiObjectManager->get('Magento\Framework\View\LayoutInterface');
        $block_att   = $layout->createBlock('Magento\Catalog\Block\Product\View\Attributes');
        $_additional = $block_att->getAdditionalData();

        $ratings      = $this->simiObjectManager
            ->get('\Simi\Simiconnector\Helper\Review')->getRatingStar($entity->getId());
        $total_rating = $this->simiObjectManager
            ->get('\Simi\Simiconnector\Helper\Review')->getTotalRate($ratings);
        $avg          = $this->simiObjectManager
            ->get('\Simi\Simiconnector\Helper\Review')->getAvgRate($ratings, $total_rating);

        $info['additional']       = $_additional;
        $info['images']           = $images;
        $info['app_tier_prices'] =$this->simiObjectManager
            ->get('\Simi\Simiconnector\Helper\Price')->getProductTierPricesLabel($entity);
        $info['app_prices']       = $this->simiObjectManager
            ->get('\Simi\Simiconnector\Helper\Price')->formatPriceFromProduct($entity, true);
        $info['app_options']      = $this->simiObjectManager
            ->get('\Simi\Simiconnector\Helper\Options')->getOptions($entity);
        $info['wishlist_item_id'] = $this->simiObjectManager
            ->get('\Simi\Simiconnector\Helper\Wishlist')->getWishlistItemId($entity);
        $info['product_label']    = $this->simiObjectManager
            ->get('\Simi\Simiconnector\Helper\Simiproductlabel')->getProductLabel($entity);
        $info['app_reviews']      = $this->simiObjectManager
            ->get('\Simi\Simiconnector\Helper\Review')
            ->getProductReviews($entity->getId());
        $info['product_label']    = $this->simiObjectManager
            ->get('\Simi\Simiconnector\Helper\Simiproductlabel')->getProductLabel($entity);
        $info['product_video']    = $this->simiObjectManager
            ->get('\Simi\Simiconnector\Helper\Simivideo')->getProductVideo($entity);
        $this->detail_info        = $this->getDetail($info);
        $this->eventManager->dispatch(
            'simi_simiconnector_model_api_products_show_after',
            ['object' => $this, 'data' => $this->detail_info]
        );
        return $this->detail_info;
    }

    public function setFilterByCategoryId($cat_id)
    {
        $this->helperProduct->setCategoryProducts($cat_id);
        $this->layer       = $this->helperProduct
            ->getLayerNavigator($this->helperProduct->getBuilderQuery());
        $this->builderQuery = $this->helperProduct->getBuilderQuery();
        $this->sortOrders  = $this->helperProduct->getStoreQrders();
    }

    public function setFilterByQuery()
    {
        $this->helperProduct->setLayers(1);
        $this->layer       = $this->helperProduct
            ->getLayerNavigator($this->helperProduct->getBuilderQuery());
        $this->builderQuery = $this->helperProduct->getBuilderQuery();
        $this->sortOrders  = $this->helperProduct->getStoreQrders();
    }

    public function setFilterByRelated()
    {
        $this->helperProduct->setLayers(0);
        $this->layer       = $this->helperProduct
            ->getLayerNavigator($this->helperProduct->getBuilderQuery());
        $this->builderQuery = $this->helperProduct->getBuilderQuery();
        $this->sortOrders  = $this->helperProduct->getStoreQrders();
    }

    public function setFilterByHomeList()
    {
        $this->helperProduct = $this->simiObjectManager->get('\Simi\Simiconnector\Helper\Products');
        $this->helperProduct->setData($this->getData());
        $this->builderQuery = $this->helperProduct->getBuilderQuery();
    }

    public function loadProductWithId($id)
    {
        $categoryModel    = $this->simiObjectManager
            ->create('Magento\Catalog\Model\Product')->load($id);
        return $categoryModel;
    }

    public function setFilterBySpot()
    {
        $data = $this->getData();
        if (!isset($data['params']['filter']['spot_product'])) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('No Spot Type Sent'), 4);
        }
        $type = $data['params']['filter']['spot_product'];
        if ($type == '1') {
            if (!isset($data['params']['filter']['product_ids'])) {
                throw new \Simi\Simiconnector\Helper\SimiException(__('No Product List Sent'), 4);
            }
            $listProduct = str_replace(' ', '', $data['params']['filter']['product_ids']);
            $this->builderQuery = $this->simiObjectManager
                ->create('Simi\Simiconnector\Model\ResourceModel\Productlist\ProductlistCollection')
                ->getProductCollectionByType($type, $this->simiObjectManager, $listProduct);
        } else {
            $this->builderQuery = $this->simiObjectManager
                ->create('Simi\Simiconnector\Model\ResourceModel\Productlist\ProductlistCollection')
                ->getProductCollectionByType($type, $this->simiObjectManager);
        }
    }
}

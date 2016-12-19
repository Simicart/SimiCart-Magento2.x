<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Products extends Apiabstract {

    protected $_layer = array();
    protected $_allow_filter_core = false;
    protected $_helperProduct;
    protected $_sortOrders = array();
    public $detail_info;

    /*
     * incase the collection doens't containt the full information product
     * need to get product model again on detail calculating
     */
    public $reload_detail_product = false;
    
    /**
     * override
     */
    public function setBuilderQuery() {

        $data = $this->getData();
        $parameters = $data['params'];
        $this->_helperProduct = $this->_objectManager->get('\Simi\Simiconnector\Helper\Products');
        $this->_helperProduct->setData($data);

        if ($data['resourceid']) {
            $this->builderQuery = $this->_helperProduct->getProduct($data['resourceid']);
        } else {
            if (isset($parameters[self::FILTER])) {
                $filter = $parameters[self::FILTER];
                if (isset($filter['cat_id'])) {
                    $this->setFilterByCategoryId($filter['cat_id']);
                } elseif (isset($filter['q'])) {
                    $this->setFilterByQuery();
                } elseif (isset($filter['related_to_id'])) {
                    $this->setFilterByRelated($filter['related_to_id']);
                } else {
                    $this->setFilterByCategoryId($this->_storeManager->getStore()->getRootCategoryId());
                    // $this->_allow_filter_core = true;
                }
            } else {
                //all products
                $this->setFilterByCategoryId($this->_storeManager->getStore()->getRootCategoryId());
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
    public function getList($info, $all_ids, $total, $page_size, $from) {
        return array(
            'all_ids' => $all_ids,
            $this->getPluralKey() => $info,
            'total' => $total,
            'page_size' => $page_size,
            'from' => $from,
            'layers' => $this->_layer,
            'orders' => $this->_sortOrders,
        );
    }

    /**
     * @return collection
     * override
     */
    protected function filter() {
        if (!$this->FILTER_RESULT)
            return;
        $data = $this->_data;
        $parameters = $data['params'];
        if ($this->_allow_filter_core) {
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
    public function index() {
        $collection = $this->builderQuery;
        $this->filter();
        $data = $this->getData();
        $parameters = $data['params'];
        $page = 1;
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

        $all_ids = array();
        $info = array();
        $total = $collection->getSize();

        if ($offset > $total)
            throw new \Exception(__('Invalid method.'), 4);

        $fields = array();
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }

        $check_limit = 0;
        $check_offset = 0;

        foreach ($collection as $entity) {
            if (++$check_offset <= $offset) {
                continue;
            }
            if (++$check_limit > $limit)
                break;
            if ($this->reload_detail_product) {
                $entity = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($entity->getId());
            }
            $info_detail = $entity->toArray($fields);

            $images = array();
            $images[] = array(
                'url' => $this->_helperProduct->getImageProduct($entity, null, $parameters['image_width'], $parameters['image_height']),
                'position' => 1,
            );
            $ratings = $this->_objectManager->get('\Simi\Simiconnector\Helper\Review')->getRatingStar($entity->getId());
            $total_rating = $this->_objectManager->get('\Simi\Simiconnector\Helper\Review')->getTotalRate($ratings);
            $avg = $this->_objectManager->get('\Simi\Simiconnector\Helper\Review')->getAvgRate($ratings, $total_rating);

            $info_detail['images'] = $images;
            $info_detail['app_prices'] = $this->_objectManager->get('\Simi\Simiconnector\Helper\Price')->formatPriceFromProduct($entity);
            $info_detail['app_reviews'] = array(
                'rate' => $avg,
                'number' => $ratings[5],
            );
            //hainh $info_detail['product_label'] = $this->_objectManager->get('\Simi\Simiconnector\Helper\Productlabel')->getProductLabel($entity);
            $info[] = $info_detail;

            $all_ids[] = $entity->getId();
        }
        return $this->getList($info, $all_ids, $total, $limit, $offset);
    }

    /**
     * @return array
     * override
     */
    public function show() {
        $entity = $this->builderQuery;
        $data = $this->getData();
        $parameters = $data['params'];
        $fields = array();
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }
        $info = $entity->toArray($fields);
        $media_gallery = $entity->getMediaGallery();
        $images = array();

        foreach ($media_gallery['images'] as $image) {
            // Zend_debug::dump($image['disabled']);
            if ($image['disabled'] == 0) {
                $images[] = array(
                    'url' => $this->_helperProduct->getImageProduct($entity, $image['file'], $parameters['image_width'], $parameters['image_height']),
                    'position' => $image['position'],
                );
            }
        }
        if (count($images) == 0) {
            $images[] = array(
                'url' => $this->_helperProduct->getImageProduct($entity, null, $parameters['image_width'], $parameters['image_height']),
                'position' => 1,
            );
        }


        $registry = $this->_objectManager->get('\Magento\Framework\Registry');
        if (!$registry->registry('product') && $entity->getId()) {
            $registry->register('product', $entity);
        }
        $layout = $this->_objectManager->get('Magento\Framework\View\LayoutInterface');
        $block_att = $layout->createBlock('Magento\Catalog\Block\Product\View\Attributes');
        $_additional = $block_att->getAdditionalData();

        $ratings = $this->_objectManager->get('\Simi\Simiconnector\Helper\Review')->getRatingStar($entity->getId());
        $total_rating = $this->_objectManager->get('\Simi\Simiconnector\Helper\Review')->getTotalRate($ratings);
        $avg = $this->_objectManager->get('\Simi\Simiconnector\Helper\Review')->getAvgRate($ratings, $total_rating);


        $info['additional'] = $_additional;
        $info['images'] = $images;
        $info['app_prices'] = $this->_objectManager->get('\Simi\Simiconnector\Helper\Price')->formatPriceFromProduct($entity, true);
        $info['app_options'] = $this->_objectManager->get('\Simi\Simiconnector\Helper\Options')->getOptions($entity);
        //$info['wishlist_item_id'] = $this->_objectManager->get('\Simi\Simiconnector\Helper\Wishlist')->getWishlistItemId($entity);
        //$info['product_label'] = $this->_objectManager->get('\Simi\Simiconnector\Helper\Productlabel')->getProductLabel($entity);
        $info['app_reviews'] = array(
                'rate' => $avg,
                'number' => $ratings[5],
            );
        $this->detail_info = $this->getDetail($info);
        $this->_eventManager->dispatch('simi_simiconnector_model_api_products_show_after', array('object' => $this, 'data' => $this->detail_info));
        return $this->detail_info;
    }

    public function setFilterByCategoryId($cat_id) {
        $data = $this->getData();
        $this->_helperProduct->setCategoryProducts($cat_id);
        $this->_layer = $this->_helperProduct->getLayerNavigator($this->_helperProduct->getBuilderQuery(), $data['params']);
        $this->builderQuery = $this->_helperProduct->getBuilderQuery();
        $this->_sortOrders = $this->_helperProduct->getStoreQrders();
    }

    public function setFilterByQuery() {
        $data = $this->getData();
        $this->_helperProduct->setLayers(1);
        $this->_layer = $this->_helperProduct->getLayerNavigator($this->_helperProduct->getBuilderQuery(), $data['params']);
        $this->builderQuery = $this->_helperProduct->getBuilderQuery();
        $this->_sortOrders = $this->_helperProduct->getStoreQrders();
    }

    public function setFilterByRelated($product_id) {
        $data = $this->getData();
        $this->_helperProduct->setLayers(0);
        $this->_layer = $this->_helperProduct->getLayerNavigator($this->_helperProduct->getBuilderQuery(), $data['params']);
        $this->builderQuery = $this->_helperProduct->getBuilderQuery();
        $this->_sortOrders = $this->_helperProduct->getStoreQrders();
    }

}

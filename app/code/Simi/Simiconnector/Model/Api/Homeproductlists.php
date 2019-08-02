<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Homeproductlists extends Apiabstract
{

    public $DEFAULT_ORDER     = 'sort_order';
    public $SHOW_PRODUCT_ARRAY = true;

    public function setBuilderQuery()
    {
        $data = $this->getData();
        if ($data['resourceid']) {
            $this->builderQuery = $this->simiObjectManager
                    ->get('Simi\Simiconnector\Model\Productlist')->load($data['resourceid']);
        } else {
            $this->builderQuery = $this->getCollection();
        }
    }

    public function getCollection()
    {
        $typeID          = $this->simiObjectManager
                ->get('Simi\Simiconnector\Helper\Data')->getVisibilityTypeId('productlist');
        $visibilityTable = $this->resource->getTableName('simiconnector_visibility');
        $listCollection  = $this->simiObjectManager
                ->get('Simi\Simiconnector\Model\Productlist')->getCollection()->addFieldToFilter('list_status', '1')
                ->applyAPICollectionFilter($visibilityTable, $typeID, $this->storeManager->getStore()->getId());
        return $listCollection;
    }

    public function show()
    {
        $result                    = parent::show();
        $result['homeproductlist'] = $this->_addInfo($result['homeproductlist']);
        return $result;
    }

    public function index()
    {
        $result = parent::index();
        foreach ($result['homeproductlists'] as $index => $item) {
            $result['homeproductlists'][$index] = $this->_addInfo($item);
        }
        return $result;
    }

    private function _addInfo($dataArray)
    {
        $listHelper = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Productlist');
        $listModel  = $this->simiObjectManager->get('Simi\Simiconnector\Model\Productlist')
                ->load($dataArray['productlist_id']);

        if (!isset($dataArray['list_image_tablet']) || !$dataArray['list_image_tablet']) {
            $dataArray['list_image_tablet'] = $dataArray['list_image'];
        }
        try {
            $imagesize               = getimagesize(BP . '/pub/media/' . $dataArray['list_image']);
            $dataArray['width']      = $imagesize[0];
            $dataArray['height']     = $imagesize[1];
            $dataArray['list_image'] = $this->getMediaUrl($dataArray['list_image']);

            $imagesize                      = getimagesize(BP . '/pub/media/' . $dataArray['list_image_tablet']);
            $dataArray['width_tablet']      = $imagesize[0];
            $dataArray['height_tablet']     = $imagesize[1];
            $dataArray['list_image_tablet'] = $this->getMediaUrl($dataArray['list_image_tablet']);
        } catch (\Exception $e) {
            $item['function_warning'] = true;
        }
        $typeArray              = $listHelper->getListTypeId();
        $listTypeIndex          = $listModel->getData('list_type');
        $dataArray['type_name'] = isset($typeArray[$listTypeIndex])?$typeArray[$listTypeIndex]:$typeArray[6];
        $fields = array(
            'entity_id','entity_id','entity_type_id','attribute_set_id','type_id','sku','name','created_at',
            'updated_at','has_options','required_options','cat_index_position','price','tax_class_id','final_price',
            'description','short_description','is_salable','url_key,url_path'
        );
        if ($this->SHOW_PRODUCT_ARRAY) {
            $productCollection                          = $listHelper->getProductCollection($listModel);
            $productListAPIModel = $this->simiObjectManager->get('Simi\Simiconnector\Model\Api\Products');
            $productListAPIModel->setData($this->getData());
            $productListAPIModelData                    = $this->getData();
            $productListAPIModelData['resourceid']      = null;

            if (isset($productListAPIModelData['params']) && is_object($productListAPIModelData['params']))
                $productListAPIModelData['params']->fields = implode(',', $fields);
            else if (isset($productListAPIModelData['params']) && is_array($productListAPIModelData['params']))
                $productListAPIModelData['params']['fields'] = implode(',', $fields);
            else
                $productListAPIModelData['params']          = array('fields'=> implode(',', $fields));

            $productListAPIModel->setData($productListAPIModelData);
            $productListAPIModel->reload_detail_product = true;
            $productListAPIModel->setFilterByHomeList();
            $productListAPIModel->FILTER_RESULT         = false;
            $productListAPIModel->builderQuery          = $productCollection;
            $productListAPIModel->pluralKey             = 'products';
            $listAPI                                    = $productListAPIModel->index();
            $dataArray['product_array']                 = $listAPI;
        }
        return $dataArray;
    }
}

<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Homebanners extends Apiabstract
{

    public $DEFAULT_ORDER = 'sort_order';

    public function setBuilderQuery()
    {
        $data = $this->getData();
        if ($data['resourceid']) {
            $this->builderQuery = $this->simiObjectManager
                    ->get('Simi\Simiconnector\Model\Cms')->load($data['resourceid']);
        } else {
            $this->builderQuery = $this->getCollection();
        }
    }

    public function getCollection()
    {
        $typeID           = $this->simiObjectManager
                ->get('Simi\Simiconnector\Helper\Data')->getVisibilityTypeId('banner');
        $visibilityTable  = $this->resource->getTableName('simiconnector_visibility');
        $bannerCollection = $this->simiObjectManager
                ->get('Simi\Simiconnector\Model\Banner')->getCollection()->addFieldToFilter('status', '1')
                ->applyAPICollectionFilter($visibilityTable, $typeID, $this->storeManager
                        ->getStore()->getId());
        $this->builderQuery = $bannerCollection;
        return $bannerCollection;
    }

    public function index()
    {
        $result = parent::index();
        foreach ($result['homebanners'] as $index => $item) {
            if (!$item['banner_name_tablet']) {
                $item['banner_name_tablet'] = $item['banner_name'];
            }
            try {
                if ($item['banner_name']) {
                    $imagesize           = getimagesize(BP . '/pub/media/' . $item['banner_name']);
                    $item['width']       = $imagesize[0];
                    $item['height']      = $imagesize[1];
                    $item['banner_name'] = $this->getMediaUrl($item['banner_name']);
                }
                if ($item['banner_name_tablet']) {
                    $imagesize                  = getimagesize(BP . '/pub/media/' . $item['banner_name_tablet']);
                    $item['width_tablet']       = $imagesize[0];
                    $item['height_tablet']      = $imagesize[1];
                    $item['banner_name_tablet'] = $this->getMediaUrl($item['banner_name_tablet']);
                }
            } catch (\Exception $e) {
                $item['function_warning'] = true;
            }

            if ($item['type'] == 2) {
                $categoryModel        = $this->loadCategoryWithId($item['category_id']);
                $item['has_children'] = $categoryModel->hasChildren();
                $item['cat_name']     = $categoryModel->getName();
            }

            $result['homebanners'][$index] = $item;
        }
        return $result;
    }
    
    public function loadCategoryWithId($id)
    {
        $categoryModel    = $this->simiObjectManager
                ->create('\Magento\Catalog\Model\Category')->load($id);
        return $categoryModel;
    }
}

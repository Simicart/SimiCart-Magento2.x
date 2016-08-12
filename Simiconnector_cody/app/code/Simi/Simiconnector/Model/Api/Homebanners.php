<?php
/**
 * Copyright © 2015 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

/**
 * Codymodeltab codymodel model
 */
class Homebanners extends Apiabstract
{
    protected $_DEFAULT_ORDER = 'sort_order';

    public function setBuilderQuery() {
        $data = $this->getData();
        if ($data['resourceid']) {
            $this->builderQuery = $this->_objectManager->get('Simi\Simiconnector\Model\Cms')->load($data['resourceid']);
        } else {
            $this->builderQuery = $this->getCollection();
        }
    }

    public function getCollection() {
        $typeID = $this->_objectManager->get('Simi\Simiconnector\Helper\Data')->getVisibilityTypeId('banner');
        $visibilityTable = $this->_resource->getTableName('simiconnector_visibility');
        $bannerCollection = $this->_objectManager->get('Simi\Simiconnector\Model\Banner')->getCollection();
        $bannerCollection->getSelect()
                ->join(array('visibility' => $visibilityTable), 'visibility.item_id = main_table.banner_id AND visibility.content_type = ' . $typeID . ' AND visibility.store_view_id =' . $this->_storeManager->getStore()->getId());

        $this->builderQuery = $bannerCollection;
        return $bannerCollection;
    }

    public function index() {
        $result = parent::index();
        foreach ($result['homebanners'] as $index => $item) {
            
            if ($item['banner_name']) {
                $imagesize = @getimagesize(BP.'/pub/media/'.$item['banner_name']);
                $item['width'] = $imagesize[0];
                $item['height'] = $imagesize[1];
                $item['banner_name'] = $this->getMediaUrl($item['banner_name']);
            }
                
            if ($item['banner_name_tablet']) {
                $imagesize = @getimagesize(BP.'/pub/media/'.$item['banner_name_tablet']);
                $item['width_tablet'] = $imagesize[0];
                $item['height_tablet'] = $imagesize[1];
                $item['banner_name_tablet'] = $this->getMediaUrl($item['banner_name_tablet']);
            }
            
            if ($item['type'] == 2) {
                $categoryModel = $this->_objectManager->create('\Magento\Catalog\Model\Category')->load($item['category_id']);
                $item['has_children'] = $categoryModel->hasChildren();
                $item['cat_name'] = $categoryModel->getName();
            }
            
            $result['homebanners'][$index] = $item;
        }
        return $result;
    }

}

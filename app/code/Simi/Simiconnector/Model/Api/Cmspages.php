<?php
/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;


class Cmspages extends Apiabstract
{
    protected $_DEFAULT_ORDER = 'sort_order';
    

    public function setBuilderQuery() {
        $data = $this->getData();
        if ($data['resourceid']) {
            $this->builderQuery = $this->_objectManager->get('Simi\Simiconnector\Model\Cms')->load($data['resourceid']);
        } else {
            $typeID = $this->_objectManager->get('Simi\Simiconnector\Helper\Data')->getVisibilityTypeId('cms');
            $visibilityTable = $this->_resource->getTableName('simiconnector_visibility');
            $cmsCollection = $this->_objectManager->get('Simi\Simiconnector\Model\Cms')->getCollection()->addFieldToFilter('type','1');
            $cmsCollection->getSelect()
                    ->join(array('visibility' => $visibilityTable), 'visibility.item_id = main_table.cms_id AND visibility.content_type = '.$typeID.' AND visibility.store_view_id =' . $this->_storeManager->getStore()->getId());
            $this->builderQuery = $cmsCollection;
        }
    }

    
    public function index() {
        $result = parent::index();
        foreach ($result['cmspages'] as $index => $store) {
            $result['cmspages'][$index]['cms_image'] = $this->getMediaUrl($result['cmspages'][$index]['cms_image']);
        }
        return $result;
    }

}

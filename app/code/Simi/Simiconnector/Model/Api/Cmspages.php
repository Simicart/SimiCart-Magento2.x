<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Cmspages extends Apiabstract
{

    public $DEFAULT_ORDER = 'sort_order';

    public function setBuilderQuery()
    {
        $data = $this->getData();
        if ($data['resourceid']) {
            $this->builderQuery = $this->simiObjectManager
                    ->get('Simi\Simiconnector\Model\Cms')->load($data['resourceid']);
        } else {
            $typeID             = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')
                    ->getVisibilityTypeId('cms');
            $visibilityTable    = $this->resource->getTableName('simiconnector_visibility');
            $cmsCollection      = $this->simiObjectManager->get('Simi\Simiconnector\Model\Cms')
                    ->getCollection()->addFieldToFilter('type', '1')
                    ->applyAPICollectionFilter($visibilityTable, $typeID, $this->storeManager
                        ->getStore()->getId());
            $this->builderQuery = $cmsCollection;
        }
    }

    public function index()
    {
        $result = parent::index();
        foreach ($result['cmspages'] as $index => $store) {
            $result['cmspages'][$index]['cms_image'] = $this->getMediaUrl($result['cmspages'][$index]['cms_image']);
        }
        return $result;
    }
}

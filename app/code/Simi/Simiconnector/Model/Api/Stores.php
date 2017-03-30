<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Stores extends Apiabstract
{

    public $DEFAULT_ORDER = 'group_id';

    public function setBuilderQuery()
    {
        $data       = $this->getData();
        $collection = $this->simiObjectManager->get('\Magento\Store\Model\Group')->getCollection();
        if ($data['resourceid']) {
            $this->builderQuery = $this->simiObjectManager
                    ->get('\Magento\Store\Model\Group')->load($data['resourceid']);
        } else {
            $this->builderQuery = $collection
                    ->addFieldToFilter('website_id', $this->storeManager->getStore()->getWebsiteId());
        }
    }

    public function index()
    {
        $result = parent::index();
        foreach ($result['stores'] as $index => $store) {
            $storeViewAPIModel                      = $this->simiObjectManager
                    ->get('\Simi\Simiconnector\Model\Api\Storeviews');
            $storeViewAPIModel->setData($this->getData());
            $storeViewAPIModel->builderQuery        = $this->simiObjectManager
                    ->get('\Magento\Store\Model\Store')
                    ->getCollection()->addFieldToFilter('group_id', $store['group_id']);
            $storeViewAPIModel->pluralKey           = 'storeviews';
            $result['stores'][$index]['storeviews'] = $storeViewAPIModel->index();
        }
        return $result;
    }
}

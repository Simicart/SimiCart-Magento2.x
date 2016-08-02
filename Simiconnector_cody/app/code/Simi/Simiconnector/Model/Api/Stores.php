<?php
/**
 * Copyright © 2015 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

/**
 * Codymodeltab codymodel model
 */
class Stores extends Apiabstract
{
    protected $_DEFAULT_ORDER = 'group_id';

    public function setBuilderQuery() {
        
        \zend_debug::dump($this->storeManager->getStore()->getData());die;
        $collection = $this->_objectManager->create('\Magento\Store\Model\Group')->getCollection();
        $data = $this->getData();
        if ($data['resourceid']) {
            $this->builderQuery = Mage::getModel('core/store_group')->load($data['resourceid']);
        } else {
            $this->builderQuery = $collection;
        }
    }

    /*
    public function index() {
        $result = parent::index();
        foreach ($result['stores'] as $index => $store) {
            $storeViewAPIModel = Mage::getModel('simiconnector/api_storeviews');
            $storeViewAPIModel->setData($this->getData());
            $storeViewAPIModel->builderQuery = Mage::getModel('core/store')->getCollection()->addFieldToFilter('group_id', $store['group_id']);
            $storeViewAPIModel->pluralKey = 'storeviews';
            $result['stores'][$index]['storeviews'] = $storeViewAPIModel->index();
        }
        return $result;
    }
     */

}

<?php
/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;


class Categories extends Apiabstract
{
    protected $_DEFAULT_ORDER = 'position';
    protected $_visible_array;
    

    public function setBuilderQuery() {
        $data = $this->getData();
        if (!$data['resourceid']) {
            $data['resourceid'] = $this->_storeManager->getStore()->getRootCategoryId();
        }
        if ($this->getStoreConfig('simiconnector/general/categories_in_app'))
            $this->_visible_array = explode(',', $this->getStoreConfig('simiconnector/general/categories_in_app'));
        
        
        $category = $this->_objectManager->create('\Magento\Catalog\Model\Category')->load($data['resourceid']);
        if (is_array($category->getChildrenCategories())) {
            $childArray = $category->getChildrenCategories();
            $idArray = array();
            foreach ($childArray as $childArrayItem) {
                $idArray[] = $childArrayItem->getId();
            }
            if ($this->_visible_array)
                $idArray = array_intersect($idArray, $this->_visible_array);
            $this->builderQuery = $this->_objectManager->create('\Magento\Catalog\Model\Category')->getCollection()->addAttributeToSelect('*')->addFieldToFilter('entity_id', array('in' => $idArray));
        }
        else {
            $this->builderQuery = $category->getChildrenCategories()->addAttributeToSelect('*');
            if ($this->_visible_array)
                $this->builderQuery->addFieldToFilter('entity_id', array('in' => $this->_visible_array));
        }

    }

    public function index() {
        $data = $this->getData();
        $result = parent::index();
        foreach ($result['categories'] as $index => $catData) {
            $childCollection = $this->_objectManager->create('\Magento\Catalog\Model\Category')->getCollection()->addFieldToFilter('parent_id', $catData['entity_id']);
            if ($this->_visible_array)
                $childCollection->addFieldToFilter('entity_id', array('nin' => $this->_visible_array));
            if ($childCollection->count() > 0)
                $result['categories'][$index]['has_children'] = TRUE;
            else
                $result['categories'][$index]['has_children'] = FALSE;
        }
        return $result;
    }

    public function show() {
        return $this->index();
    }
}

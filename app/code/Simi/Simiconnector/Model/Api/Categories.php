<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Categories extends Apiabstract
{

    public $DEFAULT_ORDER = 'position';
    public $visible_array;

    public function setBuilderQuery()
    {
        $data = $this->getData();
        if (!$data['resourceid']) {
            $data['resourceid'] = $this->storeManager->getStore()->getRootCategoryId();
        }
        if ($this->getStoreConfig('simiconnector/general/categories_in_app')) {
            $this->visible_array = explode(',', $this->getStoreConfig('simiconnector/general/categories_in_app'));
        }

        $category = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')->load($data['resourceid']);
        if (is_array($category->getChildrenCategories())) {
            $childArray = $category->getChildrenCategories();
            $idArray    = [];
            foreach ($childArray as $childArrayItem) {
                $idArray[] = $childArrayItem->getId();
            }
            if ($this->visible_array) {
                $idArray = array_intersect($idArray, $this->visible_array);
            }
            $this->builderQuery = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')
                ->getCollection()->addAttributeToSelect('*')->addFieldToFilter('entity_id', ['nin' => $idArray]);
        } else {
            $this->builderQuery = $category->getChildrenCategories()->addAttributeToSelect('*');
            if ($this->visible_array) {
                $this->builderQuery->addFieldToFilter('entity_id', ['nin' => $this->visible_array]);
            }
        }
    }

    public function index()
    {
        $result = parent::index();
        foreach ($result['categories'] as $index => $catData) {
            $categoryModel = $this->simiObjectManager
                ->create('\Magento\Catalog\Model\Category')
                ->load($catData['entity_id']);
            $catData = array_merge($catData, $categoryModel->getData());
            if (isset($catData['request_path'])) {
                $catData['url_path'] = $catData['request_path'];
            }
            if ($image_url = $categoryModel->getImageUrl()) {
                $catData['image_url'] = $image_url;
            }
            if (isset($catData['landing_page']) && $catData['landing_page']) {
                $block = $this->simiObjectManager->get('Magento\Framework\View\LayoutInterface')
                    ->createBlock('Magento\Cms\Block\Block');
                $block->setBlockId($catData['landing_page']);
                $catData['landing_page_cms'] = $block->toHtml();
            }

            if ($categoryModel->getData('description'))
                $catData['description'] = $this->simiObjectManager
                    ->get('Magento\Cms\Model\Template\FilterProvider')
                    ->getPageFilter()->filter($categoryModel->getData('description'));

            $childCollection = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')
                ->getCollection()->addFieldToFilter('parent_id', $catData['entity_id']);
            if ($this->visible_array) {
                $childCollection->addFieldToFilter('entity_id', ['nin' => $this->visible_array]);
            }
            if ($this->simiObjectManager
                    ->get('Simi\Simiconnector\Helper\Data')->countCollection($childCollection) > 0) {
                $catData['has_children'] = true;
            } else {
                $catData['has_children'] = false;
            }
            $result['categories'][$index] = $catData;
        }
        return $result;
    }

    public function show()
    {
        return $this->index();
    }
}

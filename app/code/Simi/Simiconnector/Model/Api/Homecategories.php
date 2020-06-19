<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Homecategories extends Apiabstract
{

    public $DEFAULT_ORDER = 'sort_order';
    const DEFAULT_LIMIT = 9999;

    public $visible_array;

    public function setSingularKey($singularKey)
    {
        if ($singularKey != 'Homecategory') {
            $this->singularKey = 'Homecategory';
        }
        return $this;
    }

    public function setBuilderQuery()
    {
        if ($this->getStoreConfig('simiconnector/general/categories_in_app')) {
            $this->visible_array = explode(',', $this->getStoreConfig('simiconnector/general/categories_in_app'));
        }
        $data = $this->getData();
        if ($data['resourceid']) {
            $this->builderQuery = $this->simiObjectManager
                    ->get('Simi\Simiconnector\Model\Simicategory')->load($data['resourceid']);
        } else {
            $this->builderQuery = $this->getCollection();
        }
    }

    public function getCollection()
    {
        $typeID                 = $this->simiObjectManager
                ->get('Simi\Simiconnector\Helper\Data')->getVisibilityTypeId('homecategory');
        $visibilityTable        = $this->resource->getTableName('simiconnector_visibility');
        $simicategoryCollection = $this->simiObjectManager
                ->get('Simi\Simiconnector\Model\Simicategory')->getCollection()->addFieldToFilter('status', '1')
                ->applyAPICollectionFilter($visibilityTable, $typeID, $this->storeManager
                        ->getStore()->getId());
        $this->builderQuery     = $simicategoryCollection;
        return $simicategoryCollection;
    }

    public function index()
    {
        $result = parent::index();
        $data   = $this->getData();

        foreach ($result['homecategories'] as $index => $item) {
            if (!$item['simicategory_filename_tablet']) {
                $item['simicategory_filename_tablet'] = $item['simicategory_filename'];
            }
            try {
                $imagesize                     = getimagesize(BP . '/pub/media/' . $item['simicategory_filename']);
                $item['width']                 = $imagesize[0];
                $item['height']                = $imagesize[1];
                $item['simicategory_filename'] = $this->getMediaUrl($item['simicategory_filename']);

                if ($item['simicategory_filename_tablet']) {
                    $imagesize                            = getimagesize(BP . '/pub/media/' .
                            $item['simicategory_filename_tablet']);
                    $item['width_tablet']                 = $imagesize[0];
                    $item['height_tablet']                = $imagesize[1];
                    $item['simicategory_filename_tablet'] = $this->getMediaUrl($item['simicategory_filename_tablet']);
                }
            } catch (\Exception $e) {
                $item['function_warning'] = true;
            }
            $categoryModel    = $this->loadCategoryWithId($item['category_id']);
            $item['url_path'] = $categoryModel->getUrlPath();
            $item['cat_name'] = $categoryModel->getName();
            $childCollection  = $this->getVisibleChildren($item['category_id']);
            if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countCollection($childCollection)) {
                $item['has_children'] = true;
                if (isset($data['params']['get_child_cat']) && $data['params']['get_child_cat']) {
                    $childArray = [];
                    foreach ($childCollection as $childCat) {
                        $childInfo            = $childCat->toArray();
                        $grandchildCollection = $this->getVisibleChildren($childCat->getId());
                        if ($this->simiObjectManager
                                ->get('Simi\Simiconnector\Helper\Data')->countCollection($grandchildCollection) > 0) {
                            $childInfo['has_children'] = true;
                        } else {
                            $childInfo['has_children'] = false;
                        }
                        $childArray[] = $childInfo;
                    }
                    $item['children'] = $childArray;
                }
            } else {
                $item['has_children'] = false;
            }
            $result['homecategories'][$index] = $item;
        }
        return $result;
    }

    public function getDefaultLimit() {
        return self::DEFAULT_LIMIT;
    }

    public function loadCategoryWithId($id)
    {
        $categoryModel    = $this->simiObjectManager
                ->create('\Magento\Catalog\Model\Category')->load($id);
        return $categoryModel;
    }

    /*
     * @param Cat ID
     * Return Child Cat collection
     */

    public function getVisibleChildren($catId)
    {
        $category = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')->load($catId);
        if (is_array($category->getChildrenCategories())) {
            $childArray = $category->getChildrenCategories();
            $idArray    = [];
            foreach ($childArray as $childArrayItem) {
                $idArray[] = $childArrayItem->getId();
            }
            return $this->simiObjectManager
                    ->create('\Magento\Catalog\Model\Category')->getCollection()
                    ->addAttributeToSelect('*')->addFieldToFilter('entity_id', ['in' => $idArray]);
        }

        return $category->getChildrenCategories()->addAttributeToSelect('*');
    }
}

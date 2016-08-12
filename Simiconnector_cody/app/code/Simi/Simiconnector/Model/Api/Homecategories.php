<?php
/**
 * Copyright © 2015 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

/**
 * Codymodeltab codymodel model
 */
class Homecategories extends Apiabstract
{
    protected $_DEFAULT_ORDER = 'sort_order';
    protected $_visible_array;

    public function setSingularKey($singularKey) {
        $this->singularKey = 'Homecategory';
        return $this;
    }
    
    public function setBuilderQuery() {
        if ($this->getStoreConfig('simiconnector/general/categories_in_app'))
            $this->_visible_array = explode(',', Mage::getStoreConfig('simiconnector/general/categories_in_app'));
        $data = $this->getData();
        if ($data['resourceid']) {
            $this->builderQuery = $this->_objectManager->get('Simi\Simiconnector\Model\Simicategory')->load($data['resourceid']);
        } else {
            $this->builderQuery = $this->getCollection();
        }
    }

    public function getCollection() {
        $typeID = $this->_objectManager->get('Simi\Simiconnector\Helper\Data')->getVisibilityTypeId('homecategory');
        $visibilityTable = $this->_resource->getTableName('simiconnector_visibility');
        $simicategoryCollection = $this->_objectManager->get('Simi\Simiconnector\Model\Simicategory')->getCollection();

        $simicategoryCollection->getSelect()
                ->join(array('visibility' => $visibilityTable), 'visibility.item_id = main_table.simicategory_id AND visibility.content_type = ' . $typeID . ' AND visibility.store_view_id =' . $this->_storeManager->getStore()->getId());
        $this->builderQuery = $simicategoryCollection;
        return $simicategoryCollection;
    }
      

    
    public function index() {
        $result = parent::index();
        $data = $this->getData();

        foreach ($result['homecategories'] as $index => $item) {
            $imagesize = @getimagesize(BP.'/pub/media/'.$item['simicategory_filename']);
            $item['width'] = $imagesize[0];
            $item['height'] = $imagesize[1];
            if ($item['simicategory_filename_tablet']) {
                $imagesize = @getimagesize(BP.'/pub/media/'.$item['simicategory_filename_tablet']);
                $item['width_tablet'] = $imagesize[0];
                $item['height_tablet'] = $imagesize[1];
            }
            $categoryModel = $this->_objectManager->create('\Magento\Catalog\Model\Category')->load($item['category_id']);
            $item['cat_name'] = $categoryModel->getName();
            $childCollection = $this->getVisibleChildren($item['category_id']);
            if ($childCollection->count() > 0) {
                $item['has_children'] = TRUE;
                if ($data['params']['get_child_cat']) {
                    $childArray = array();
                    foreach ($childCollection as $childCat) {
                        $childInfo = $childCat->toArray();
                        $grandchildCollection = $this->getVisibleChildren($childCat->getId());
                        if ($grandchildCollection->count() > 0)
                            $childInfo['has_children'] = TRUE;
                        else
                            $childInfo['has_children'] = FALSE;
                        $childArray[] = $childInfo;
                    }
                    $item['children'] = $childArray;
                }
            } else {
                $item['has_children'] = FALSE;
            }
            $result['homecategories'][$index] = $item;
        }
        return $result;
    }

    /*
     * @param Cat ID
     * Return Child Cat collection
     */

    public function getVisibleChildren($catId) {
        $childCollection = $this->_objectManager->create('\Magento\Catalog\Model\Category')->getCollection()->addAttributeToSelect('*')->addFieldToFilter('parent_id', $catId);
        if ($this->_visible_array)
            $childCollection->addFieldToFilter('entity_id', array('nin' => $this->_visible_array));
        return $childCollection;
    }

}

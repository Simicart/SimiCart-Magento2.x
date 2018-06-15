<?php

namespace Simi\Simiconnector\Model\Api;

class Categorytrees extends Apiabstract
{

    public $DEFAULT_ORDER = 'position';
    public $visible_array;
    public $_result = [];
    public $_rootlevel = 0;

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
        $this->_result = [];
        $this->_rootlevel = $category->getData('level');
        $this->getChildCatArray($category->getData('level'), $this->_result, $category->getData('entity_id'));
    }

    public function index()
    {
        return ['categorytrees'=>$this->_result];
    }

    public function show()
    {
        return $this->index();
    }

    public $categoryArray;
    public function getChildCatArray($level = 0, &$optionArray = [], $parent_id = 0)
    {
        if (!$this->categoryArray) {
            if ($this->visible_array) {
                $this->categoryArray = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')
                    ->getCollection()
                    ->addFieldToFilter('entity_id', ['in' => $this->visible_array])
                    ->addAttributeToSelect('*')
                    ->setOrder('position', 'asc')
                    ->toArray();
            } else {
                $this->categoryArray = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->setOrder('position', 'asc')
                    ->toArray();
            }
        }
        $beforeString = '';
        for ($i=0; $i< $level; $i++) {
            $beforeString .= '  --  ';
        }
        $level+=1;
        foreach ($this->categoryArray as $category) {
            if ($category['level'] != $level) {
                continue;
            }
            if (($parent_id == 0) || (($parent_id!=0) && ($category['parent_id'] == $parent_id))) {
                $categoryModel = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')->load($category['entity_id']);
                $category = array_merge($category, $categoryModel->getData());
                $category['url_path'] = isset($category['request_path'])?$category['request_path']:$category['url_path'];
                if ($image_url = $categoryModel->getImageUrl()) {
                    $category['image_url'] = $image_url;
                }
                if (isset($category['landing_page']) && $category['landing_page']) {
                    $block = $this->simiObjectManager->get('Magento\Framework\View\LayoutInterface')
                        ->createBlock('Magento\Cms\Block\Block');
                    $block->setBlockId($category['landing_page']);
                    $category['landing_page_cms'] = $block->toHtml();
                }
                if ($categoryModel->getData('description'))
                    $category['description'] = $this->simiObjectManager
                        ->get('Magento\Cms\Model\Template\FilterProvider')
                        ->getPageFilter()->filter($categoryModel->getData('description'));
                
                $this->getChildCatArray($level, $category['child_cats'], $category['entity_id']);
                $optionArray[] = $category;
            }
        }
        return $optionArray;
    }
}

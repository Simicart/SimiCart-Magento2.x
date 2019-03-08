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
            $categoryCollection = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->setOrder('position', 'asc');
            if ($this->visible_array) {
                $categoryCollection
                    ->addFieldToFilter('entity_id', ['nin' => $this->visible_array]);
            }
            if ($this->getStoreConfig('simiconnector/general/filter_categories_by_include_in_menu')) {
                $categoryCollection->addAttributeToFilter('include_in_menu', 1);
            }
            $this->categoryArray = $categoryCollection->getData();
        }
        $beforeString = '';
        for ($i=0; $i< $level; $i++) {
            $beforeString .= '  --  ';
        }
        $level+=1;
        foreach ($this->categoryArray as $category) {
            if (isset($category['level']) && ($category['level'] != $level)) {
                continue;
            }
            if (($parent_id == 0) ||
                (($parent_id!=0) && isset($category['parent_id']) &&  ($category['parent_id']== $parent_id))) {
                $categoryModel = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')->load($category['entity_id']);
                $category = array_merge($category, $categoryModel->getData());
                $category['url_path'] = isset($category['request_path'])?$category['request_path']:$category['url_path'];      
                if (strpos($category['url_path'], '.html') === false) {
                    $category['url_path'] = $category['url_path'].'.html';
                }
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
                
                unset($category['all_children']);
                unset($category['attribute_set_id']);
                unset($category['available_sort_by']);
                unset($category['breadcrumbs_priority']);
                unset($category['children']);
                unset($category['custom_design_from']);
                unset($category['custom_design_to']);
                unset($category['redirect_priority']);
                unset($category['updated_at']);
                unset($category['created_at']);
                unset($category['in_html_sitemap']);
                unset($category['include_in_menu']);
                unset($category['is_anchor']);
                unset($category['sizechart']);
                unset($category['use_in_crosslinking']);
                unset($category['whatsapp_category']);
                unset($category['position']);
                unset($category['path_in_store']);
                unset($category['default_sort_by']);
                unset($category['custom_layout_update']);
                unset($category['custom_use_parent_settings']);
                unset($category['custom_apply_to_products']);
                unset($category['custom_design']);
                unset($category['filter_price_range']);
                $this->getChildCatArray($level, $category['child_cats'], $category['entity_id']);
                $optionArray[] = $category;
            }
        }
        return $optionArray;
    }
}

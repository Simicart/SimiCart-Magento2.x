<?php

namespace Simi\Simiconnector\Helper;

class Catetree extends Data
{
    public $categoryArray;

    public function getChildCatArray($level = 0, &$optionArray = [], $parent_id = 0)
    {
        if (!$this->categoryArray) {
            $productMetadata = $this->simiObjectManager->get('Magento\Framework\App\ProductMetadataInterface');
            if (strpos($productMetadata->getVersion(), '2.0') === 0) {
                $this->categoryArray = [];
                foreach ($this->simiObjectManager->create('\Magento\Catalog\Model\Category')
                             ->getCollection()->addAttributeToSelect('name') as $categoryModel) {
                    $this->categoryArray[] = $categoryModel->toArray();
                }
            } else {
                $this->categoryArray = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')
                    ->getCollection()->addAttributeToSelect('name')->toArray();
            }
        }
        $beforeString = '';
        for ($i = 0; $i < $level; $i++) {
            $beforeString .= '  --  ';
        }
        $level += 1;
        foreach ($this->categoryArray as $category) {
            if (!isset($category['level']) || ($category['level'] != $level) || !isset($category['name'])) {
                continue;
            }
            if (($parent_id == 0) || (($parent_id != 0) && isset($category['parent_id']) && ($category['parent_id'] == $parent_id))) {
                $optionArray[] = ['value' => $category['entity_id'], 'label' => $beforeString . $category['name']];
                $this->getChildCatArray($level, $optionArray, $category['entity_id']);
            }
        }
        return $optionArray;
    }
}
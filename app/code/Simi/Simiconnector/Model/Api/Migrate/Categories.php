<?php

namespace Simi\Simiconnector\Model\Api\Migrate;

class Categories extends Apiabstract
{
    public function setBuilderQuery()
    {
        $this->builderQuery = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')
            ->getCollection()
            ->addAttributeToSelect('url_path')
            ->addAttributeToSelect('name');
    }

    public function index()
    {
        $result = parent::index();
        $storeIds = $this->getAllStoreIds();
        foreach ($result['migrate_categories'] as $index => $category) {
            $nameArray = [];
            foreach ($storeIds as $storeId) {
                $nameByStore = $this->loadCategory($category['entity_id'], $storeId)->getName();
                if ($nameByStore) {
                    $nameArray[] = [$storeId, $nameByStore];
                }
            }
            if ($this->simiObjectManager
                ->get('Simi\Simiconnector\Helper\Data')->countArray($nameArray)) {
                $result['migrate_categories'][$index]['json_name'] = json_encode($nameArray);
            }
        }
        return $result;
    }

    public function loadCategory($id, $storeId)
    {
        return $this->simiObjectManager->create('\Magento\Catalog\Model\Category')
            ->setStoreId($storeId)->load($id);
    }

    public function getAllStoreIds()
    {
        return $this->simiObjectManager->get('\Magento\Store\Model\Store')->getCollection()->getAllIds();
    }
}

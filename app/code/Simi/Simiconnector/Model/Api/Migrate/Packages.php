<?php

namespace Simi\Simiconnector\Model\Api\Migrate;

class Packages extends Apiabstract
{
    public function setBuilderQuery()
    {
        $data = $this->getData();
        if (!$data['resourceid']) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('No Package Sent'), 4);
        } else {
            if ($data['resourceid'] == 'all') {
                return;
            }
            throw new \Simi\Simiconnector\Helper\SimiException(__('Package Invalid'), 4);
        }
    }

    public function show()
    {
        $result = [];

        $storeModel = $this->simiObjectManager->create('Simi\Simiconnector\Model\Api\Migrate\Stores');
        $storeModel->setBuilderQuery();
        $storeModel->pluralKey = 'migrate_stores';
        $result['stores'] = $storeModel->index();

        $storeviewModel = $this->simiObjectManager->create('Simi\Simiconnector\Model\Api\Migrate\Storeviews');
        $storeviewModel->setBuilderQuery();
        $storeviewModel->pluralKey = 'migrate_storeviews';
        $result['storeviews'] = $storeviewModel->index();

        $productModel = $this->simiObjectManager->create('Simi\Simiconnector\Model\Api\Migrate\Products');
        $productModel->setBuilderQuery();
        $productModel->pluralKey = 'migrate_products';
        $result['products'] = $productModel->index();

        $categoryModel = $this->simiObjectManager->create('Simi\Simiconnector\Model\Api\Migrate\Categories');
        $categoryModel->setBuilderQuery();
        $categoryModel->pluralKey = 'migrate_categories';
        $result['categories'] = $categoryModel->index();

        $customerModel = $this->simiObjectManager->create('Simi\Simiconnector\Model\Api\Migrate\Customers');
        $customerModel->setBuilderQuery();
        $customerModel->pluralKey = 'migrate_customers';
        $result['customers'] = $customerModel->index();

        return ['migrate_package'=>$result];
    }
}

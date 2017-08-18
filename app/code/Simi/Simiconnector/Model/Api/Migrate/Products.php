<?php

namespace Simi\Simiconnector\Model\Api\Migrate;

class Products extends Apiabstract
{
    public function setBuilderQuery()
    {
        $this->builderQuery = $this->simiObjectManager
                ->get('Magento\Catalog\Model\Product')
                ->getCollection()
                ->addAttributeToSelect('name');
    }
}

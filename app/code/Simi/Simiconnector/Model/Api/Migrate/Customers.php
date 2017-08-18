<?php

namespace Simi\Simiconnector\Model\Api\Migrate;

class Customers extends Apiabstract
{
    public function setBuilderQuery()
    {
        $this->builderQuery = $this->simiObjectManager->get('Magento\Customer\Model\Customer')->getCollection();
    }
}

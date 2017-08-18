<?php

namespace Simi\Simiconnector\Model\Api\Migrate;

class Orders extends Apiabstract
{
    public function setBuilderQuery()
    {
        $this->builderQuery = $this->simiObjectManager->create('Magento\Sales\Model\Order')->getCollection();
    }
}

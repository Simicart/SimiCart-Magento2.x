<?php

namespace Simi\Simiconnector\Model\Api\Migrate;

class Stores extends Apiabstract
{
    public function setBuilderQuery()
    {
        $this->builderQuery = $this->simiObjectManager->get('\Magento\Store\Model\Group')->getCollection();
    }
}

<?php

namespace Simi\Simiconnector\Model\Api\Migrate;

class Storeviews extends Apiabstract
{
    public function setBuilderQuery()
    {
        $this->builderQuery = $this->simiObjectManager->get('\Magento\Store\Model\Store')->getCollection();
    }
}

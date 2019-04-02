<?php

namespace Simi\Simiconnector\Model\ResourceModel\Customermap;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    public function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\Customermap', 'Simi\Simiconnector\Model\ResourceModel\Customermap');
    }
}

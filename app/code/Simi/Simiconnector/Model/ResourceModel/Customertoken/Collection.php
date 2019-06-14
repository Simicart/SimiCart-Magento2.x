<?php

namespace Simi\Simiconnector\Model\ResourceModel\Customertoken;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    public function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\Customertoken', 'Simi\Simiconnector\Model\ResourceModel\Customertoken');
    }
}

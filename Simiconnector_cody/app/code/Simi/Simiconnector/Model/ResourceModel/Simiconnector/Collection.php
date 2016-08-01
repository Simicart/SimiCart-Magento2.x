<?php

/**
 * Simisimiconnector Resource Collection
 */
namespace Simi\Simisimiconnector\Model\ResourceModel\Simisimiconnector;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Simi\Simisimiconnector\Model\Simisimiconnector', 'Simi\Simisimiconnector\Model\ResourceModel\Simisimiconnector');
    }
}

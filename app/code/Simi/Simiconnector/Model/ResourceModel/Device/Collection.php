<?php

/**
 * Connector Resource Collection
 */

namespace Simi\Simiconnector\Model\ResourceModel\Device;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Resource initialization
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\Device', 'Simi\Simiconnector\Model\ResourceModel\Device');
    }
}

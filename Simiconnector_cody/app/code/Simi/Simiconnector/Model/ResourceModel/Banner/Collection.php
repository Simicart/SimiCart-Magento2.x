<?php

/**
 * Simiconnector Resource Collection
 */
namespace Simi\Simiconnector\Model\ResourceModel\Banner;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\Banner', 'Simi\Simiconnector\Model\ResourceModel\Banner');
    }
}

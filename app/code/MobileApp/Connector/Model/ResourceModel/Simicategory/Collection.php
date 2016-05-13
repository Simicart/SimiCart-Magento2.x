<?php

/**
 * Connector Resource Collection
 */
namespace MobileApp\Connector\Model\ResourceModel\Simicategory;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('MobileApp\Connector\Model\Simicategory', 'MobileApp\Connector\Model\ResourceModel\Simicategory');
    }
}

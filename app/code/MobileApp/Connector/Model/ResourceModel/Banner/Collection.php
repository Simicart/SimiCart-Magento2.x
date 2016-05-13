<?php

/**
 * Connector Resource Collection
 */
namespace MobileApp\Connector\Model\ResourceModel\Banner;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('MobileApp\Connector\Model\Banner', 'MobileApp\Connector\Model\ResourceModel\Banner');
    }
}

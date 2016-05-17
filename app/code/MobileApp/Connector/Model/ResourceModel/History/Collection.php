<?php

/**
 * Connector Resource Collection
 */
namespace MobileApp\Connector\Model\ResourceModel\History;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('MobileApp\Connector\Model\History', 'MobileApp\Connector\Model\ResourceModel\History');
    }
}

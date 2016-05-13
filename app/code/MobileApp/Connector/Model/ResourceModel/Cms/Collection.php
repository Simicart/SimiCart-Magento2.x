<?php

/**
 * Connector Resource Collection
 */
namespace MobileApp\Connector\Model\ResourceModel\Cms;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('MobileApp\Connector\Model\Cms', 'MobileApp\Connector\Model\ResourceModel\Cms');
    }
}

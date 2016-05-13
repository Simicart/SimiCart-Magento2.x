<?php

namespace MobileApp\Connector\Model\ResourceModel;

/**
 * Connector Resource Model
 */
class Device extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('connector_device', 'device_id');
    }
}

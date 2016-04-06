<?php

namespace MobileApp\Connector\Model\ResourceModel;

/**
 * Connector Resource Model
 */
class Connector extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mobileapp_connector', 'connector_id');
    }
}

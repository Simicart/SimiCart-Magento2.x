<?php

namespace Simi\Simisimiconnector\Model\ResourceModel;

/**
 * Simisimiconnector Resource Model
 */
class Simisimiconnector extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('simi_simiconnector', 'simiconnector_id');
    }
}

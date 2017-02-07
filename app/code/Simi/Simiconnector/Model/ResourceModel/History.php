<?php

namespace Simi\Simiconnector\Model\ResourceModel;

/**
 * Connector Resource Model
 */
class History extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('simiconnector_notice_history', 'history_id');
    }
}

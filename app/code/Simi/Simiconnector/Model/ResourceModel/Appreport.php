<?php

namespace Simi\Simiconnector\Model\ResourceModel;

/**
 * Connector Resource Model
 */
class Appreport extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('simiconnector_transactions', 'transaction_id');
    }
}

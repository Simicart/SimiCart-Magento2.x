<?php

namespace Simi\Simiconnector\Model\ResourceModel;

/**
 * Connector Resource Model
 */
class Simicategory extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('simiconnector_simicategory', 'simicategory_id');
    }
}

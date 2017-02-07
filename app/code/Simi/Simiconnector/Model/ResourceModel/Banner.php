<?php

namespace Simi\Simiconnector\Model\ResourceModel;

/**
 * Simiconnector Resource Model
 */
class Banner extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('simiconnector_banner', 'banner_id');
    }
}

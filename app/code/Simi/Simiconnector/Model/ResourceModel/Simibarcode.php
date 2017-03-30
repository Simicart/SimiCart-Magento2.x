<?php

namespace Simi\Simiconnector\Model\ResourceModel;

/**
 * Connector Resource Model
 */
class Simibarcode extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    const TABLE_NAME = "simiconnector_simibarcode";

    public function _construct()
    {
        $this->_init('simiconnector_simibarcode', 'barcode_id');
    }
}

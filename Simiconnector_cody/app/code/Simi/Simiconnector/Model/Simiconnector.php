<?php

namespace Simi\Simiconnector\Model;

/**
 * Simiconnector Model
 *
 * @method \Simi\Simiconnector\Model\Resource\Page _getResource()
 * @method \Simi\Simiconnector\Model\Resource\Page getResource()
 */
class Simiconnector extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\ResourceModel\Connector');
    }

}

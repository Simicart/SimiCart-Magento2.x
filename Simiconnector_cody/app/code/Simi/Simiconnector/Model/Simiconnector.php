<?php

namespace Simi\Simisimiconnector\Model;

/**
 * Simisimiconnector Model
 *
 * @method \Simi\Simisimiconnector\Model\Resource\Page _getResource()
 * @method \Simi\Simisimiconnector\Model\Resource\Page getResource()
 */
class Simisimiconnector extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Simi\Simisimiconnector\Model\ResourceModel\Simisimiconnector');
    }

}

<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model;

class Visibility extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\ResourceModel\Visibility');
    }
}

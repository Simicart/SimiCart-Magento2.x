<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\ResourceModel;

class Visibility extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('simiconnector_visibility', 'entity_id');
    }
}

<?php
/**
 * Copyright © 2015 Simi. All rights reserved.
 */
namespace Simi\Simiconnector\Model\ResourceModel;

/**
 * Codymodel resource
 */
class Codymodel extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('simiconnector_codymodel', 'id');
    }

  
}

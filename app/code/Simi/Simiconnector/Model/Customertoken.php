<?php

namespace Simi\Simiconnector\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;

class Customertoken extends AbstractModel
{
    public function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\ResourceModel\Customertoken');
    }
}

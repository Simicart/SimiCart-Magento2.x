<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Siminotification;

class Devicegrid extends \Simi\Simiconnector\Controller\Adminhtml\Device\Grid
{

    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}

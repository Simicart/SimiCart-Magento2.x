<?php

/**
 * Adminhtml connector list block
 *
 */

namespace Simi\Simiconnector\Block\Adminhtml;

class Transactions extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller     = 'adminhtml_transactions';
        $this->_blockGroup     = 'Simi_Simiconnector';
        $this->_headerText     = __('App Transactions');
        $this->_addButtonLabel = __('Add New App Transactions');
        parent::_construct();
        $this->buttonList->remove('add');
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    public function _isAllowedAction($resourceId)
    {
        return true;
    }
}

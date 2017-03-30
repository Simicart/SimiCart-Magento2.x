<?php

/**
 * Adminhtml connector list block
 *
 */

namespace Simi\Simiconnector\Block\Adminhtml;

class Siminotification extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller     = 'adminhtml_siminotification';
        $this->_blockGroup     = 'Simi_Simiconnector';
        $this->_headerText     = __('Notification');
        $this->_addButtonLabel = __('Add New Notification');
        parent::_construct();
        if ($this->_isAllowedAction('Simi_Simiconnector::save')) {
            $this->buttonList->update('add', 'label', __('Add Notification'));
        } else {
            $this->buttonList->remove('add');
        }
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

<?php

/**
 * Adminhtml simiconnector list block
 *
 */

namespace Simi\Simiconnector\Block\Adminhtml;

class Simivideo extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller     = 'adminhtml_simivideo';
        $this->_blockGroup     = 'Simi_Simiconnector';
        $this->_headerText     = __('Videos');
        $this->_addButtonLabel = __('Add New Video');
        parent::_construct();
        if ($this->_isAllowedAction('Simi_Simiconnector::save')) {
            $this->buttonList->update('add', 'label', __('Add Video'));
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

<?php

/**
 * Adminhtml simiconnector list block
 *
 */

namespace Simi\Simiconnector\Block\Adminhtml;

class Banner extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller     = 'adminhtml_banner';
        $this->_blockGroup     = 'Simi_Simiconnector';
        $this->_headerText     = __('Banner');
        $this->_addButtonLabel = __('Add New Banner');
        parent::_construct();
        if ($this->_isAllowedAction('Simi_Simiconnector::save')) {
            $this->buttonList->update('add', 'label', __('Add Banner'));
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

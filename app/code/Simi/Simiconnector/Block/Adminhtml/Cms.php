<?php

/**
 * Adminhtml connector list block
 *
 */

namespace Simi\Simiconnector\Block\Adminhtml;

class Cms extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller     = 'adminhtml_cms';
        $this->_blockGroup     = 'Simi_Simiconnector';
        $this->_headerText     = __('CMS');
        $this->_addButtonLabel = __('Add New Cms');
        parent::_construct();
        if ($this->_isAllowedAction('Simi_Simiconnector::save')) {
            $this->buttonList->update('add', 'label', __('Add CMS'));
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

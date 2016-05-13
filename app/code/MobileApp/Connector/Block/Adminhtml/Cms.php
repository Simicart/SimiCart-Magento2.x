<?php
/**
 * Adminhtml connector list block
 *
 */
namespace MobileApp\Connector\Block\Adminhtml;

class Cms extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_cms';
        $this->_blockGroup = 'MobileApp_Connector';
        $this->_headerText = __('CMS');
        $this->_addButtonLabel = __('Add New Cms');
        parent::_construct();
        if ($this->_isAllowedAction('MobileApp_Connector::save')) {
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
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

}

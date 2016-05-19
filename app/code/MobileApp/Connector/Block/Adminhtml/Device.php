<?php
/**
 * Adminhtml connector list block
 *
 */
namespace MobileApp\Connector\Block\Adminhtml;

class Device extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_device';
        $this->_blockGroup = 'MobileApp_Connector';
        $this->_headerText = __('Device');
        $this->_addButtonLabel = __('Add New Device');
        parent::_construct();
        $this->buttonList->remove('add');
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

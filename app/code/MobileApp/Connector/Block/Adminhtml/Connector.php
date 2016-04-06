<?php
/**
 * Adminhtml connector list block
 *
 */
namespace MobileApp\Connector\Block\Adminhtml;

class Connector extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_connector';
        $this->_blockGroup = 'MobileApp_Connector';
        $this->_headerText = __('Connector');
        $this->_addButtonLabel = __('Add New Connector');
        parent::_construct();
        if ($this->_isAllowedAction('MobileApp_Connector::save')) {
            $this->buttonList->update('add', 'label', __('Add New Connector'));
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

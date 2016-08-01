<?php
/**
 * Adminhtml simiconnector list block
 *
 */
namespace Simi\Simisimiconnector\Block\Adminhtml;

class Simisimiconnector extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_simiconnector';
        $this->_blockGroup = 'Simi_Simisimiconnector';
        $this->_headerText = __('Simisimiconnector');
        $this->_addButtonLabel = __('Add New Simisimiconnector');
        parent::_construct();
        if ($this->_isAllowedAction('Simi_Simisimiconnector::save')) {
            $this->buttonList->update('add', 'label', __('Add New Simisimiconnector'));
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

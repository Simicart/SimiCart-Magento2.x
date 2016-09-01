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
    protected function _construct()
    {
        $this->_controller = 'adminhtml_transactions';
        $this->_blockGroup = 'Simi_Simiconnector';
        $this->_headerText = __('App Transactions');
        $this->_addButtonLabel = __('Add New App Transactions');
        parent::_construct();
        $this->buttonList->remove('add');
//        if ($this->_isAllowedAction('Simi_Simiconnector::save')) {
//            $this->buttonList->update('add', 'label', __('Add New Connector'));
//        } else {
//            $this->buttonList->remove('add');
//        }
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

<?php
namespace Simi\Simiconnector\Block\Adminhtml;
class Codymodel extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
		
        $this->_controller = 'adminhtml_codymodel';/*block grid.php directory*/
        $this->_blockGroup = 'Simi_Simiconnector';
        $this->_headerText = __('Codymodel');
        $this->_addButtonLabel = __('Add New Entry'); 
        parent::_construct();
		
    }
}
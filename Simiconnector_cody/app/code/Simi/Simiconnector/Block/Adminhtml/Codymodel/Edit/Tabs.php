<?php
namespace Simi\Simiconnector\Block\Adminhtml\Codymodel\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    protected function _construct()
    {
		
        parent::_construct();
        $this->setId('checkmodule_codymodel_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Codymodel Information'));
    }
}
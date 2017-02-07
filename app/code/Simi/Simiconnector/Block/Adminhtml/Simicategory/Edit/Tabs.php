<?php

namespace Simi\Simiconnector\Block\Adminhtml\Simicategory\Edit;

/**
 * Admin simiconnector left menu
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('page_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Simicategory Information'));
    }
}

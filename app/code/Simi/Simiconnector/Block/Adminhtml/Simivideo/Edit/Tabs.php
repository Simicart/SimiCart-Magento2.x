<?php

namespace Simi\Simiconnector\Block\Adminhtml\Simivideo\Edit;

/**
 * Admin connector left menu
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
        $this->setTitle(__('Video Information'));
    }
}

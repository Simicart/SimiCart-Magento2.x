<?php
namespace MobileApp\Connector\Block\Adminhtml\Banner\Edit;

/**
 * Admin connector left menu
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('page_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Banner Information'));
    }

    /**
     * Prepare Layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addTab(
            'category',
            [
                'label' => __('Select Product'),
                'url' => $this->getUrl('connector/*/productgrid', ['_current' => true]),
                'class' => 'ajax',
            ]
        );
        return parent::_prepareLayout();
    }
}

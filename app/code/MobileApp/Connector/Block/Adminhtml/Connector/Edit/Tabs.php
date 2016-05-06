<?php
namespace MobileApp\Connector\Block\Adminhtml\Connector\Edit;

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
        $this->setTitle(__('Mobile App Information'));
    }

    /**
     * Prepare Layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addTab(
            'plugin',
            [
                'label' => __('Manage Plugins'),
                'url' => $this->getUrl('connector/*/pluginlist', ['_current' => true]),
                'class' => 'ajax'
            ]
        );

        $this->addTab(
            'category',
            [
                'label' => __('Manage Categories'),
                'title' => __('Manage Categories'),
                'content' => $this->getLayout()->createBlock('MobileApp\Connector\Block\Adminhtml\Connector\Edit\Tab\Category')->toHtml(),
                'class' => 'ajax'
            ]
        );

        $id = $this->getRequest()->getParam('device_id');
        if($id == "1" || $id == "2"){
            $this->addTab(
                'pem',
                [
                    'label' => __('Upload PEM File'),
                    'title' => __('Upload PEM File'),
                    'content' => $this->getLayout()->createBlock('MobileApp\Connector\Block\Adminhtml\Connector\Edit\Tab\Pem')->toHtml(),
                    'class' => 'ajax'
                ]
            );
        } else if($id == 3) {
            $this->addTab(
                'keyapp',
                [
                    'label' => __('Key app for Notification'),
                    'title' => __('Key app for Notification'),
                    'content' => $this->getLayout()->createBlock('MobileApp\Connector\Block\Adminhtml\Connector\Edit\Tab\Android')->toHtml(),
                    'class' => 'ajax'
                ]
            );
        }
        return parent::_prepareLayout();
    }
}

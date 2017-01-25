<?php
namespace Simi\Simiconnector\Block\Adminhtml\Simibarcode\Edit;

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
        $this->setTitle(__('barcode Information'));
    }
    
    protected function _beforeToHtml() {
        if (!$this->getRequest()->getParam('barcode_id')) {
            $this->addTab('form_section', array(
                'label' => __('Product Select'),
                'title' => __('Product Select'),
                'content' => $this->getLayout()
                        ->createBlock('Simi\Simiconnector\Block\Adminhtml\Simibarcode\Edit\Tab\Newcode')
                        ->toHtml(),
            ));
        } else {
            $this->addTab('form_section', array(
                'label' => __('Barcode Information'),
                'title' => __('Barcode Information'),
                'content' => $this->getLayout()
                        ->createBlock('Simi\Simiconnector\Block\Adminhtml\Simibarcode\Edit\Tab\Main')
                        ->toHtml(),
            ));
        }
        return parent::_beforeToHtml();
    }
}

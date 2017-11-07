<?php

namespace Simi\Simiconnector\Block\Adminhtml\Simibarcode\Edit\Tab;

use \Magento\Backend\Block\Widget\Form\Generic;

/**
 * Cms page edit form Newcode tab
 */
class Newcode extends Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    public $simiObjectManager;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    public $systemStore;

    /**
     * @var \Simi\Simiconnector\Helper\Website
     * */
    public $websiteHelper;

    /**
     * @var \Simi\Simiconnector\Model\Simibarcode
     */
    public $simibarcodeFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    public $jsonEncoder;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    public $categoryFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Simi\Simiconnector\Helper\Website $websiteHelper,
        \Simi\Simiconnector\Model\SimibarcodeFactory $simibarcodeFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    ) {
   
        $this->simiObjectManager   = $simiObjectManager;
        $this->simibarcodeFactory = $simibarcodeFactory;
        $this->websiteHelper       = $websiteHelper;
        $this->systemStore         = $systemStore;
        $this->jsonEncoder         = $jsonEncoder;
        $this->categoryFactory     = $categoryFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    public function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Select Product(s)')]);

        $fieldset->addField(
            'product_ids',
            'text',
            [
            'name'               => 'product_ids',
            'label'              => __('Product ID(s)'),
            'title'              => __('Choose products'),
            'after_element_html' =>
                '<a href="#" title="Show Product Grid" onclick="toogleProduct();return false;">'
                . '<img id="show_product_grid" src="'
                . $this->getViewFileUrl('Simi_Simiconnector::images/arrow_down.png') . '" title="" /></a>'
                . $this->getLayout()
                ->createBlock('Simi\Simiconnector\Block\Adminhtml\Simibarcode\Edit\Tab\Productgrid')->toHtml()
                ]
        );

        $this->_eventManager->dispatch('adminhtml_simibarcode_edit_tab_main_prepare_form', ['form' => $form]);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('barcode Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('barcode Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    public function _isAllowedAction($resourceId)
    {
        return true;
    }
}

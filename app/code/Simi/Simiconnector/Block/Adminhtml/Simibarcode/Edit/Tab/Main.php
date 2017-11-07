<?php

namespace Simi\Simiconnector\Block\Adminhtml\Simibarcode\Edit\Tab;

/**
 * Cms page edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
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
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Simi\Simiconnector\Helper\Website $websiteHelper,
        \Simi\Simiconnector\Model\SimibarcodeFactory $simibarcodeFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
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
        
        $model = $this->_coreRegistry->registry('simibarcode');
        $data  = $model->toArray();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');

        $fieldset  = $form->addFieldset('base_fieldset', ['legend' => __('Code Information')]);
        $width     = $height    = 200;
        $sampleQR  = '<img src="http://chart.googleapis.com/chart?chs=' .
                $width . 'x' . $height . '&cht=qr&chl=' . $data['qrcode'] . '" />';
        $sampleBar = '<img id="simi-barcode-present" '
                . 'src="http://barcodes4.me/barcode/c128a/'.$data['barcode'].'.jpg" />';
        $data = $model->getData();
        if ($model->getId()) {
            $fieldset->addField('barcode_id', 'hidden', ['name' => 'barcode_id']);
        }

        $fieldset->addField(
            'barcode',
            'text',
            [
            'name'     => 'barcode',
            'label'    => __('Barcode'),
            'title'    => __('Barcode'),
            'required' => false
                ]
        );

        $fieldset->addField(
            'barcode_type',
            'select',
            [
            'name'               => 'barcode_type',
            'label'              => __(''),
            'title'              => __(''),
            'required'           => false,
            'options'            => $this->simibarcodeFactory->create()->toOptionBarcodeTypeHash(),
            'onclick'            => 'updateBarcodeValue()',
            'onchange'           => 'updateBarcodePresent()',
            'after_element_html' => $sampleBar
                ]
        );

        $fieldset->addField(
            'qrcode',
            'text',
            [
            'name'               => 'qrcode',
            'label'              => __('QR code'),
            'title'              => __('QR code'),
            'bold'               => true,
            'required'           => false,
            'after_element_html' => $sampleQR
                ]
        );

        $fieldset->addField(
            'product_name',
            'label',
            [
            'name'     => 'product_name',
            'label'    => __('QR code'),
            'title'    => __('QR code'),
            'required' => false,
                ]
        );

        $fieldset->addField(
            'product_sku',
            'label',
            [
            'name'     => 'product_sku',
            'label'    => __('Product Sku'),
            'title'    => __('Product Sku'),
            'required' => false,
                ]
        );

        $fieldset->addField(
            'created_date',
            'label',
            [
            'name'     => 'created_date',
            'label'    => __('Created Date'),
            'title'    => __('Created Date'),
            'required' => false,
                ]
        );

        $fieldset->addField(
            'status',
            'select',
            [
            'name'     => 'status',
            'label'    => __('Status'),
            'title'    => __('Status'),
            'required' => false,
            'options'  => $this->simibarcodeFactory->create()->toOptionStatusHash(),
                ]
        );

        $this->_eventManager->dispatch('adminhtml_simibarcode_edit_tab_main_prepare_form', ['form' => $form]);

        $form->setValues($data);
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

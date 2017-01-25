<?php

namespace Simi\Simiconnector\Block\Adminhtml\Simibarcode\Edit\Tab;

/**
 * Cms page edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface {

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $_objectManager;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Simi\Simiconnector\Helper\Website
     * */
    protected $_websiteHelper;

    /**
     * @var \Simi\Simiconnector\Model\Simibarcode
     */
    protected $_simibarcodeFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

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
    \Magento\Catalog\Model\CategoryFactory $categoryFactory, array $data = []
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_simibarcodeFactory = $simibarcodeFactory;
        $this->_websiteHelper = $websiteHelper;
        $this->_systemStore = $systemStore;
        $this->_jsonEncoder = $jsonEncoder;
        $this->_categoryFactory = $categoryFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm() {
        /* @var $model \Magento\Cms\Model\Page */
        $model = $this->_coreRegistry->registry('simibarcode');
        $data = $model->toArray();
        
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Code Information')]);
        $width = $height = 200;
        $sampleQR = '<img src="http://chart.googleapis.com/chart?chs='.$width.'x'.$height.'&cht=qr&chl='.$data['qrcode'].'" />';
        $sampleBar = '<img id="simi-barcode-present" src="'.$this->getUrl('*/*/barcode', ['code'=>$data['barcode'], 'type'=>'code128']).'" />';
        
        $data = $model->getData();
        if ($model->getId()) {
            $fieldset->addField('barcode_id', 'hidden', ['name' => 'barcode_id']);
        }

        $fieldset->addField(
                'barcode', 'text', [
            'name' => 'barcode',
            'label' => __('Barcode'),
            'title' => __('Barcode'),
            'required' => false
                ]
        );
        
        $fieldset->addField(
                'barcode_type', 'select', [
            'name' => 'barcode_type',
            'label' => __(''),
            'title' => __(''),
            'required' => false,
            'options' => $this->_simibarcodeFactory->create()->toOptionBarcodeTypeHash(),
            'onclick' => 'updateBarcodeValue()',
            'onchange' => 'updateBarcodePresent()',        
            'after_element_html' => $sampleBar
                ]
        );
        
        $fieldset->addField(
                'qrcode', 'text', [
            'name' => 'qrcode',
            'label' => __('QR code'),
            'title' => __('QR code'),
            'bold' => true,
            'required' => false,
            'after_element_html' => $sampleQR
                ]
        );
        
        $fieldset->addField(
                'product_name', 'label', [
            'name' => 'product_name',
            'label' => __('QR code'),
            'title' => __('QR code'),
            'required' => false,
                ]
        );
        
        
        $fieldset->addField(
                'product_sku', 'label', [
            'name' => 'product_sku',
            'label' => __('Product Sku'),
            'title' => __('Product Sku'),
            'required' => false,
                ]
        );
        
        $fieldset->addField(
                'created_date', 'label', [
            'name' => 'created_date',
            'label' => __('Created Date'),
            'title' => __('Created Date'),
            'required' => false,
                ]
        );
        
        
        $fieldset->addField(
                'status', 'select', [
            'name' => 'status',
            'label' => __('Status'),
            'title' => __('Status'),
            'required' => false,
            'options' => $this->_simibarcodeFactory->create()->toOptionStatusHash(),
                ]
        );
        

        $this->_eventManager->dispatch('adminhtml_simibarcode_edit_tab_main_prepare_form', ['form' => $form]);


        $form->setValues($data);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Get parent category options
     *
     * @return array
     */
    protected function _getParentCategoryOptions($category_id) {

        $items = $this->_categoryFactory->create()->getCollection()->addAttributeToSelect(
                        'name'
                )->addAttributeToSort(
                        'entity_id', 'ASC'
                )->setPageSize(
                        3
                )->load()->getItems();

        $result = [];
        if (count($items) === 2) {
            $item = array_pop($items);
            $result = [$item->getEntityId() => $item->getName()];
        }

        if (sizeof($result) == 0 && $category_id) {
            $category = $this->_categoryFactory->create()->load($category_id);
            $result = [$category_id => $category->getName()];
        }

        return $result;
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel() {
        return __('barcode Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle() {
        return __('barcode Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden() {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId) {
        return true;
    }

}

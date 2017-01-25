<?php

namespace Simi\Simiconnector\Block\Adminhtml\Simibarcode\Edit\Tab;

/**
 * Cms page edit form Newcode tab
 */
class Newcode extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface {

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
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Select Product(s)')]);
        
        $fieldset->addField(
                'product_ids', 'text', [
            'name' => 'product_ids',
            'label' => __('Product ID(s)'),
            'title' => __('Choose products'),
            'after_element_html' => '<a href="#" title="Show Product Grid" onclick="toogleProduct();return false;"><img id="show_product_grid" src="' . $this->getViewFileUrl('Simi_Simiconnector::images/arrow_down.png') . '" title="" /></a>' . $this->getLayout()->createBlock('Simi\Simiconnector\Block\Adminhtml\Simibarcode\Edit\Tab\Productgrid')->toHtml()
                ]
        );


        $this->_eventManager->dispatch('adminhtml_simibarcode_edit_tab_main_prepare_form', ['form' => $form]);
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
        //return $this->_authorization->isAllowed($resourceId);
    }

}

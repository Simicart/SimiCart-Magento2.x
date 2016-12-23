<?php

namespace Simi\Simiconnector\Block\Adminhtml\Productlist\Edit\Tab;

/**
 * Cms page edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface {

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
     * @var \Simi\Simiconnector\Model\Productlist
     */
    protected $_productlistFactory;

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
    \Magento\Backend\Block\Template\Context $context, \Magento\Framework\Registry $registry, \Magento\Framework\Data\FormFactory $formFactory, \Magento\Store\Model\System\Store $systemStore, \Simi\Simiconnector\Helper\Website $websiteHelper, \Simi\Simiconnector\Model\ProductlistFactory $productlistFactory, \Magento\Framework\Json\EncoderInterface $jsonEncoder, \Magento\Catalog\Model\CategoryFactory $categoryFactory, array $data = []
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_productlistFactory = $productlistFactory;
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
        $model = $this->_coreRegistry->registry('productlist');

        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('Simi_Simiconnector::productlist_save')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Product List Information')]);

        $new_category_parent = false;
        $data = $model->getData();
        if ($model->getId()) {
            $fieldset->addField('productlist_id', 'hidden', ['name' => 'productlist_id']);
            $new_category_parent = $model->getData('category_id');

            $simiconnectorhelper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');
            $typeID = $simiconnectorhelper->getVisibilityTypeId('productlist');
            $visibleStoreViews = $this->_objectManager->create('Simi\Simiconnector\Model\Visibility')->getCollection()
                    ->addFieldToFilter('content_type', $typeID)
                    ->addFieldToFilter('item_id', $model->getId());
            $storeIdArray = array();

            foreach ($visibleStoreViews as $visibilityItem) {
                $storeIdArray[] = $visibilityItem->getData('store_view_id');
            }
            $data['storeview_id'] = implode(',', $storeIdArray);
        }

        $storeResourceModel = $this->_objectManager->get('Simi\Simiconnector\Model\ResourceModel\Storeviewmultiselect');

        $fieldset->addField('storeview_id', 'multiselect', array(
            'name' => 'storeview_id[]',
            'label' => __('Store View'),
            'title' => __('Store View'),
            'required' => true,
            'values' => $storeResourceModel->toArray(),
        ));


        $fieldset->addField(
                'list_title', 'text', [
            'name' => 'list_title',
            'label' => __('Title'),
            'title' => __('Title'),
            'disabled' => $isElementDisabled
                ]
        );

        $fieldset->addField(
                'list_image', 'image', [
            'name' => 'list_image',
            'label' => __('Product List Image'),
            'title' => __('Product List Image'),
            'disabled' => $isElementDisabled
                ]
        );

        $fieldset->addField(
                'list_image_tablet', 'image', [
            'name' => 'list_image_tablet',
            'label' => __('Product List Tablet Image'),
            'title' => __('Product List Tablet Image'),
            'disabled' => $isElementDisabled
                ]
        );
        
        if (!isset($data['sort_order']))
            $data['sort_order'] = 1;
        $fieldset->addField(
                'sort_order', 'text', [
            'name' => 'sort_order',
            'label' => __('Sort Order'),
            'title' => __('Sort Order'),
            'class' => 'validate-not-negative-number',
            'disabled' => $isElementDisabled
                ]
        );



        if (!isset($data['list_type']))
            $data['list_type'] = 2;

        $fieldset->addField(
                'list_type', 'select', [
            'name' => 'list_type',
            'label' => __('Product List Type'),
            'title' => __('Product List Type'),
            'required' => false,
            'disabled' => $isElementDisabled,
            'options' => $this->_objectManager->get('Simi\Simiconnector\Helper\Productlist')->getListTypeId(),
            'onchange' => 'changeType(this.value)',
                ]
        );


        $fieldset->addField(
                'list_products', 'text', [
            'name' => 'list_products',
            'label' => __('Product ID(s)'),
            'title' => __('Choose products'),
            'disabled' => $isElementDisabled,
            'after_element_html' => '<a href="#" title="Show Product Grid" onclick="toogleProduct();return false;"><img id="show_product_grid" src="' . $this->getViewFileUrl('Simi_Simiconnector::images/arrow_down.png') . '" title="" /></a>' . $this->getLayout()->createBlock('Simi\Simiconnector\Block\Adminhtml\Productlist\Edit\Tab\Productgrid')->toHtml()
                ]
        );


        $fieldset->addField(
                'list_status', 'select', [
            'name' => 'list_status',
            'label' => __('Enable'),
            'title' => __('Enable'),
            'required' => false,
            'disabled' => $isElementDisabled,
            'options' => $this->_productlistFactory->create()->toOptionStatusHash(),
                ]
        );

        $this->_eventManager->dispatch('adminhtml_productlist_edit_tab_main_prepare_form', ['form' => $form]);

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
        return __('Product List Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle() {
        return __('Product List Information');
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
    protected function _isAllowedAction($resourceId)
    {
        return true;
        //return $this->_authorization->isAllowed($resourceId);
    }

}

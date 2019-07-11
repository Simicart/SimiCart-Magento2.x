<?php

namespace Simi\Simiconnector\Block\Adminhtml\Productlist\Edit\Tab;

/**
 * Cms page edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

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
     * @var \Simi\Simiconnector\Model\Productlist
     */
    public $productlistFactory;

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
        \Simi\Simiconnector\Model\ProductlistFactory $productlistFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        array $data = []
    ) {
   
        $this->simiObjectManager   = $simiObjectManager;
        $this->productlistFactory = $productlistFactory;
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

        $data                = $model->getData();
        if ($model->getId()) {
            $fieldset->addField('productlist_id', 'hidden', ['name' => 'productlist_id']);

            $simiconnectorhelper = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Data');
            $typeID              = $simiconnectorhelper->getVisibilityTypeId('productlist');
            $visibleStoreViews   = $this->simiObjectManager
                    ->create('Simi\Simiconnector\Model\Visibility')->getCollection()
                    ->addFieldToFilter('content_type', $typeID)
                    ->addFieldToFilter('item_id', $model->getId());
            $storeIdArray        = [];

            foreach ($visibleStoreViews as $visibilityItem) {
                $storeIdArray[] = $visibilityItem->getData('store_view_id');
            }
            $data['storeview_id'] = implode(',', $storeIdArray);
        }

        $storeResourceModel = $this->simiObjectManager
                ->get('Simi\Simiconnector\Model\ResourceModel\Storeviewmultiselect');

        $fieldset->addField('storeview_id', 'multiselect', [
            'name'     => 'storeview_id[]',
            'label'    => __('Store View'),
            'title'    => __('Store View'),
            'required' => true,
            'values'   => $storeResourceModel->toOptionArray(),
        ]);

        $fieldset->addField(
            'list_title',
            'text',
            [
            'name'     => 'list_title',
            'label'    => __('Title'),
            'title'    => __('Title'),
            'required' => true,
            'disabled' => $isElementDisabled
                ]
        );

        $fieldset->addField(
            'list_image',
            'image',
            [
            'name'     => 'list_image',
            'label'    => __('Product List Image'),
            'title'    => __('Product List Image'),
            'disabled' => $isElementDisabled
                ]
        );

        $fieldset->addField(
            'list_image_tablet',
            'image',
            [
            'name'     => 'list_image_tablet',
            'label'    => __('Product List Tablet Image'),
            'title'    => __('Product List Tablet Image'),
            'disabled' => $isElementDisabled
                ]
        );

        if (!isset($data['sort_order'])) {
            $data['sort_order'] = 1;
        }
        $fieldset->addField(
            'sort_order',
            'text',
            [
            'name'     => 'sort_order',
            'label'    => __('Sort Order'),
            'title'    => __('Sort Order'),
            'class'    => 'validate-not-negative-number',
            'disabled' => $isElementDisabled
                ]
        );

        if (!isset($data['list_type'])) {
            $data['list_type'] = 6;
        }

        $fieldset->addField(
            'list_type',
            'select',
            [
            'name'     => 'list_type',
            'label'    => __('Product List Type'),
            'title'    => __('Product List Type'),
            'required' => false,
            'disabled' => true,
            'options'  => $this->simiObjectManager->get('Simi\Simiconnector\Helper\Productlist')->getListTypeId(),
            'onchange' => 'changeType(this.value)',
                ]
        );

        $fieldset->addField('category_id', 'select', [
            'name'     => 'category_id',
            'label'    => __('Category'),
            'title'    => __('Category'),
            'required' => true,
            'values'   => $this->simiObjectManager->get('Simi\Simiconnector\Helper\Catetree')->getChildCatArray(),
        ]);

        $fieldset->addField(
            'list_products',
            'text',
            [
            'name'               => 'list_products',
            'label'              => __('Product ID(s)'),
            'title'              => __('Choose products'),
            'after_element_html' => '<a href="#" title="Show Product Grid" onclick="toogleProduct();return false;">'
                . '<img id="show_product_grid" src="'
                . $this->getViewFileUrl('Simi_Simiconnector::images/arrow_down.png') . '" title="" /></a>'
                . $this->getLayout()
                ->createBlock('Simi\Simiconnector\Block\Adminhtml\Productlist\Edit\Tab\Productgrid')
                ->toHtml()
                ]
        );

        $fieldset->addField(
            'list_status',
            'select',
            [
            'name'     => 'list_status',
            'label'    => __('Enable'),
            'title'    => __('Enable'),
            'required' => false,
            'disabled' => $isElementDisabled,
            'options'  => $this->productlistFactory->create()->toOptionStatusHash(),
                ]
        );

        $this->_eventManager->dispatch('adminhtml_productlist_edit_tab_main_prepare_form', ['form' => $form]);

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
        return __('Product List Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Product List Information');
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

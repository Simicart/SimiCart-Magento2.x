<?php

namespace Simi\Simiconnector\Block\Adminhtml\Banner\Edit\Tab;

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
     * @var \Simi\Simiconnector\Model\Banner
     */
    public $bannerFactory;

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
        \Simi\Simiconnector\Model\BannerFactory $bannerFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        array $data = []
    ) {
   
        $this->simiObjectManager = $simiObjectManager;
        $this->bannerFactory     = $bannerFactory;
        $this->websiteHelper     = $websiteHelper;
        $this->systemStore       = $systemStore;
        $this->jsonEncoder       = $jsonEncoder;
        $this->categoryFactory   = $categoryFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    public function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('banner');

        /*
         * Checking if user have permissions to save information
         */
        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        $fieldset            = $form->addFieldset('base_fieldset', ['legend' => __('Banner Information')]);

        $data = $model->getData();
        if ($model->getId()) {
            $fieldset->addField('banner_id', 'hidden', ['name' => 'banner_id']);
            $simiconnectorhelper = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Data');
            $typeID              = $simiconnectorhelper->getVisibilityTypeId('banner');
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
                ->create('Simi\Simiconnector\Model\ResourceModel\Storeviewmultiselect');

        $fieldset->addField('storeview_id', 'multiselect', [
            'name'     => 'storeview_id[]',
            'label'    => __('Store View'),
            'title'    => __('Store View'),
            'required' => true,
            'values'   => $storeResourceModel->toOptionArray(),
        ]);

        $fieldset->addField(
            'banner_title',
            'text',
            [
            'name'     => 'banner_title',
            'label'    => __('Title'),
            'title'    => __('Title'),
            'required' => true,
            'disabled' => $isElementDisabled
                ]
        );

        $fieldset->addField('banner_name', 'image', ['name'     => 'banner_name',
            'label'    => __('Image (width:640px, height:340px)'),
            'title'    => __('Image (width:640px, height:340px)'),
            'required' => false,
            'disabled' => $isElementDisabled
        ]);

        $fieldset->addField(
            'banner_name_tablet',
            'image',
            [
            'name'     => 'banner_name_tablet',
            'label'    => __('Tablet Image (width:640px, height:340px)'),
            'title'    => __('Tablet Image (width:640px, height:340px)'),
            'required' => false,
            'disabled' => $isElementDisabled
                ]
        );

        $fieldset->addField(
            'type',
            'select',
            [
            'name'     => 'type',
            'label'    => __('Direct viewers to'),
            'title'    => __('Direct viewers to'),
            'required' => true,
            'disabled' => $isElementDisabled,
            'options'  => $this->bannerFactory->create()->toOptionTypeHash(),
            'onchange' => 'changeType(this.value)',
                ]
        );

        /* product + category + url */
        $fieldset->addField(
            'product_id',
            'text',
            [
            'name'               => 'product_id',
            'label'              => __('Product ID'),
            'title'              => __('Product ID'),
            'required'           => true,
            'disabled'           => $isElementDisabled,
            'class' => 'validate-number',
            'after_element_html' => '<a href="#" title="Show Product Grid" onclick="toogleProduct();return false;">'
                . '<img id="show_product_grid" src="'
                . $this->getViewFileUrl('Simi_Simiconnector::images/arrow_down.png')
                . '" title="" /></a>'
                . $this->getLayout()->createBlock('Simi\Simiconnector\Block\Adminhtml\Banner\Edit\Tab\Productgrid')
                ->toHtml()
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
            'banner_url',
            'textarea',
            [
            'name'     => 'banner_url',
            'label'    => __('Url'),
            'title'    => __('Url'),
            'required' => true,
            'disabled' => $isElementDisabled,
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

        $fieldset->addField(
            'status',
            'select',
            [
            'name'     => 'status',
            'label'    => __('Status'),
            'title'    => __('Status'),
            'required' => false,
            'disabled' => $isElementDisabled,
            'options'  => $this->bannerFactory->create()->toOptionStatusHash(),
                ]
        );

        /* product + category + url */

        $this->_eventManager->dispatch('adminhtml_banner_edit_tab_main_prepare_form', ['form' => $form]);

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
        return __('Banner Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Banner Information');
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

<?php

namespace Simi\Simiconnector\Block\Adminhtml\Simicategory\Edit\Tab;

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
     * @var \Simi\Simiconnector\Model\Simicategory
     */
    public $simicategoryFactory;

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
        \Simi\Simiconnector\Model\SimicategoryFactory $simicategoryFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        array $data = []
    ) {
   
        $this->simiObjectManager    = $simiObjectManager;
        $this->simicategoryFactory = $simicategoryFactory;
        $this->websiteHelper        = $websiteHelper;
        $this->systemStore          = $systemStore;
        $this->jsonEncoder          = $jsonEncoder;
        $this->categoryFactory      = $categoryFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    public function _prepareForm()
    {
        
        $model = $this->_coreRegistry->registry('simicategory');

        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('Simi_Simiconnector::simicategory_save')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Simicategory Information')]);

        $data                = $model->getData();
        if ($model->getId()) {
            $fieldset->addField('simicategory_id', 'hidden', ['name' => 'simicategory_id']);
            $simiconnectorhelper = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Data');
            $typeID              = $simiconnectorhelper->getVisibilityTypeId('homecategory');
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
            'simicategory_filename',
            'image',
            [
            'name'     => 'simicategory_filename',
            'label'    => __('Image (width:220px, height:220px)'),
            'title'    => __('Image (width:220px, height:220px)'),
            'disabled' => $isElementDisabled
                ]
        );

        $fieldset->addField(
            'simicategory_filename_tablet',
            'image',
            [
            'name'     => 'simicategory_filename_tablet',
            'label'    => __('Tablet Image (width:220px, height:220px)'),
            'title'    => __('Tablet Image (width:220px, height:220px)'),
            'disabled' => $isElementDisabled
                ]
        );

        $fieldset->addField('category_id', 'select', [
            'name'     => 'category_id',
            'label'    => __('Category'),
            'title'    => __('Category'),
            'required' => true,
            'values'   => $this->simiObjectManager->get('Simi\Simiconnector\Helper\Catetree')->getChildCatArray(),
        ]);

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
            'options'  => $this->simicategoryFactory->create()->toOptionStatusHash(),
                ]
        );

        $this->_eventManager->dispatch('adminhtml_simicategory_edit_tab_main_prepare_form', ['form' => $form]);

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
        return __('Simicategory Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Simicategory Information');
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

<?php

namespace Simi\Simiconnector\Block\Adminhtml\Simiproductlabel\Edit\Tab;

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
     * @var \Simi\Simiconnector\Model\Simiproductlabel
     */
    public $simiproductlabelFactory;

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
        \Simi\Simiconnector\Model\SimiproductlabelFactory $simiproductlabelFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    ) {
   
        $this->simiObjectManager        = $simiObjectManager;
        $this->simiproductlabelFactory = $simiproductlabelFactory;
        $this->websiteHelper            = $websiteHelper;
        $this->systemStore              = $systemStore;
        $this->jsonEncoder              = $jsonEncoder;
        $this->categoryFactory          = $categoryFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    public function _prepareForm()
    {
        
        $model = $this->_coreRegistry->registry('simiproductlabel');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Product Label Information')]);

        $data = $model->getData();
        if ($model->getId()) {
            $fieldset->addField('label_id', 'hidden', ['name' => 'label_id']);
        }

        if (!isset($data['status']) || ($data['status'] == null)) {
            $data['status'] = 1;
        }

        $fieldset->addField(
            'status',
            'select',
            [
            'name'     => 'status',
            'label'    => __('Status'),
            'title'    => __('Status'),
            'required' => false,
            'options'  => $this->simiproductlabelFactory->create()->toOptionStatusHash(),
                ]
        );

        $stores     = $this->simiObjectManager->get('\Magento\Store\Model\Store')->getCollection();
        $list_store = [];
        foreach ($stores as $store) {
            $list_store[] = [
                'value' => $store->getId(),
                'label' => $store->getName(),
            ];
        }

        $fieldset->addField('storeview_id', 'select', [
            'label'  => __('Store View'),
            'title'  => __('Store View'),
            'name'   => 'storeview_id',
            'values' => $list_store
        ]);

        $fieldset->addField('name', 'text', [
            'name'     => 'label_name',
            'label'    => __('Label Name'),
            'title'    => __('Label Name'),
            'required' => true
        ]);

        $fieldset->addField(
            'description',
            'textarea',
            [
            'name'     => 'description',
            'label'    => __('Description'),
            'title'    => __('Description'),
            'required' => true
                ]
        );

        $fieldset->addField('image', 'image', [
            'name'     => 'image',
            'label'    => __('Image (width:340px, height:340px)'),
            'title'    => __('Image (width:340px, height:340px)'),
            'required' => false
        ]);

        $fieldset->addField(
            'position',
            'select',
            [
            'name'     => 'position',
            'label'    => __('Position'),
            'title'    => __('Position'),
            'required' => false,
            'options'  => $this->simiObjectManager->get('Simi\Simiconnector\Helper\Simiproductlabel')->getPositionId(),
                ]
        );

        $fieldset->addField('priority', 'text', [
            'name'  => 'priority',
            'class' => 'validate-number',
            'label' => __('Priority'),
            'title' => __('Priority'),
            'note'  => __('The higher the value, the higher the priority.'),
        ]);

        $fieldset->addField(
            'text',
            'textarea',
            [
            'name'     => 'text',
            'label'    => __('Text'),
            'title'    => __('Text'),
            'required' => false
                ]
        );

        if (!isset($data['is_auto_fill']) || ($data['is_auto_fill'] == null)) {
            $data['is_auto_fill'] = 1;
        }

        $fieldset->addField(
            'product_ids',
            'text',
            [
            'name'               => 'product_ids',
            'label'              => __('Product ID(s)'),
            'title'              => __('Choose products'),
            'after_element_html' => '<a href="#" title="Show Product Grid" onclick="toogleProduct();return false;">'
                . '<img id="show_product_grid" src="'
                . $this->getViewFileUrl('Simi_Simiconnector::images/arrow_down.png')
                . '" title="" /></a>'
                . $this->getLayout()
                ->createBlock('Simi\Simiconnector\Block\Adminhtml\Simiproductlabel\Edit\Tab\Productgrid')->toHtml()
                ]
        );

        $this->_eventManager->dispatch('adminhtml_simiproductlabel_edit_tab_main_prepare_form', ['form' => $form]);

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
        return __('productlabel Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('productlabel Information');
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

<?php

namespace Simi\Simiconnector\Block\Adminhtml\Cms\Edit\Tab;

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
     * @var \Simi\Simiconnector\Model\Cms
     */
    public $cmsFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    public $jsonEncoder;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    public $categoryFactory;

    /**
     * @var \Magento\Cms\Model\Wysiwyg\ConfigFactory
     */
    public $wysiwygConfig;

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
        \Simi\Simiconnector\Model\CmsFactory $cmsFactory,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        array $data = []
    ) {
   
        $this->simiObjectManager = $simiObjectManager;
        $this->cmsFactory       = $cmsFactory;
        $this->websiteHelper     = $websiteHelper;
        $this->systemStore       = $systemStore;
        $this->jsonEncoder       = $jsonEncoder;
        $this->categoryFactory   = $categoryFactory;
        $this->wysiwygConfig    = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    public function _prepareForm()
    {

        $model = $this->_coreRegistry->registry('cms');

        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('Simi_Simiconnector::cms_save')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        $fieldset            = $form->addFieldset('base_fieldset', ['legend' => __('Cms Information')]);

        $data = $model->getData();
        if ($model->getId()) {
            $fieldset->addField('cms_id', 'hidden', ['name' => 'cms_id']);

            $simiconnectorhelper = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Data');
            $typeID              = $simiconnectorhelper->getVisibilityTypeId('cms');
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

        $fieldset->addField(
            'storeview_id',
            'multiselect',
            [
            'name'     => 'storeview_id[]',
            'label'    => __('Store View'),
            'title'    => __('Store View'),
            'required' => true,
            'values'   => $storeResourceModel->toOptionArray(),
                ]
        );

        $fieldset->addField(
            'cms_title',
            'text',
            ['name'     => 'cms_title',
            'label'    => __('Title'),
            'title'    => __('Title'),
            'required' => true,
            'disabled' => $isElementDisabled]
        );

        $fieldset->addField(
            'cms_content',
            'editor',
            [
            'name'     => 'cms_content',
            'label'    => __('Content'),
            'title'    => __('Content'),
            'required' => true,
            'style'    => 'height: 500px',
            'disabled' => $isElementDisabled,
            'config'   => $this->wysiwygConfig->getConfig()
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
            'cms_status',
            'select',
            [
            'name'     => 'cms_status',
            'label'    => __('Status'),
            'title'    => __('Status'),
            'required' => false,
            'disabled' => $isElementDisabled,
            'options'  => $this->cmsFactory->create()->toOptionStatusHash(),
                ]
        );

        $fieldset->addField(
            'type',
            'select',
            [
            'name'     => 'type',
            'label'    => __('Show Block On'),
            'title'    => __('Show Block On'),
            'required' => false,
            'disabled' => $isElementDisabled,
            'options'  => [
                '0' => __('Nowhere'),
                '1' => __('Left Menu'),
                '2' => __('Category In-app'),
            ],
            'onchange' => 'toogleType()'
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
            'cms_image',
            'image',
            [
            'name'     => 'cms_image',
            'label'    => __('Image (width:64px, height:64px)'),
            'title'    => __('Image (width:64px, height:64px)'),
            'required' => false,
            'disabled' => $isElementDisabled
                ]
        );

        $webappfieldset = $form->addFieldset('webapp_fieldset', ['legend' => __('PWA Configuration')]);

        $webappfieldset->addField(
            'cms_url',
            'text',
            ['name'     => 'cms_url',
                'label'    => __('Url'),
                'title'    => __('Url')]
        );
        
        $webappfieldset->addField(
            'cms_meta_title',
            'text',
            ['name'     => 'cms_meta_title',
                'label'    => __('Meta Title'),
                'title'    => __('Meta Title')]
        );

        $webappfieldset->addField(
            'cms_meta_desc',
            'text',
            ['name'     => 'cms_meta_desc',
                'label'    => __('Meta Description'),
                'title'    => __('Meta Description')]
        );

        $webappfieldset->addField(
            'cms_script',
            'editor',
            [
                'name'     => 'cms_script',
                'label'    => __('Script'),
                'title'    => __('Script')
            ]
        );

        $this->_eventManager->dispatch('adminhtml_cms_edit_tab_main_prepare_form', ['form' => $form]);

        $form->setValues($data);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return mixed
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setWysiwyg(true);
        $element->setConfig($this->wysiwygConfig->getConfig($element));
        return parent::_getElementHtml($element);
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Cms Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Cms Information');
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

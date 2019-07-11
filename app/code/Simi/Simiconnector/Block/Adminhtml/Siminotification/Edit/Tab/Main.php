<?php

namespace Simi\Simiconnector\Block\Adminhtml\Siminotification\Edit\Tab;

/**
 * Cms page edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    /**
     * @var \Magento\Store\Model\System\Store
     */
    public $systemStore;

    /**
     * @var \Simi\Simiconnector\Helper\Website
     * */
    public $websiteHelper;

    /**
     * @var \Simi\Simiconnector\Model\Siminotification
     */
    public $siminotificationFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    public $jsonEncoder;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    public $categoryFactory;
    public $simiObjectManager;

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
        \Simi\Simiconnector\Model\SiminotificationFactory $siminotificationFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        array $data = []
    ) {
   
        $this->simiObjectManager        = $simiObjectManager;
        $this->siminotificationFactory = $siminotificationFactory;
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
        
        $model = $this->_coreRegistry->registry('siminotification');
        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('Simi_Simiconnector::siminotification_save')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Siminotification Information')]);

        if ($model->getId()) {
            $fieldset->addField('notice_id', 'hidden', ['name' => 'notice_id']);
        }
        $data = $model->getData();

        if (!isset($data['storeview_id'])) {
            $storeviewCollection = $this->simiObjectManager
                    ->get('\Magento\Store\Model\Store')->getCollection()->setPageSize(1);
            foreach ($storeviewCollection as $storeview) {
                $data['storeview_id'] = $storeview->getId();
                break;
            }
        }
        $data['storeview_selected'] = $data['storeview_id'];
        $fieldset->addField(
            'storeview_selected',
            'select',
            [
            'name'     => 'storeview_selected',
            'label'    => __('Store View'),
            'title'    => __('Store View'),
            'required' => true,
            'disabled' => $isElementDisabled,
            'options'  => $this->siminotificationFactory->create()->toOptionStoreviewHash(),
            'onchange' => 'clearDevices()'
                ]
        );

        $fieldset->addField(
            'notice_sanbox',
            'select',
            [
            'name'     => 'notice_sanbox',
            'label'    => __('Send To'),
            'title'    => __('Send To'),
            'required' => true,
            'disabled' => $isElementDisabled,
            'options'  => $this->siminotificationFactory->create()->toOptionSanboxHash(),
                ]
        );
        if (isset($data['device_id'])) {
            $data['device_type'] = $data['device_id'];
        }
        $fieldset->addField(
            'device_type',
            'select',
            [
            'label'    => __('Device Type'),
            'title'    => __('Device Type'),
            'name'     => 'device_type',
            'disabled' => $isElementDisabled,
            'options'  => [
                0 => __('All'),
                1 => __('IOS'),
                2 => __('Android'),
            ],
                ]
        );

        $fieldset->addField(
            'show_popup',
            'select',
            [
            'name'     => 'show_popup',
            'label'    => __('Show Popup'),
            'title'    => __('Show Popup'),
            'required' => true,
            'disabled' => $isElementDisabled,
            'options'  => $this->siminotificationFactory->create()->toOptionPopupHash(),
                ]
        );

        $fieldset->addField(
            'notice_title',
            'text',
            [
            'name'     => 'notice_title',
            'label'    => __('Title'),
            'title'    => __('Title'),
            'required' => true,
            'disabled' => $isElementDisabled
                ]
        );

        $fieldset->addField(
            'image_url',
            'image',
            [
            'name'     => 'image_url',
            'label'    => __('Image'),
            'title'    => __('Image'),
            'required' => false,
            'disabled' => $isElementDisabled
                ]
        );

        $fieldset->addField(
            'notice_content',
            'textarea',
            [
            'name'     => 'notice_content',
            'label'    => __('Message'),
            'title'    => __('Message'),
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
            'options'  => $this->siminotificationFactory->create()->toOptionTypeHash(),
            'onchange' => 'changeType(this.value)',
                ]
        );

        $fieldset->addField(
            'product_id',
            'text',
            [
            'name'               => 'product_id',
            'label'              => __('Product ID'),
            'title'              => __('Product ID'),
            'required'           => true,
            'disabled'           => $isElementDisabled,
            'after_element_html' => '<a href="#" title="Show Product Grid" onclick="toogleProduct();return false;">'
                . '<img id="show_product_grid" src="'
                . $this->getViewFileUrl('Simi_Simiconnector::images/arrow_down.png')
                . '" title="" /></a>'
                . $this->getLayout()
                ->createBlock('Simi\Simiconnector\Block\Adminhtml\Siminotification\Edit\Tab\Productgrid')->toHtml()
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
            'notice_url',
            'textarea',
            [
            'name'     => 'notice_url',
            'label'    => __('Url'),
            'title'    => __('Url'),
            'required' => true,
            'disabled' => $isElementDisabled,
                ]
        );

        $fieldset->addField(
            'created_time',
            'label',
            [
            'name'     => 'created_time',
            'label'    => __('Created Date'),
            'title'    => __('Created Date'),
            'required' => false,
            'disabled' => $isElementDisabled,
                ]
        );

        $_fieldset = $form->addFieldset('device_location', ['legend' => __('Notification Device Select')]);

        $_fieldset->addField(
            'devices_pushed',
            'textarea',
            [
            'name'               => 'devices_pushed',
            'label'              => __('Device IDs'),
            'title'              => __('Device IDs'),
            'required'           => true,
            'disabled'           => $isElementDisabled,
            'after_element_html' => '<a href="#" title="Show Device Grid" onclick="toogleDevice();return false;">'
                . '<img id="show_device_grid" src="'
                . $this->getViewFileUrl('Simi_Simiconnector::images/arrow_down.png') . '" title="" /></a>'
                . $this->getLayout()
                ->createBlock('Simi\Simiconnector\Block\Adminhtml\Siminotification\Edit\Tab\Devicegrid')
                ->setStoreview($data['storeview_id'])->toHtml()
                ]
        );

        $this->_eventManager->dispatch('adminhtml_siminotification_edit_tab_main_prepare_form', ['form' => $form]);

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
        return __('Siminotification Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Siminotification Information');
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

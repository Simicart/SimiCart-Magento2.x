<?php

namespace Simi\Simiconnector\Block\Adminhtml\Device\Edit\Tab;

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
     * @var \Simi\Simiconnector\Model\Device
     */
    public $deviceFactory;

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
        \Simi\Simiconnector\Model\DeviceFactory $deviceFactory,
        \Simi\Simiconnector\Model\BannerFactory $bannerFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    ) {
   
        $this->deviceFactory  = $deviceFactory;
        $this->bannerFactory   = $bannerFactory;
        $this->websiteHelper   = $websiteHelper;
        $this->systemStore     = $systemStore;
        $this->jsonEncoder     = $jsonEncoder;
        $this->categoryFactory = $categoryFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    public function _prepareForm()
    {
        
        $model = $this->_coreRegistry->registry('device');

        /*
         * Checking if user have permissions to save information
         */
        $isElementDisabled = true;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Device Information')]);

        if (isset($model) && $model->getId()) {
            $fieldset->addField('device_id', 'hidden', ['name' => 'device_id']);
        }

        $fieldset->addField(
            'storeview_id',
            'select',
            [
            'name'     => 'storeview_id',
            'label'    => __('Store View'),
            'title'    => __('Store View'),
            'required' => false,
            'disabled' => $isElementDisabled,
            'options'  => $this->deviceFactory->create()->toOptionStoreviewHash(),
                ]
        );

        $fieldset->addField(
            'plaform_id',
            'select',
            [
            'name'     => 'plaform_id',
            'label'    => __('Device Type'),
            'title'    => __('Device Type'),
            'required' => false,
            'disabled' => $isElementDisabled,
            'options'  => $this->deviceFactory->create()->toOptionDeviceHash(),
                ]
        );

        $fieldset->addField(
            'country',
            'label',
            [
            'name'     => 'country',
            'label'    => __('Country'),
            'title'    => __('Country'),
            'required' => false,
            'disabled' => $isElementDisabled,
                ]
        );

        $fieldset->addField(
            'state',
            'label',
            [
            'name'     => 'state',
            'label'    => __('State/Province'),
            'title'    => __('State/Province'),
            'required' => false,
            'disabled' => $isElementDisabled,
                ]
        );

        $fieldset->addField(
            'city',
            'label',
            [
            'name'     => 'city',
            'label'    => __('City'),
            'title'    => __('City'),
            'required' => false,
            'disabled' => $isElementDisabled,
                ]
        );

        $fieldset->addField(
            'device_token',
            'label',
            [
            'name'     => 'device_token',
            'label'    => __('Device Token'),
            'title'    => __('Device Token'),
            'required' => false,
            'disabled' => $isElementDisabled,
                ]
        );

        $fieldset->addField(
            'is_demo',
            'select',
            [
            'name'     => 'is_demo',
            'label'    => __('Is Demo'),
            'title'    => __('Is Demo'),
            'required' => false,
            'disabled' => $isElementDisabled,
            'options'  => $this->deviceFactory->create()->toOptionDemoHash(),
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

        $this->_eventManager->dispatch('adminhtml_device_edit_tab_main_prepare_form', ['form' => $form]);

        $form->setValues($model->getData());
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
        return __('Device Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Device Information');
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

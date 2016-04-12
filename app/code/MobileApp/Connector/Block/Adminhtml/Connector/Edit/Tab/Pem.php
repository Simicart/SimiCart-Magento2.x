<?php
namespace MobileApp\Connector\Block\Adminhtml\Connector\Edit\Tab;

/**
 * connector edit form main tab
 */
class Pem extends \Magento\Backend\Block\Widget\Form\Generic implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Initialise form fields
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(['data' => ['html_id_prefix' => 'connector_image_']]);

        $model = $this->_coreRegistry->registry('app');

        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('MobileApp_Connector::save')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        $layoutFieldset = $form->addFieldset(
            'pem_fieldset',
            ['legend' => __('Upload PEM file'), 'class' => 'fieldset-wide', 'disabled' => $isElementDisabled]
        );

        $layoutFieldset->addField(
            'image',
            'file',
            [
                'name' => 'image',
                'label' => __('Upload PEM file'),
                'title' => __('Upload PEM file'),
                'required'  => false,
                'disabled' => $isElementDisabled,
                'note' => 'PEM file has been uploaded . It use to send notification to IOS',
            ]
        );

        $this->_eventManager->dispatch('adminhtml_connector_edit_tab_pem_prepare_form', ['form' => $form]);

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
        return __('Upload PEM file');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Upload PEM file');
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
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}

<?php
namespace MobileApp\Connector\Block\Adminhtml\Connector\Edit\Tab;
/**
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Category extends \Magento\Backend\Block\Widget\Form\Generic
{
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
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    )
    {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_categoryFactory = $categoryFactory;
        parent::__construct($context, $registry, $formFactory, $data);
        $this->setUseContainer(true);
    }

    /**
     * Form preparation
     *
     * @return void
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(['data' => []]);

        $model = $this->_coreRegistry->registry('app');
        $model->setData('device_id',$this->getRequest()->getParam('device_id'));
        $model->setData('website_id',$this->getRequest()->getParam('website_id'));
        $model->setData('category_ids',explode(',',$model->getData('categories')));

        /*
        * Checking if user have permissions to save information
        */
        if ($this->_isAllowedAction('MobileApp_Connector::save')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        $fieldset = $form->addFieldset(
            'category_fieldset',
            ['legend' => __('Categories displayed on App'), 'class' => 'fieldset-wide', 'disabled' => $isElementDisabled]
        );

        $fieldset->addField(
            'new_category_parent',
            'select',
            [
                'label' => __('Categories'),
                'title' => __('Categories'),
                'required' => false,
                'class' => 'validate-parent-category',
                'name' => 'new_category_parent',
            ]
        );

        $this->setForm($form);
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

<?php
namespace MobileApp\Connector\Block\Adminhtml\Banner\Edit\Tab;

/**
 * Cms page edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \MobileApp\Connector\Helper\Website
     **/
    protected $_websiteHelper;

    /**
     * @var \MobileApp\Connector\Model\Banner
     */
    protected $_bannerFactory;

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
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \MobileApp\Connector\Helper\Website $websiteHelper,
        \MobileApp\Connector\Model\BannerFactory $bannerFactory,

        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    )
    {
        $this->_bannerFactory = $bannerFactory;
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
    protected function _prepareForm()
    {
        /* @var $model \Magento\Cms\Model\Page */
        $model = $this->_coreRegistry->registry('banner');

        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('MobileApp_Connector::banner_save')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Banner Information')]);

        $new_category_parent = false;
        if ($model->getId()) {
            $fieldset->addField('banner_id', 'hidden', ['name' => 'banner_id']);
            $new_category_parent = $model->getData('category_id');
        }

        $fieldset->addField(
            'website_id',
            'select',
            [
                'name' => 'website_id',
                'label' => __('Website'),
                'title' => __('Website'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'options' => $this->_bannerFactory->create()->toOptionWebsiteHash(),
            ]
        );

        $fieldset->addField(
            'banner_title',
            'text',
            [
                'name' => 'banner_title',
                'label' => __('Title'),
                'title' => __('Title'),
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'banner_name',
            'image',
            [
                'name' => 'banner_name',
                'label' => __('Image (width:640px, height:180px)'),
                'title' => __('Image (width:640px, height:180px)'),
                'required' => false,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Status'),
                'title' => __('Status'),
                'required' => false,
                'disabled' => $isElementDisabled,
                'options' => $this->_bannerFactory->create()->toOptionStatusHash(),
            ]
        );

        $fieldset->addField(
            'type',
            'select',
            [
                'name' => 'type',
                'label' => __('Direct viewers to'),
                'title' => __('Direct viewers to'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'options' => $this->_bannerFactory->create()->toOptionTypeHash(),
                'onchange' => 'changeType(this.value)',
            ]
        );

        /* product + category + url */
        $fieldset->addField(
            'product_id',
            'text',
            [
                'name' => 'product_id',
                'label' => __('Product ID'),
                'title' => __('Product ID'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'after_element_html' => '<a href="#" title="Show Product Grid" onclick="toogleProduct();return false;"><img id="show_product_grid" src="'.$this->getViewFileUrl('MobileApp_Connector::images/arrow_down.png').'" title="" /></a>'.$this->getLayout()->createBlock('MobileApp\Connector\Block\Adminhtml\Banner\Edit\Tab\Productgrid')->toHtml()
            ]
        );

        $fieldset->addField(
            'new_category_parent',
            'select',
            [
                'label' => __('Categories'),
                'title' => __('Categories'),
                'required' => true,
                'class' => 'validate-parent-category',
                'name' => 'new_category_parent',
                'options' => $this->_getParentCategoryOptions($new_category_parent),
            ]
        );

        $fieldset->addField(
            'banner_url',
            'textarea',
            [
                'name' => 'banner_url',
                'label' => __('Url'),
                'title' => __('Url'),
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );
        /* product + category + url */

        $this->_eventManager->dispatch('adminhtml_banner_edit_tab_main_prepare_form', ['form' => $form]);

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Get parent category options
     *
     * @return array
     */
    protected function _getParentCategoryOptions($category_id)
    {

        $items = $this->_categoryFactory->create()->getCollection()->addAttributeToSelect(
            'name'
        )->addAttributeToSort(
            'entity_id',
            'ASC'
        )->setPageSize(
            3
        )->load()->getItems();

        $result = [];
        if (count($items) === 2) {
            $item = array_pop($items);
            $result = [$item->getEntityId() => $item->getName()];
        }

        if(sizeof($result) == 0 && $category_id){
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
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

}

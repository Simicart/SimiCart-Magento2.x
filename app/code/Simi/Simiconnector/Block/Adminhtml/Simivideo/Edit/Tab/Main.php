<?php

namespace Simi\Simiconnector\Block\Adminhtml\Simivideo\Edit\Tab;

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
     * @var \Simi\Simiconnector\Model\Simivideo
     */
    public $simivideoFactory;

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
        \Simi\Simiconnector\Model\SimivideoFactory $simivideoFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    ) {
   
        $this->simiObjectManager = $simiObjectManager;
        $this->simivideoFactory = $simivideoFactory;
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
        
        $model = $this->_coreRegistry->registry('simivideo');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Video Information')]);

        $data = $model->getData();
        if ($model->getId()) {
            $fieldset->addField('video_id', 'hidden', ['name' => 'video_id']);
        }

        $fieldset->addField(
            'status',
            'select',
            [
            'name'     => 'status',
            'label'    => __('Status'),
            'title'    => __('Status'),
            'required' => false,
            'options'  => $this->simivideoFactory->create()->toOptionStatusHash(),
                ]
        );

        $fieldset->addField(
            'video_title',
            'text',
            [
            'name'     => 'video_title',
            'label'    => __('Title'),
            'title'    => __('Title'),
            'required' => true
                ]
        );

        $fieldset->addField(
            'video_url',
            'text',
            [
            'name'     => 'video_url',
            'label'    => __('Youtube Video URL'),
            'note'     => __('Example: https://www.youtube.com/watch?v=AfgX7GB_Rkc'),
            'required' => true
                ]
        );

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
                . $this->getLayout()->createBlock('Simi\Simiconnector\Block\Adminhtml\Simivideo\Edit\Tab\Productgrid')
                ->toHtml()
                ]
        );

        $this->_eventManager->dispatch('adminhtml_simivideo_edit_tab_main_prepare_form', ['form' => $form]);

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
        return __('Video Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Video Information');
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

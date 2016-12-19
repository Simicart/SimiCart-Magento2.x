<?php
namespace Simi\Simiconnector\Block\Adminhtml\Productlist\Edit\Tab;

/**
 * Cms page edit form main tab
 */
class Matrix extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected  $_objectManager;
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Simi\Simiconnector\Helper\Website
     **/
    protected $_websiteHelper;

    /**
     * @var \Simi\Simiconnector\Model\Productlist
     */
    protected $_productlistFactory;

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
        \Simi\Simiconnector\Helper\Website $websiteHelper,
        \Simi\Simiconnector\Model\ProductlistFactory $productlistFactory,

        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    )
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_productlistFactory = $productlistFactory;
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
        $model = $this->_coreRegistry->registry('productlist');

        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('Simi_Simiconnector::productlist_save')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        $data = $model->getData();
        
        $matrixfieldset = $form->addFieldset('productlist_matrix', ['legend' => __('Matrix Layout Config')]);
  
        if (!isset($data['matrix_width_percent']))
            $data['matrix_width_percent'] = 100;
        if (!isset($data['matrix_height_percent']))
            $data['matrix_height_percent'] = 30;
        if (!isset($data['matrix_width_percent_tablet']))
            $data['matrix_width_percent_tablet'] = 100;
        if (!isset($data['matrix_height_percent_tablet']))
            $data['matrix_height_percent_tablet'] = 30;
        if (!isset($data['matrix_row']))
            $data['matrix_row'] = 1;
        
        $matrixfieldset->addField(
            'matrix_width_percent', 'text', [
            'name' => 'matrix_width_percent',
            'label' => __('Image Width/ Screen Width Ratio'),
            'note' => __('With Screen Height is 100%'),
            'disabled' => $isElementDisabled,
            ]
        );
        
        $matrixfieldset->addField(
            'matrix_height_percent', 'text', [
            'name' => 'matrix_height_percent',
            'label' => __('Image Height/ Screen Width Ratio'),
            'note' => __('With Screen Height is 100%'),
            'disabled' => $isElementDisabled,
            ]
        );
        
        $matrixfieldset->addField(
            'matrix_width_percent_tablet', 'text', [
            'name' => 'matrix_width_percent_tablet',
            'label' => __('Tablet Image Width/Screen Width Ratio'),
            'note' => __('Leave it empty if you want to use Phone Value'),
            'disabled' => $isElementDisabled,
            ]
        );
        
        $matrixfieldset->addField(
            'matrix_height_percent_tablet', 'text', [
            'name' => 'matrix_height_percent_tablet',
            'label' => __('Tablet Image Height/Screen Width Ratio'),
            'note' => __('Leave it empty if you want to use Phone Value'),
            'disabled' => $isElementDisabled,
            ]
        );      

        
        $matrixfieldset->addField(
            'matrix_row', 'select', [
            'name' => 'matrix_row',
            'label' => __('Row Number'),
            'options' => $this->_objectManager->create('Simi\Simiconnector\Helper\Productlist')->getMatrixRowOptions(),
            'disabled' => $isElementDisabled,
            ]
        );
        
        foreach ($this->_objectManager->create('\Magento\Store\Model\Store')->getCollection() as $storeView) {
            if (!isset($data['storeview_scope']))
                $data['storeview_scope'] = $storeView->getId();
            $storeviewArray[$storeView->getId()] = $storeView->getName();
        }
        
        $matrixfieldset->addField(
            'storeview_scope', 'select', [
            'name' => 'storeview_scope',
            'label' => __('Storeview for Mockup Preview'),
            'options' => $storeviewArray,
            'disabled' => $isElementDisabled,
            'onchange' => 'updateMockupPreview(this.value)',
            'after_element_html' => '</br><div id="mockuppreview" style="text-align:center"></div> <script>
            ' . $this->_objectManager->create('Simi\Simiconnector\Helper\Productlist')->autoFillMatrixRowHeight() . '
            function updateMockupPreview(storeview){
                var urlsend = "' . $this->getUrl("*/*/getmockup") . '?storeview_id=" + storeview;
                xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                  if (xhttp.readyState == 4 && xhttp.status == 200) {
                    document.getElementById("mockuppreview").innerHTML = xhttp.responseText;
                  }
                };
                xhttp.open("GET", urlsend, true);
                xhttp.send();
            }
            updateMockupPreview(\'' . $data['storeview_scope'] . '\');</script>',
            ]
        );
        
        $this->_eventManager->dispatch('adminhtml_productlist_edit_tab_matrix_prepare_form', ['form' => $form]);

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
        return __('Matrix Layout Config');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Matrix Layout Config');
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

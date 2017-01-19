<?php

namespace Simi\Simiconnector\Block\Adminhtml\Cms\Edit\Tab;

/**
 * Cms page edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface{

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $_objectManager;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Simi\Simiconnector\Helper\Website
     * */
    protected $_websiteHelper;

    /**
     * @var \Simi\Simiconnector\Model\Cms
     */
    protected $_cmsFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var \Magento\Cms\Model\Wysiwyg\ConfigFactory
     */
    protected $_wysiwygConfig;

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
                array $data = []
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_cmsFactory = $cmsFactory;
        $this->_websiteHelper = $websiteHelper;
        $this->_systemStore = $systemStore;
        $this->_jsonEncoder = $jsonEncoder;
        $this->_categoryFactory = $categoryFactory;
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm() {
        
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

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Cms Information')]);
        $new_category_parent = false;

        $data = $model->getData();
        if ($model->getId()) {
            $fieldset->addField('cms_id', 'hidden', ['name' => 'cms_id']);
            $new_category_parent = $model->getData('category_id');

            $simiconnectorhelper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');
            $typeID = $simiconnectorhelper->getVisibilityTypeId('cms');
            $visibleStoreViews = $this->_objectManager->create('Simi\Simiconnector\Model\Visibility')->getCollection()
                    ->addFieldToFilter('content_type', $typeID)
                    ->addFieldToFilter('item_id', $model->getId());
            $storeIdArray = array();

            foreach ($visibleStoreViews as $visibilityItem) {
                $storeIdArray[] = $visibilityItem->getData('store_view_id');
            }
            $data['storeview_id'] = implode(',', $storeIdArray);
        }

        $storeResourceModel = $this->_objectManager->get('Simi\Simiconnector\Model\ResourceModel\Storeviewmultiselect');

        $fieldset->addField('storeview_id',
          'multiselect',
          array(
            'name' => 'storeview_id[]',
            'label' => __('Store View'),
            'title' => __('Store View'),
            'required' => true,
            'values' => $storeResourceModel->toArray(),
          )
        );


        $fieldset->addField(
            'cms_title', 
            'text',
            ['name' => 'cms_title',
            'label' => __('Title'),
            'title' => __('Title'),
            'required' => true,
            'disabled' => $isElementDisabled]
        );

        $fieldset->addField(
                'cms_content', 'editor', [
            'name' => 'cms_content',
            'label' => __('Content'),
            'title' => __('Content'),
            'required' => true,
            'style' => 'height: 500px',
            'disabled' => $isElementDisabled,
            'config' => $this->_wysiwygConfig->getConfig()
                ]
        );

        if (!isset($data['sort_order']))
            $data['sort_order'] = 1;
        $fieldset->addField(
                'sort_order', 'text', [
            'name' => 'sort_order',
            'label' => __('Sort Order'),
            'title' => __('Sort Order'),
            'class' => 'validate-not-negative-number',
            'disabled' => $isElementDisabled
                ]
        );

        $fieldset->addField(
                'cms_status', 'select', [
            'name' => 'cms_status',
            'label' => __('Status'),
            'title' => __('Status'),
            'required' => false,
            'disabled' => $isElementDisabled,
            'options' => $this->_cmsFactory->create()->toOptionStatusHash(),
                ]
        );


        $fieldset->addField(
                'type', 'select', [
            'name' => 'type',
            'label' => __('Show Block On'),
            'title' => __('Show Block On'),
            'required' => false,
            'disabled' => $isElementDisabled,
            'options' => array(
                '1' => __('Left Menu'),
                '2' => __('Category In-app'),
            ),
            'onchange' => 'toogleType()'
                ]
        );

        /*
          $fieldset->addField(
          'category_id',
          'select',
          [
          'label' => __('Category ID'),
          'title' => __('Category ID'),
          'required' => true,
          'class' => 'validate-parent-category',
          'name' => 'new_category_parent',
          'options' => $this->_getParentCategoryOptions($new_category_parent),
          ]
          );
         */
        $fieldset->addField(
                'new_category_parent', 'select', [
            'label' => __('Categories'),
            'title' => __('Categories'),
            'class' => 'validate-parent-category',
            'name' => 'new_category_parent',
            'options' => $this->_getParentCategoryOptions($new_category_parent),
                ]
        );



        $fieldset->addField(
                'cms_image', 'image', [
            'name' => 'cms_image',
            'label' => __('Image (width:64px, height:64px)'),
            'title' => __('Image (width:64px, height:64px)'),
            'required' => false,
            'disabled' => $isElementDisabled
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
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element) {
        $element->setWysiwyg(true);
        $element->setConfig($this->_wysiwygConfig->getConfig($element));
        return parent::_getElementHtml($element);
    }

    /**
     * Get parent category options
     *
     * @return array
     */
    protected function _getParentCategoryOptions($category_id) {

        $items = $this->_categoryFactory->create()->getCollection()->addAttributeToSelect(
                        'name'
                )->addAttributeToSort(
                        'entity_id', 'ASC'
                )->setPageSize(
                        3
                )->load()->getItems();

        $result = [];
        if (count($items) === 2) {
            $item = array_pop($items);
            $result = [$item->getEntityId() => $item->getName()];
        }

        if (sizeof($result) == 0 && $category_id) {
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
    public function getTabLabel() {
        return __('Cms Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle() {
        return __('Cms Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden() {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId) {
        return true;
        //return $this->_authorization->isAllowed($resourceId);
    }

}

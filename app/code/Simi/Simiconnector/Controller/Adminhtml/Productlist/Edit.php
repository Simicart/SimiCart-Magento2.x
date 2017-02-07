<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Productlist;

use Magento\Backend\App\Action;

class Edit extends \Magento\Backend\App\Action
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    public $coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry
    ) {
    
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry     = $registry;
        parent::__construct($context);
    }
    
    /**
     * Init actions
     *
     * @return $this
     */
    private function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(
            'Simi_Simiconnector::simiconnector_manage'
        )->addBreadcrumb(
            __('Home Product List'),
            __('Home Product List')
        )->addBreadcrumb(
            __('Manage Product Lists'),
            __('Manage Product Lists')
        );
        return $resultPage;
    }

    /**
     * Edit CMS page
     *
     * @return void
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id    = $this->getRequest()->getParam('productlist_id');
        $simiObjectManager = $this->_objectManager;
        $model = $simiObjectManager->create('Simi\Simiconnector\Model\Productlist');

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This productlist no longer exists.'));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        // 3. Set entered data if was error when we do save
        $data = $simiObjectManager->get('Magento\Backend\Model\Session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        // 4. Register model to use later in blocks
        $this->coreRegistry->register('productlist', $model);

        // 5. Build edit form
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Productlist') : __('New Home Product List'),
            $id ? __('Edit Productlist') : __('New Home Product List')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Product List'));
        $resultPage->getConfig()->getTitle()
                ->prepend($model->getId() ? $model->getId() : __('New Home Product List'));
        return $resultPage;
    }
}

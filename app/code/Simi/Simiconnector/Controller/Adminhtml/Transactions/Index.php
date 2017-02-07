<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Transactions;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
   
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }
    
    /**
     * Connector List action
     *
     * @return void
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(
            'Simi_Simiconnector::simiconnector_manage'
        )->addBreadcrumb(
            __('App Transactions'),
            __('App Transactions')
        )->addBreadcrumb(
            __('Manage App Transactions'),
            __('Manage App Transactions')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('App Transactions'));
        return $resultPage;
    }
}

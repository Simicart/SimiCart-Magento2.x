<?php

namespace MobileApp\Connector\Controller\Adminhtml\Notice;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
	/**
     * @var PageFactory
     */
    protected $resultPageFactory;

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
     * Check the permission to run it
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MobileApp_Connector::notice_save');
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
            'MobileApp_Connector::connector_manage'
        )->addBreadcrumb(
            __('Notification'),
            __('Notification')
        )->addBreadcrumb(
            __('Manage Notification'),
            __('Manage Notification')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Notification'));
        return $resultPage;
    }
}

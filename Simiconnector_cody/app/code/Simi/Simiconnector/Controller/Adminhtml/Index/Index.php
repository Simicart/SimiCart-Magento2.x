<?php

namespace Simi\Simisimiconnector\Controller\Adminhtml\Index;

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
        return $this->_authorization->isAllowed('Simi_Simisimiconnector::simiconnector_manage');
    }

    /**
     * Simisimiconnector List action
     *
     * @return void
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(
            'Simi_Simisimiconnector::simiconnector_manage'
        )->addBreadcrumb(
            __('Simisimiconnector'),
            __('Simisimiconnector')
        )->addBreadcrumb(
            __('Manage Simisimiconnector'),
            __('Manage Simisimiconnector')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Simisimiconnector'));
        return $resultPage;
    }
}

<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Simiproductlabel;

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
        return true;
        //return $this->_authorization->isAllowed('Simi_Simiconnector::simiproductlabel_save');
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
            'Simi_Simiconnector::connector_manage'
        )->addBreadcrumb(
            __('productlabel'),
            __('productlabel')
        )->addBreadcrumb(
            __('Manage productlabel'),
            __('Manage productlabel')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Product Labels'));
        return $resultPage;
    }
}

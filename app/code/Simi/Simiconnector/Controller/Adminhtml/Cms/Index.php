<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Cms;

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
            'Simi_Simiconnector::connector_manage'
        )->addBreadcrumb(
            __('Cms'),
            __('Cms')
        )->addBreadcrumb(
            __('Manage Cms'),
            __('Manage Cms')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Cms'));
        return $resultPage;
    }
}

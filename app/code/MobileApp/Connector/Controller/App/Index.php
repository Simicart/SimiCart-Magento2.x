<?php

namespace MobileApp\Connector\Controller\App;

use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /** @var \MobileApp\Connector\Helper\Data */
    protected $_dataHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Default Connector Index page
     *
     * @return void
     */
    public function execute()
    {
        $data_helper = $this->_objectManager->get('MobileApp\Connector\Helper\Data');
        $data_helper->importDesgin();
        $data_helper->importApp();
        echo 'success';

        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('App Demo'));
        $listBlock = $this->_view->getLayout()->getBlock('connector.app');

        if ($listBlock) {
            $currentPage = abs(intval($this->getRequest()->getParam('p')));
            if ($currentPage < 1) {
                $currentPage = 1;
            }

            $listBlock->setCurrentPage($currentPage);
        }
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        return $resultPage;
    }
}

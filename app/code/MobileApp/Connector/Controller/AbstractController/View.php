<?php

namespace MobileApp\Connector\Controller\AbstractController;

use Magento\Framework\App\Action;
use Magento\Framework\View\Result\PageFactory;

abstract class View extends Action\Action
{
    /**
     * @var \MobileApp\Connector\Controller\AbstractController\ConnectorLoaderInterface
     */
    protected $connectorLoader;
	
	/**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param OrderLoaderInterface $orderLoader
	 * @param PageFactory $resultPageFactory
     */
    public function __construct(Action\Context $context, ConnectorLoaderInterface $connectorLoader, PageFactory $resultPageFactory)
    {
        $this->connectorLoader = $connectorLoader;
		$this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Connector view page
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->connectorLoader->load($this->_request, $this->_response)) {
            return;
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
		return $resultPage;
    }
}

<?php

namespace Simi\Simisimiconnector\Controller\AbstractController;

use Magento\Framework\App\Action;
use Magento\Framework\View\Result\PageFactory;

abstract class View extends Action\Action
{
    /**
     * @var \Simi\Simisimiconnector\Controller\AbstractController\SimisimiconnectorLoaderInterface
     */
    protected $simiconnectorLoader;
	
	/**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param OrderLoaderInterface $orderLoader
	 * @param PageFactory $resultPageFactory
     */
    public function __construct(Action\Context $context, SimisimiconnectorLoaderInterface $simiconnectorLoader, PageFactory $resultPageFactory)
    {
        $this->simiconnectorLoader = $simiconnectorLoader;
		$this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Simisimiconnector view page
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->simiconnectorLoader->load($this->_request, $this->_response)) {
            return;
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
		return $resultPage;
    }
}

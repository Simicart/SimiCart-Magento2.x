<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Productlist;

use Magento\Backend\App\Action;

class Getmockup extends \Magento\Backend\App\Action
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
     * Edit CMS page
     *
     * @return void
     */
    public function execute()
    {
        $simiObjectManager = $this->_objectManager;
        $storeviewid = $this->getRequest()->getParam('storeview_id');
        $output      = $simiObjectManager
                ->create('Simi\Simiconnector\Helper\Productlist')
                ->getMatrixLayoutMockup($storeviewid, $this);
        return $this->getResponse()->setBody($output);
    }
}

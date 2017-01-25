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
    protected $_coreRegistry = null;
    
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \Magento\Framework\Registry $registry)
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return true;
        //return $this->_authorization->isAllowed('Simi_Simiconnector::save');
    }

    
    /**
     * Edit CMS page
     *
     * @return void
     */
    public function execute()
    {

        $storeviewid = $this->getRequest()->getParam('storeview_id');
        $output= $this->_objectManager->create('Simi\Simiconnector\Helper\Productlist')->getMatrixLayoutMockup($storeviewid, $this);
        return $this->getResponse()->setBody($output);
    }
}

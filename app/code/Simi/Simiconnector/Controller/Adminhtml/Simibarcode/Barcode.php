<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Simibarcode;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Barcode extends \Magento\Backend\App\Action
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
	
    protected function _isAllowed()
    {
        return true;
    }

    public function execute()
    {   
        $code = $this->getRequest()->getParam('code');
        $type = $this->getRequest()->getParam('type');
        $this->_objectManager->get('Simi\Simiconnector\Helper\Simibarcode')->createBarcode(null, $code, "100", "horizontal", $type, false);
    }
}

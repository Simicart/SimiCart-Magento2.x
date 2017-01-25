<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Simibarcode;


use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    
    protected $_objectManager;
    protected $filter;
    
    public function __construct(
        Context $context,
        Filter $filterObject
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->filter = $filterObject;
        parent::__construct($context);
    }

    public function execute()
    {
        $barcodeIds = $this->getRequest()->getParam('massaction');
        $collection = $this->_objectManager->get('Simi\Simiconnector\Model\Simibarcode')
                ->getCollection()->addFieldToFilter('barcode_id', array('in', $barcodeIds));
        $barcodeDeleted = 0;
        foreach ($collection->getItems() as $barcode) {
            $barcode->delete();
            $barcodeDeleted++;
        }
        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been deleted.', $barcodeDeleted)
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}

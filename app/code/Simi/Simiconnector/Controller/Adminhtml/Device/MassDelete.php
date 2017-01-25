<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Device;

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
        $deviceIds = $this->getRequest()->getParam('massaction');
        $collection = $this->_objectManager->get('Simi\Simiconnector\Model\Device')
                ->getCollection()->addFieldToFilter('device_id', ['in', $deviceIds]);
        $deviceDeleted = 0;
        foreach ($collection->getItems() as $device) {
            $device->delete();
            $deviceDeleted++;
        }
        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been deleted.', $deviceDeleted)
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}

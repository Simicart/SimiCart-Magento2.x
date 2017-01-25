<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Simivideo;


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
        $videoIds = $this->getRequest()->getParam('massaction');
        $collection = $this->_objectManager->get('Simi\Simiconnector\Model\Simivideo')
                ->getCollection()->addFieldToFilter('video_id', array('in', $videoIds));
        $videoDeleted = 0;
        foreach ($collection->getItems() as $video) {
            $video->delete();
            $videoDeleted++;
        }
        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been deleted.', $videoDeleted)
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}

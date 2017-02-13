<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Simiproductlabel;

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
    public $simiObjectManager;
    public $filter;

    public function __construct(
        Context $context,
        Filter $filterObject
    ) {
        $this->simiObjectManager = $context->getObjectManager();
        $this->filter            = $filterObject;
        parent::__construct($context);
    }

    public function execute()
    {
        $productlabelIds     = $this->getRequest()->getParam('massaction');
        $collection          = $this->simiObjectManager->get('Simi\Simiconnector\Model\Simiproductlabel')
                        ->getCollection()->addFieldToFilter('label_id', ['in', $productlabelIds]);
        $productlabelDeleted = 0;
        foreach ($collection->getItems() as $productlabel) {
            $this->simiObjectManager
                            ->get('Simi\Simiconnector\Helper\Data')->deleteModel($productlabel);
            $productlabelDeleted++;
        }
        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been deleted.', $productlabelDeleted)
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}

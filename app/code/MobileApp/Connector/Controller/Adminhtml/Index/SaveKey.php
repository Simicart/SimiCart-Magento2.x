<?php

namespace MobileApp\Connector\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;

class SaveKey extends \Magento\Backend\App\Action
{
    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @param Action\Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(Action\Context $context, PostDataProcessor $dataProcessor)
    {

        $this->dataProcessor = $dataProcessor;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MobileApp_Connector::saveKey');
    }

    /**
     * Save action
     *
     * @return void
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $data = $this->dataProcessor->filter($data);
            $key = $data['key_app'];
            $websiteId = $data['website_id'];

            try {
                $modelApi = $this->_objectManager->create('MobileApp\Connector\Model\Simicart\Api');
                $modelApp = $this->_objectManager->create('MobileApp\Connector\Model\App');
                $modelPlugin = $this->_objectManager->create('MobileApp\Connector\Model\Plugin');
                $modelKey = $this->_objectManager->create('MobileApp\Connector\Model\Key');

                $app_list = $modelApi->getListApp($key);

                if ($app_list->status == "FAIL") {
                    $modelApp->deleteList($websiteId);
                    $modelPlugin->deleteList($websiteId);
                    $modelKey->setKey($key, $websiteId);
                    $this->messageManager->addError(__('Authorize secret key is incorrect'));
                } else {
                    $modelApp->saveList($app_list, $websiteId);
                    $modelPlugin->deleteList($websiteId);
                    $modelPlugin->saveList($app_list, $websiteId);
                    $modelKey->setKey($key, $websiteId);

                    $this->messageManager->addSuccess(__('Authorize secret key is correct'));
                }
            } catch (Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');
    }
}

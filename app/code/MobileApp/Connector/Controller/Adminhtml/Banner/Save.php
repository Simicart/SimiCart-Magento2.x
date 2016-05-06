<?php

namespace MobileApp\Connector\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action;

class Save extends \Magento\Backend\App\Action
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
        return $this->_authorization->isAllowed('MobileApp_Connector::banner_save');
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
            $device_id = $data['device_id'];
            $web_id = $data['website_id'];


            $model = $this->_objectManager->create('MobileApp\Connector\Model\Banner');
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
            }

            if(isset($data['category_ids'])){
                $categories = implode(',', array_unique($data['category_ids']));
                $model->saveCategories($web_id, $categories);
            }

            if (!$this->dataProcessor->validate($data)) {
                $this->_redirect('*/*/edit', [
                    'id' => $model->getId(),
                    '_current' => true,
                    'device_id' => $device_id,
                    'website_id' => $web_id,
                ]);
                return;
            }

            try {
                $dataHelper = $this->_objectManager->get('MobileApp\Connector\Helper\Data');

                // check pem file for iOs
                if (isset($_FILES['pem']) && isset($_FILES['pem']['name']) && strlen($_FILES['pem']['name'])) {
                    $dataHelper->savePem($_FILES['pem']);
                }
                // check Androi key
                if (isset($data['android_key']) && $data['android_key']) {
                    $dataHelper->saveAndroidConfigData($data['android_key'], $data['android_sendid']);
                }

                $model->save();
                $this->messageManager->addSuccess(__('The Data has been saved.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', [
                        'id' => $model->getId(),
                        '_current' => true,
                        'device_id' => $device_id,
                        'website_id' => $web_id,
                    ]);
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (\Magento\Framework\Model\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                var_dump($e->getMessage());exit;
                $this->messageManager->addException($e, __('Something went wrong while saving the data.'));
            }

            $this->_getSession()->setFormData($data);
            $this->_redirect('*/*/edit', [
                'id' => $model->getId(),
                '_current' => true,
                'device_id' => $model->getDeviceId(),
                'website_id' => $model->getWebsiteId(),
            ]);
            return;
        }
        $this->_redirect('*/*/');
    }
}

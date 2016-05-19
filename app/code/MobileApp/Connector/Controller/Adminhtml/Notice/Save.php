<?php

namespace MobileApp\Connector\Controller\Adminhtml\Notice;

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
        return $this->_authorization->isAllowed('MobileApp_Connector::notice_save');
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
            $model = $this->_objectManager->create('MobileApp\Connector\Model\Notice');

            $id = $this->getRequest()->getParam('notice_id');
            if ($id) {
                $model->load($id);
            }
            if(isset($data['new_category_parent']))
                $data['category_id'] = $data['new_category_parent'];

            $is_delete_notice = isset($data['image_url']['delete']) ? $data['image_url']['delete'] : false;
            $data['image_url'] = isset($data['image_url']['value']) ? $data['image_url']['value'] : '';
            $data['created_at'] = time();
            $model->addData($data);

            if (!$this->dataProcessor->validate($data)) {
                $this->_redirect('*/*/edit', ['notice_id' => $model->getId(), '_current' => true]);
                return;
            }

            try {
                $imageHelper = $this->_objectManager->get('MobileApp\Connector\Helper\Data');
                if ($is_delete_notice && $model->getImageUrl()) {
                    $model->setImageUrl('');
                } else {
                    $imageFile = $imageHelper->uploadImage('image_url','notice');
                    if ($imageFile) {
                        $model->setImageUrl($imageFile);
                    }
                }

                $model->save();
                $this->messageManager->addSuccess(__('The Data has been saved.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['notice_id' => $model->getId(), '_current' => true]);
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (\Magento\Framework\Model\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the data.'));
            }

            $this->_getSession()->setFormData($data);
            $this->_redirect('*/*/edit', ['notice_id' => $this->getRequest()->getParam('notice_id')]);
            return;
        }
        $this->_redirect('*/*/');
    }
}

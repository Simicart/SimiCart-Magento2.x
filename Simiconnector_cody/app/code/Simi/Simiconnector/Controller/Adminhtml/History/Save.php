<?php

namespace Simi\Simiconnector\Controller\Adminhtml\History;

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
        return $this->_authorization->isAllowed('Simi_Simiconnector::history_save');
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
            $model = $this->_objectManager->create('Simi\Simiconnector\Model\History');

            $id = $this->getRequest()->getParam('history_id');
            if ($id) {
                $model->load($id);
            }

            $is_delete_history = isset($data['history_name']['delete']) ? $data['history_name']['delete'] : false;
            $data['history_name'] = isset($data['history_name']['value']) ? $data['history_name']['value'] : '';
            $model->addData($data);

            if (!$this->dataProcessor->validate($data)) {
                $this->_redirect('*/*/edit', ['history_id' => $model->getId(), '_current' => true]);
                return;
            }

            try {
                $imageHelper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');
                if ($is_delete_history && $model->getBannerName()) {
                    $model->setBannerName('');
                } else {
                    $imageFile = $imageHelper->uploadImage('history_name','history');
                    if ($imageFile) {
                        $model->setBannerName($imageFile);
                    }
                }

                $model->save();
                $this->messageManager->addSuccess(__('The Data has been saved.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['history_id' => $model->getId(), '_current' => true]);
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
            $this->_redirect('*/*/edit', ['history_id' => $this->getRequest()->getParam('history_id')]);
            return;
        }
        $this->_redirect('*/*/');
    }
}

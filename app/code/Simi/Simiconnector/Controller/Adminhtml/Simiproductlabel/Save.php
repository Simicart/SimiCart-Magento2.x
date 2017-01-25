<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Simiproductlabel;

use Magento\Backend\App\Action;

class Save extends \Magento\Backend\App\Action {

    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @param Action\Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(Action\Context $context, PostDataProcessor $dataProcessor) {
        $this->dataProcessor = $dataProcessor;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed() {
        return true;
    }

    /**
     * Save action
     *
     * @return void
     */
    public function execute() {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $data = $this->dataProcessor->filter($data);
            $model = $this->_objectManager->create('Simi\Simiconnector\Model\Simiproductlabel');

            $id = $this->getRequest()->getParam('label_id');
            if ($id) {
                $model->load($id);
            }
            
            $is_delete_productlabel = isset($data['image']['delete']) ? $data['image']['delete'] : false;
            $data['image'] = isset($data['image']['value']) ? $data['image']['value'] : '';

            $model->addData($data);
            $model->setData('name',$data['label_name']);

            if (!$this->dataProcessor->validate($data)) {
                $this->_redirect('*/*/edit', ['label_id' => $model->getId(), '_current' => true]);
                return;
            }

            try {
                $imageHelper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');
                if ($is_delete_productlabel && $model->getImage()) {
                    $model->setListImage('');
                } else {
                    $imageFile = $imageHelper->uploadImage('image', 'productlabel');
                    if ($imageFile) {
                        $model->setImage($imageFile);
                    }
                }
                $model->save();
                $this->messageManager->addSuccess(__('The Data has been saved.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['label_id' => $model->getId(), '_current' => true]);
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
            $this->_redirect('*/*/edit', ['label_id' => $this->getRequest()->getParam('label_id')]);
            return;
        }
        $this->_redirect('*/*/');
    }

}

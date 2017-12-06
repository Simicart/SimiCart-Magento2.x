<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Simiproductlabel;

use Magento\Backend\App\Action;

class Save extends \Magento\Backend\App\Action
{
    /**
     * Save action
     *
     * @return void
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $simiObjectManager = $this->_objectManager;
            $model = $simiObjectManager->create('Simi\Simiconnector\Model\Simiproductlabel');

            $id = $this->getRequest()->getParam('label_id');
            if ($id) {
                $model->load($id);
            }

            $is_delete_productlabel = isset($data['image']['delete']) ? $data['image']['delete'] : false;
            $data['image']          = isset($data['image']['value']) ? $data['image']['value'] : '';

            $model->addData($data);
            $model->setData('name', $data['label_name']);

            try {
                $imageHelper = $simiObjectManager->get('Simi\Simiconnector\Helper\Data');
                if ($is_delete_productlabel && $model->getImage()) {
                    $model->setImage('');
                } else {
                    $imageFile = $imageHelper->uploadImage('image', 'productlabel');
                    if ($imageFile) {
                        $model->setImage($imageFile);
                    }
                }
                $model->save();
                $this->messageManager->addSuccess(__('The Data has been saved.'));
                $simiObjectManager->get('Magento\Backend\Model\Session')->setFormData(false);
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

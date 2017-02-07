<?php

namespace Simi\Simiconnector\Controller\Adminhtml\History;

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
            $model = $simiObjectManager->create('Simi\Simiconnector\Model\History');

            $id = $this->getRequest()->getParam('history_id');
            if ($id) {
                $model->load($id);
            }

            $is_delete_history    = isset($data['history_name']['delete']) ? $data['history_name']['delete'] : false;
            $data['history_name'] = isset($data['history_name']['value']) ? $data['history_name']['value'] : '';
            $model->addData($data);

            try {
                $imageHelper = $simiObjectManager->get('Simi\Simiconnector\Helper\Data');
                if ($is_delete_history && $model->getBannerName()) {
                    $model->setBannerName('');
                } else {
                    $imageFile = $imageHelper->uploadImage('history_name', 'history');
                    if ($imageFile) {
                        $model->setBannerName($imageFile);
                    }
                }

                $model->save();
                $this->messageManager->addSuccess(__('The Data has been saved.'));
                $simiObjectManager->get('Magento\Backend\Model\Session')->setFormData(false);
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

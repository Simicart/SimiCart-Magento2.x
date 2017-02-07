<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Device;

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
            $model = $simiObjectManager->create('Simi\Simiconnector\Model\Device');

            $id = $this->getRequest()->getParam('device_id');
            if ($id) {
                $model->load($id);
            }

            $is_delete_device    = isset($data['device_name']['delete']) ? $data['device_name']['delete'] : false;
            $data['device_name'] = isset($data['device_name']['value']) ? $data['device_name']['value'] : '';
            $model->addData($data);

            try {
                $imageHelper = $simiObjectManager->get('Simi\Simiconnector\Helper\Data');
                if ($is_delete_device && $model->getBannerName()) {
                    $model->setBannerName('');
                } else {
                    $imageFile = $imageHelper->uploadImage('device_name', 'device');
                    if ($imageFile) {
                        $model->setBannerName($imageFile);
                    }
                }

                $model->save();
                $this->messageManager->addSuccess(__('The Data has been saved.'));
                $simiObjectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['device_id' => $model->getId(), '_current' => true]);
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
            $this->_redirect('*/*/edit', ['device_id' => $this->getRequest()->getParam('device_id')]);
            return;
        }
        $this->_redirect('*/*/');
    }
}

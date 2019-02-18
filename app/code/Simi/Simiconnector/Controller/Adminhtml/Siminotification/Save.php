<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Siminotification;

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
        $simiObjectManager = $this->_objectManager;
        $model = $simiObjectManager->create('Simi\Simiconnector\Model\Siminotification');

        $id = $this->getRequest()->getParam('notice_id');
        if ($id) {
            $model->load($id);
        }

        $is_delete_siminotification = isset($data['image_url']['delete']) ? $data['image_url']['delete'] : false;
        $data['image_url']          = isset($data['image_url']['value']) ? $data['image_url']['value'] : '';
        $data['created_time']       = time();
        $data['device_id']          = $data['device_type'];
        $data['storeview_id']       = $data['storeview_selected'];
        $model->addData($data);

        try {
            $imageHelper = $simiObjectManager->get('Simi\Simiconnector\Helper\Data');
            if ($is_delete_siminotification && $model->getImageUrl()) {
                $model->setImageUrl('');
            } else {
                $imageFile = $imageHelper->uploadImage('image_url', 'siminotification');
                if ($imageFile) {
                    $model->setImageUrl($imageFile);
                }
            }
            $model->save();
            $this->messageManager->addSuccess(__('The Data has been saved.'));
            $simiObjectManager->get('Magento\Backend\Model\Session')->setFormData(false);

            if ($this->getRequest()->getParam('back')) {
                $this->_redirect('*/*/edit', ['notice_id' => $model->getId(), '_current' => true]);
                return;
            } else {
                $data['siminotification_type'] = 0;
                $data['notice_type']           = 0;
                $data['notice_id']             = $model->getId();
                if ($model->getImageUrl()) {
                    try {
                        $img_full_url = $imageHelper->getBaseUrl(false) . $model->getImageUrl();
                        $list              = getimagesize($img_full_url);
                        $data['width']     = $list[0];
                        $data['height']    = $list[1];
                    } catch (\Exception $e) {}
                }
                $resultSend = $simiObjectManager
                        ->get('Simi\Simiconnector\Helper\Siminotification')->sendNotice($data);
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
    }
}

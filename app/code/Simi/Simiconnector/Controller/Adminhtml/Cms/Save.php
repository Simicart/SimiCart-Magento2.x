<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Cms;

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
            $model = $simiObjectManager->create('Simi\Simiconnector\Model\Cms');

            $id = $this->getRequest()->getParam('cms_id');
            if ($id) {
                $model->load($id);
            }

            $is_delete_banner  = isset($data['cms_image']['delete']) ? $data['cms_image']['delete'] : false;
            $data['cms_image'] = isset($data['cms_image']['value']) ? $data['cms_image']['value'] : '';
            $model->addData($data);

            try {
                $imageHelper = $simiObjectManager->get('Simi\Simiconnector\Helper\Data');
                if ($is_delete_banner && $model->getCmsImage()) {
                    $model->setCmsImage('');
                } else {
                    $imageFile = $imageHelper->uploadImage('cms_image', 'cms');
                    if ($imageFile) {
                        $model->setCmsImage($imageFile);
                    }
                }

                $model->save();
                $this->updateVisibility($simiObjectManager, $model, $data);

                $this->messageManager->addSuccess(__('The Data has been saved.'));
                $simiObjectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['cms_id' => $model->getId(), '_current' => true]);
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
            $this->_redirect('*/*/edit', ['cms_id' => $this->getRequest()->getParam('cms_id')]);
            return;
        }
        $this->_redirect('*/*/');
    }
    
    private function updateVisibility($simiObjectManager, $model, $data)
    {
        $simiconnectorhelper = $simiObjectManager->get('Simi\Simiconnector\Helper\Data');
        if ($data['storeview_id'] && is_array($data['storeview_id'])) {
            $typeID            = $simiconnectorhelper->getVisibilityTypeId('cms');
            $visibleStoreViews = $simiObjectManager
                    ->create('Simi\Simiconnector\Model\Visibility')->getCollection()
                    ->addFieldToFilter('content_type', $typeID)
                    ->addFieldToFilter('item_id', $model->getId());
            foreach ($visibleStoreViews as $visibilityItem) {
                $simiObjectManager
                    ->get('Simi\Simiconnector\Helper\Data')->deleteModel($visibilityItem);
            }
            foreach ($data['storeview_id'] as $storeViewId) {
                $visibilityItem = $simiObjectManager->create('Simi\Simiconnector\Model\Visibility');
                $visibilityItem->setData('content_type', $typeID);
                $visibilityItem->setData('item_id', $model->getId());
                $visibilityItem->setData('store_view_id', $storeViewId);
                $simiObjectManager
                    ->get('Simi\Simiconnector\Helper\Data')->saveModel($visibilityItem);
            }
        }
    }
}

<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Banner;

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
        $model = $simiObjectManager->create('Simi\Simiconnector\Model\Banner');

        $id = $this->getRequest()->getParam('banner_id');
        if ($id) {
            $model->load($id);
        }
        $data['category_id'] = isset($data['category_id'])?$data['category_id']:0;

        $is_delete_banner    = isset($data['banner_name']['delete']) ? $data['banner_name']['delete'] : false;
        $data['banner_name'] = isset($data['banner_name']['value']) ? $data['banner_name']['value'] : '';
        $is_delete_banner_tablet = isset($data['banner_name_tablet']['delete']) ?
                $data['banner_name_tablet']['delete'] : false;
        $data['banner_name_tablet'] = isset($data['banner_name_tablet']['value']) ?
                $data['banner_name_tablet']['value'] : '';
        $model->addData($data);

        try {
            $imageHelper = $simiObjectManager->get('Simi\Simiconnector\Helper\Data');
            if ($is_delete_banner && $model->getBannerName()) {
                $model->setBannerName('');
            } else {
                $imageFile = $imageHelper->uploadImage('banner_name', 'banner');
                if ($imageFile) {
                    $model->setBannerName($imageFile);
                }
            }
            if ($is_delete_banner_tablet && $model->getBannerNameTablet()) {
                $model->setBannerNameTablet('');
            } else {
                $imageFileTablet = $imageHelper->uploadImage('banner_name_tablet', 'banner');
                if ($imageFileTablet) {
                    $model->setBannerNameTablet($imageFileTablet);
                }
            }
            $model->save();
            $this->updateVisibility($simiObjectManager, $model, $data);
            $this->messageManager->addSuccess(__('The Data has been saved.'));
            $simiObjectManager->get('Magento\Backend\Model\Session')->setFormData(false);
            $simiObjectManager->get('Simi\Simiconnector\Helper\Data')->flushStaticCache();
            if ($this->getRequest()->getParam('back')) {
                $this->_redirect('*/*/edit', ['banner_id' => $model->getId(), '_current' => true]);
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
        $this->_redirect('*/*/edit', ['banner_id' => $this->getRequest()->getParam('banner_id')]);
    }
    
    private function updateVisibility($simiObjectManager, $model, $data)
    {
        $simiconnectorhelper = $simiObjectManager->get('Simi\Simiconnector\Helper\Data');
        if ($data['storeview_id'] && is_array($data['storeview_id'])) {
            $typeID            = $simiconnectorhelper->getVisibilityTypeId('banner');
            $visibleStoreViews = $simiObjectManager
                    ->create('Simi\Simiconnector\Model\Visibility')->getCollection()
                    ->addFieldToFilter('content_type', $typeID)
                    ->addFieldToFilter('item_id', $model->getId());
            $visibleStoreViews->walk('delete');
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

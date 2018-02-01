<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Simicategory;

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
        $model = $simiObjectManager->create('Simi\Simiconnector\Model\Simicategory');

        $id = $this->getRequest()->getParam('simicategory_id');
        if ($id) {
            $model->load($id);
        }
        if (isset($data['category_id'])) {
            $cat                 = $simiObjectManager
                    ->create('Magento\Catalog\Model\Category')->load($data['category_id']);
            $data['simicategory_name'] = $cat->getName();
        }

        $is_delete_simicategory        = isset($data['simicategory_filename']['delete']) ?
        $data['simicategory_filename']['delete'] : false;
        $data['simicategory_filename'] = isset($data['simicategory_filename']['value']) ?
        $data['simicategory_filename']['value'] : '';

        $is_delete_simicategory_tablet        = isset($data['simicategory_filename_tablet']['delete']) ?
        $data['simicategory_filename_tablet']['delete'] : false;
        $data['simicategory_filename_tablet'] = isset($data['simicategory_filename_tablet']['value']) ?
        $data['simicategory_filename_tablet']['value'] : '';

        $model->addData($data);

        try {
            $imageHelper = $simiObjectManager->get('Simi\Simiconnector\Helper\Data');
            if ($is_delete_simicategory && $model->getSimicategoryFilename()) {
                $model->setSimicategoryFilename('');
            } else {
                $imageFile = $imageHelper->uploadImage('simicategory_filename', 'simicategory');
                if ($imageFile) {
                    $model->setSimicategoryFilename($imageFile);
                }
            }
            if ($is_delete_simicategory_tablet && $model->getSimicategoryFilenameTablet()) {
                $model->setSimicategoryFilenameTablet('');
            } else {
                $imageFiletablet = $imageHelper->uploadImage('simicategory_filename_tablet', 'simicategory');
                if ($imageFiletablet) {
                    $model->setSimicategoryFilenameTablet($imageFiletablet);
                }
            }
            $model->setData('storeview_id', null);
            $model->save();
            $this->updateVisibility($simiObjectManager, $model, $data);

            $this->messageManager->addSuccess(__('The Data has been saved.'));
            $simiObjectManager->get('Magento\Backend\Model\Session')->setFormData(false);

            $simiObjectManager->get('Simi\Simiconnector\Helper\Data')->flushStaticCache();
            if ($this->getRequest()->getParam('back')) {
                $this->_redirect('*/*/edit', ['simicategory_id' => $model->getId(), '_current' => true]);
                return;
            }
            $this->_redirect('*/*/');
            return;
        } catch (\Magento\Framework\Model\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Something went wrong while saving the data.'));
        }

        $this->_getSession()->setFormData($data);
        $this->_redirect('*/*/edit', ['simicategory_id' => $this->getRequest()->getParam('simicategory_id')]);
    }
    
    private function updateVisibility($simiObjectManager, $model, $data)
    {
        $simiconnectorhelper = $simiObjectManager->get('Simi\Simiconnector\Helper\Data');
        if ($data['storeview_id'] && is_array($data['storeview_id'])) {
            $typeID            = $simiconnectorhelper->getVisibilityTypeId('homecategory');
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

<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Cms;

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
        return $this->_authorization->isAllowed('Simi_Simiconnector::cms_save');
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
            $model = $this->_objectManager->create('Simi\Simiconnector\Model\Cms');

            $id = $this->getRequest()->getParam('cms_id');
            if ($id) {
                $model->load($id);
            }

            $is_delete_banner = isset($data['cms_image']['delete']) ? $data['cms_image']['delete'] : false;
            $data['cms_image'] = isset($data['cms_image']['value']) ? $data['cms_image']['value'] : '';
            $model->addData($data);

            if (!$this->dataProcessor->validate($data)) {
                $this->_redirect('*/*/edit', ['cms_id' => $model->getId(), '_current' => true]);
                return;
            }

            try {
                $imageHelper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');
                if ($is_delete_banner && $model->getCmsImage()) {
                    $model->setCmsImage('');
                } else {
                    $imageFile = $imageHelper->uploadImage('cms_image','cms');
                    if ($imageFile) {
                        $model->setCmsImage($imageFile);
                    }
                }
                if (isset($data['new_category_parent']))
                    $model->setData('category_id',$data['new_category_parent']);
                $model->save();
                $simiconnectorhelper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');                
                if ($data['storeview_id'] && is_array($data['storeview_id'])) {
                    $typeID = $simiconnectorhelper->getVisibilityTypeId('cms');
                    $visibleStoreViews = $this->_objectManager->create('Simi\Simiconnector\Model\Visibility')->getCollection()
                            ->addFieldToFilter('content_type', $typeID)
                            ->addFieldToFilter('item_id', $model->getId());
                    foreach ($visibleStoreViews as $visibilityItem) {
                        $visibilityItem->delete();
                    }
                    foreach ($data['storeview_id'] as $storeViewId){
                        $visibilityItem = $this->_objectManager->create('Simi\Simiconnector\Model\Visibility');
                        $visibilityItem->setData('content_type',$typeID);                        
                        $visibilityItem->setData('item_id',$model->getId());
                        $visibilityItem->setData('store_view_id',$storeViewId);
                        $visibilityItem->save();
                    }                        
                }
                 
                
                $this->messageManager->addSuccess(__('The Data has been saved.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
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
}

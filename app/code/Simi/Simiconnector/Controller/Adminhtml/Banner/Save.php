<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Banner;

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
        return true;
        //return $this->_authorization->isAllowed('Simi_Simiconnector::banner_save');
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
            $model = $this->_objectManager->create('Simi\Simiconnector\Model\Banner');

            $id = $this->getRequest()->getParam('banner_id');
            if ($id) {
                $model->load($id);
            }
            if (isset($data['new_category_parent'])) {
                $data['category_id'] = $data['new_category_parent'];
            }

            
            $is_delete_banner = isset($data['banner_name']['delete']) ? $data['banner_name']['delete'] : false;
            $data['banner_name'] = isset($data['banner_name']['value']) ? $data['banner_name']['value'] : '';
            
            $is_delete_banner_tablet = isset($data['banner_name_tablet']['delete']) ? $data['banner_name_tablet']['delete'] : false;
            $data['banner_name_tablet'] = isset($data['banner_name_tablet']['value']) ? $data['banner_name_tablet']['value'] : '';
            
            $model->addData($data);
            
            if (!$this->dataProcessor->validate($data)) {
                $this->_redirect('*/*/edit', ['banner_id' => $model->getId(), '_current' => true]);
                return;
            }
            try {
                $imageHelper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');
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
                    if ($imageFile) {
                        $model->setBannerNameTablet($imageFileTablet);
                    }
                }
                $model->save();
                
                $simiconnectorhelper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');
                if ($data['storeview_id'] && is_array($data['storeview_id'])) {
                    $typeID = $simiconnectorhelper->getVisibilityTypeId('banner');
                    $visibleStoreViews = $this->_objectManager->create('Simi\Simiconnector\Model\Visibility')->getCollection()
                            ->addFieldToFilter('content_type', $typeID)
                            ->addFieldToFilter('item_id', $model->getId());
                    // foreach ($visibleStoreViews as $visibilityItem) {
                    //     $visibilityItem->delete();
                    // }
                    $visibleStoreViews->walk('delete');
                    foreach ($data['storeview_id'] as $storeViewId) {
                        $visibilityItem = $this->_objectManager->create('Simi\Simiconnector\Model\Visibility');
                        $visibilityItem->setData('content_type', $typeID);
                        $visibilityItem->setData('item_id', $model->getId());
                        $visibilityItem->setData('store_view_id', $storeViewId);
                        $visibilityItem->save();
                    }
                }
                
                $this->messageManager->addSuccess(__('The Data has been saved.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                
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
            return;
        }
        $this->_redirect('*/*/');
    }
}

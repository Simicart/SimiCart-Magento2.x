<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Simicategory;

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
    public function __construct(
        Action\Context $context,
        PostDataProcessor $dataProcessor
    )
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
        //return $this->_authorization->isAllowed('Simi_Simiconnector::simicategory_save');
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
            $model = $this->_objectManager->create('Simi\Simiconnector\Model\Simicategory');

            $id = $this->getRequest()->getParam('simicategory_id');
            if ($id) {
                $model->load($id);
            }
            if(isset($data['new_category_parent'])){
                $data['category_id'] = $data['new_category_parent'];
                $cat = $this->_objectManager->create('Magento\Catalog\Model\Category')->load($data['category_id']);
                if($cat->getId()){
                    $data['simicategory_name'] = $cat->getName();
                }
            }

            $is_delete_simicategory = isset($data['simicategory_filename']['delete']) ? $data['simicategory_filename']['delete'] : false;
            $data['simicategory_filename'] = isset($data['simicategory_filename']['value']) ? $data['simicategory_filename']['value'] : '';

            $is_delete_simicategory_tablet = isset($data['simicategory_filename_tablet']['delete']) ? $data['simicategory_filename_tablet']['delete'] : false;
            $data['simicategory_filename_tablet'] = isset($data['simicategory_filename_tablet']['value']) ? $data['simicategory_filename_tablet']['value'] : '';

            
            $model->addData($data);

            if (!$this->dataProcessor->validate($data)) {
                $this->_redirect('*/*/edit', ['simicategory_id' => $model->getId(), '_current' => true]);
                return;
            }

            try {
                $imageHelper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');
                if ($is_delete_simicategory && $model->getSimicategoryFilename()) {
                    $model->setSimicategoryFilename('');
                } else {
                    $imageFile = $imageHelper->uploadImage('simicategory_filename','simicategory');
                    if ($imageFile) {
                        $model->setSimicategoryFilename($imageFile);
                    }
                }
                
                if ($is_delete_simicategory_tablet && $model->getSimicategoryFilename()) {
                    $model->setSimicategoryFilenameTablet('');
                } else {
                    $imageFiletablet = $imageHelper->uploadImage('simicategory_filename_tablet','simicategory');
                    if ($imageFiletablet) {
                        $model->setSimicategoryFilenameTablet($imageFiletablet);
                    }
                }
                $model->setData('storeview_id',null);
                $model->save();
                
                $simiconnectorhelper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');                
                if ($data['storeview_id'] && is_array($data['storeview_id'])) {
                    $typeID = $simiconnectorhelper->getVisibilityTypeId('homecategory');
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
                    $this->_redirect('*/*/edit', ['simicategory_id' => $model->getId(), '_current' => true]);
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
            $this->_redirect('*/*/edit', ['simicategory_id' => $this->getRequest()->getParam('simicategory_id')]);
            return;
        }
        $this->_redirect('*/*/');
    }
}

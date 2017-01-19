n<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Productlist;

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
        //return $this->_authorization->isAllowed('Simi_Simiconnector::productlist_save');
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
            $model = $this->_objectManager->create('Simi\Simiconnector\Model\Productlist');

            $id = $this->getRequest()->getParam('productlist_id');
            if ($id) {
                $model->load($id);
            }
            if(isset($data['new_category_parent'])){
                $data['category_id'] = $data['new_category_parent'];
                $cat = $this->_objectManager->create('Magento\Catalog\Model\Category')->load($data['category_id']);
                if($cat->getId()){
                    $data['productlist_name'] = $cat->getName();
                }
            }

            $is_delete_productlist = isset($data['list_image']['delete']) ? $data['list_image']['delete'] : false;
            $data['list_image'] = isset($data['list_image']['value']) ? $data['list_image']['value'] : '';

            $is_delete_productlist_tablet = isset($data['list_image_tablet']['delete']) ? $data['list_image_tablet']['delete'] : false;
            $data['list_image_tablet'] = isset($data['list_image_tablet']['value']) ? $data['list_image_tablet']['value'] : '';

            
            $model->addData($data);

            if (!$this->dataProcessor->validate($data)) {
                $this->_redirect('*/*/edit', ['productlist_id' => $model->getId(), '_current' => true]);
                return;
            }

            try {
                
                $imageHelper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');
                if ($is_delete_productlist && $model->getListImage()) {
                    $model->setListImage('');
                } else {
                    $imageFile = $imageHelper->uploadImage('list_image','productlist');
                    if ($imageFile) {
                        $model->setListImage($imageFile);
                    }
                }
                if ($is_delete_productlist_tablet && $model->setListImageTablet()) {
                    $model->setListImageTablet('');
                } else {
                    $imageFiletablet = $imageHelper->uploadImage('list_image_tablet','productlist');
                    if ($imageFiletablet) {
                        $model->setListImageTablet($imageFiletablet);
                    }
                }
                $model->setData('storeview_id',null);
                $model->save();
                
                $simiconnectorhelper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');                
                if ($data['storeview_id'] && is_array($data['storeview_id'])) {
                    $typeID = $simiconnectorhelper->getVisibilityTypeId('productlist');
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
                    $this->_redirect('*/*/edit', ['productlist_id' => $model->getId(), '_current' => true]);
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
            $this->_redirect('*/*/edit', ['productlist_id' => $this->getRequest()->getParam('productlist_id')]);
            return;
        }
        $this->_redirect('*/*/');
    }
}

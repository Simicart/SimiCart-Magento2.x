<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Simivideo;

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
            $model = $simiObjectManager->create('Simi\Simiconnector\Model\Simivideo');

            $id = $this->getRequest()->getParam('video_id');
            if ($id) {
                $model->load($id);
            }
            $model->addData($data);

            $url              = $model->getData('video_url');
            $my_array_of_vars = [];
            $parse_str = 'parse_str';
            $parse_url = 'parse_url';
            $parse_str($parse_url($url, PHP_URL_QUERY), $my_array_of_vars);
            if (isset($my_array_of_vars['v'])) {
                $model->setData('video_key', $my_array_of_vars['v']);
            } else {
                $this->messageManager->addError(__('The url you used is not a Full and Valid Youtube video url'));
                $model->setData('video_key', null);
            }
            
            try {
                $model->save();
                $this->messageManager->addSuccess(__('The Data has been saved.'));
                $simiObjectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['video_id' => $model->getId(), '_current' => true]);
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
            $this->_redirect('*/*/edit', ['video_id' => $this->getRequest()->getParam('video_id')]);
            return;
        }
        $this->_redirect('*/*/');
    }
}

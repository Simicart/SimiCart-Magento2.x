<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Siminotification;

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
        return $this->_authorization->isAllowed('Simi_Simiconnector::siminotification_save');
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
            $model = $this->_objectManager->create('Simi\Simiconnector\Model\Siminotification');

            $id = $this->getRequest()->getParam('siminotification_id');
            if ($id) {
                $model->load($id);
            }
            if(isset($data['new_category_parent']))
                $data['category_id'] = $data['new_category_parent'];

            $is_delete_siminotification = isset($data['image_url']['delete']) ? $data['image_url']['delete'] : false;
            $data['image_url'] = isset($data['image_url']['value']) ? $data['image_url']['value'] : '';
            $data['created_at'] = time();
            $model->addData($data);

            if (!$this->dataProcessor->validate($data)) {
                $this->_redirect('*/*/edit', ['siminotification_id' => $model->getId(), '_current' => true]);
                return;
            }

            try {
                $imageHelper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');
                if ($is_delete_siminotification && $model->getImageUrl()) {
                    $model->setImageUrl('');
                } else {
                    $imageFile = $imageHelper->uploadImage('image_url','siminotification');
                    if ($imageFile) {
                        $model->setImageUrl($imageFile);
                    }
                }

                $model->save();
                $this->messageManager->addSuccess(__('The Data has been saved.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);


                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['siminotification_id' => $model->getId(), '_current' => true]);
                    return;
                } else {
                    if($model->getImageUrl()){
                        $data['image_url'] = $imageHelper->getBaseUrl(false).$model->getImageUrl();
                        $list = getimagesize($data['image_url']);
                        $data['width'] = $list[0];
                        $data['height'] = $list[1];
                    }

                    $data['siminotification_type'] = 0;

                    $resultSend = $this->sendSiminotification($data);
                }
                $this->_redirect('*/*/');
                return;
            } catch (\Magento\Framework\Model\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                var_dump($e->getMessage());exit;
                $this->messageManager->addException($e, __('Something went wrong while saving the data.'));
            }

            $this->_getSession()->setFormData($data);
            $this->_redirect('*/*/edit', ['siminotification_id' => $this->getRequest()->getParam('siminotification_id')]);
            return;
        }
        $this->_redirect('*/*/');
    }

    /**
     * @param $data
     * @return mixed
     */
    public function sendSiminotification($data) {
        $trans = $this->send($data);
        // update notification history
        $history = $this->_objectManager->create('Simi\Simiconnector\Model\History');
        if(!$trans)
            $data['status'] = 0;
        else
            $data['status'] = 1;
        $collectionDevice = $data['collection_device'];
        foreach ($collectionDevice as $item) {
            if (($data['website_id']== null) || (($item->getWebsiteId()) && ($data['website_id']== $item->getWebsiteId())))
                $data['simiconnector_siminotification_history'].= $item->getId().',';
        }
        $history->setData($data);
        $history->save();
        return $trans;
    }

    /**
     * @param $data
     * @return bool
     */
    public function send(&$data) {
        if($data['category_id']){
            $categoryId = $data['category_id'];
            $category = $this->_objectManager->create('Magento\Catalog\Model\Category')->load($categoryId);
            $categoryChildrenCount = $category->getChildrenCount();
            $categoryName = $category->getName();
            $data['category_name'] = $categoryName;
            if($categoryChildrenCount > 0)
                $categoryChildrenCount = 1;
            else
                $categoryChildrenCount = 0;
            $data['has_child'] = $categoryChildrenCount;
            if(!$data['has_child']){
                $data['has_child'] = '';
            }
        }
        if($data['product_id']){
            $productId = $data['product_id'];
            $productName = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($productId)->getName();
            $data['product_name'] = $productName;
        }
        $website = $data['website_id'];
        $collectionDevice = $this->_objectManager->create('Simi\Simiconnector\Model\Device')->getCollection();
        $collectionDevice2 = $this->_objectManager->create('Simi\Simiconnector\Model\Device')->getCollection();

        if ($data['country'] != "0") {
            $country_id = trim($data['country']);
            $collectionDevice->addFieldToFilter('country', array('like' => '%' . $data['country'] . '%'));
            $collectionDevice2->addFieldToFilter('country', array('like' => '%' . $data['country'] . '%'));
        }
        if (isset($data['state']) && ($data['state'] != null)) {
            $city = trim($data['city']);
            $collectionDevice->addFieldToFilter('state', array('like' => '%' . $data['state'] . '%'));
            $collectionDevice2->addFieldToFilter('state', array('like' => '%' . $data['state'] . '%'));
        }
        if (isset($data['address']) && ($data['address'] != null)) {
            $collectionDevice->addFieldToFilter('address', $data['address']);
            $collectionDevice2->addFieldToFilter('address', $data['address']);
        }
        if (isset($data['city']) && ($data['city'] != null)) {
            $collectionDevice->addFieldToFilter('city', array('like' => '%' . $data['city'] . '%'));
            $collectionDevice2->addFieldToFilter('city', array('like' => '%' . $data['city'] . '%'));
        }
        if (isset($data['zipcode']) && ($data['zipcode'] != null)) {
            $collectionDevice->addFieldToFilter('zipcode', array('like' => '%' . $data['zipcode'] . '%'));
            $collectionDevice2->addFieldToFilter('zipcode', array('like' => '%' . $data['zipcode'] . '%'));
        }
        switch ($data['siminotification_sanbox']) {
            case '1': $sendLive = 0; $sendTest = 1; $collectionDevice->addFieldToFilter('is_demo',1); break;
            case '2': $sendLive = 1; $sendTest = 0; $collectionDevice->addFieldToFilter('is_demo',0); break;
            default: $sendLive = 1; $sendTest = 1;
        }
        $data['collection_device'] = $collectionDevice;

        if ((int) $data['device_id'] != 0) {
            if ((int) $data['device_id'] == 2) {
                //send android
                $collectionDevice->addFieldToFilter('plaform_id', array('eq' => 3));
                return $this->sendAndroid($collectionDevice, $data);
            } else {
                //send IOS
                $collectionDevice->addFieldToFilter('plaform_id', array('neq' => 3));
                return $this->sendIOS($collectionDevice, $data);
            }
        } else {
            //send all
            $collection = $collectionDevice->addFieldToFilter('website_id', array('eq' => $website));
            $collectionAndroid = $collectionDevice2->addFieldToFilter('website_id', array('eq' => $website));

            $collection->addFieldToFilter('plaform_id', array('neq' => 3));
            $collectionAndroid->addFieldToFilter('plaform_id', array('eq' => 3));

            $resultIOS = $this->sendIOS($collection, $data);
            $resultAndroid = $this->sendAndroid($collectionAndroid, $data);
            if ($resultIOS || $resultAndroid)
                return true;
            else
                return false;
        }
    }

    /**
     * @param $collectionDevice
     * @param $data
     * @return bool
     */
    public function sendIOS($collectionDevice, $data) {
        $helper = $this->_objectManager->get('Simi\Simiconnector\Helper\Data');
        $ch = $helper->getDirPEMfile();
        $dir = $helper->getDirPEMPassfile();
        $message = $data['siminotification_content'];
        $body['aps'] = array(
            'alert' => $data['siminotification_title'],
            'sound' => 'default',
            'badge' => 1,
            'title' => $data['siminotification_title'],
            'message' => $message,
            'url' => $data['siminotification_url'],
            'type' => $data['type'],
            'productID' => $data['product_id'],
            'categoryID' => $data['category_id'],
            'categoryName' => $data['category_name'],
            'has_child'  => $data['has_child'],
            'imageUrl'   => $data['image_url'],
            'height'     => $data['height'],
            'width'     => $data['width'],
            'show_popup'   => $data['show_popup'],
        );
        $payload = json_encode($body);
        $totalDevice = 0;
        if ($data['siminotification_sanbox'] == '0') { //send the old way
            foreach ($collectionDevice as $item) {
                $ctx = stream_context_create();
                stream_context_set_option($ctx, 'ssl', 'local_cert', $ch);
                //$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
                $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
                if (!$fp) {
                    Mage::getSingleton('adminhtml/session')->addError("Failed to connect:" . $err . $errstr . PHP_EOL . "(IOS)");
                    return;
                }
                $deviceToken = $item->getDeviceToken();
                $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
                // Send it to the server
                $result = fwrite($fp, $msg, strlen($msg));
                if (!$result) {
                    $this->messageManager->addError(__('Message not delivered (IOS)' . PHP_EOL));
                    return false;
                }
                $totalDevice++;
                fclose($fp);
            }
        }
        else {
            $ctx = stream_context_create();
            stream_context_set_option($ctx, 'ssl', 'local_cert', $ch);
            //$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
            $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
            if (!$fp) {
                $this->messageManager->addError(__('Failed to connect:' . $err . $errstr . PHP_EOL . '(IOS)'));
                return;
            }
            foreach ($collectionDevice as $item) {
                $deviceToken = $item->getDeviceToken();
                $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
                // Send it to the server
                $result = fwrite($fp, $msg, strlen($msg));
                if (!$result) {
                    $this->messageManager->addError(__('Message not delivered (IOS)' . PHP_EOL));
                    return false;
                }
                $totalDevice++;
            }
            fclose($fp);
        }

        $this->messageManager->addSuccess(__('Message successfully delivered to '.$totalDevice.' devices (IOS)' . PHP_EOL));
        return true;

    }

    /**
     * @param $collectionDevice
     * @param $data
     * @return bool
     */
    public function sendAndroid($collectionDevice, $data) {
        $total = count($collectionDevice);
        $this->checkIndex($data);
        $message = array(
            'message' => $data['siminotification_content'],
            'url' => $data['siminotification_url'],
            'title' => $data['siminotification_title'],
            'type' => $data['type'],
            'productID' => $data['product_id'],
            'categoryID' => $data['category_id'],
            'categoryName' => $data['category_name'],
            'has_child'  => $data['has_child'],
            'imageUrl'   => $data['image_url'],
            'height'     => $data['height'],
            'width'     => $data['width'],
            'show_popup'   => $data['show_popup'],
        );
        if($total > 0)
            $this->repeatSendAnddroid($total, $collectionDevice->getData(), $message);
        return true;
    }

    /**
     * @param $total
     * @param $collectionDevice
     * @param $message
     * @return bool
     */
    public function repeatSendAnddroid($total, $collectionDevice, $message){
        $size = $total;
        while (true) {
            $from_user = 0;
            $check = $total - 999;
            if($check <= 0){
                //send to  (total+from_user) user from_user
                $is = $this->sendTurnAnroid($collectionDevice, $from_user, $from_user+$total, $message);
                if($is == false){
                    $this->messageManager->addError(__('Message not delivered (Android)'));
                    return false;
                }
                $this->messageManager->addSuccess(__('Message successfully delivered to %s devices (Android)', $size));
                return true;
            }else{
                //send to 100 user from_user
                $is = $this->sendTurnAnroid($collectionDevice, $from_user, $from_user+999, $message);
                if($is == false){
                    $this->messageManager->addError(__('Message not delivered (Android)'));
                    return false;
                }
                $total = $check;
                $from_user += 999;
            }
        }
    }

    /**
     * @param $collectionDevice
     * @param $from
     * @param $to
     * @param $message
     * @return bool
     */
    public function sendTurnAnroid($collectionDevice, $from, $to, $message){
        $registrationIDs = array();
        for ($i = $from; $i <= $to; $i++) {
            $item = $collectionDevice[$i];
            $registrationIDs[] = $item['device_token'];
        }

        $url = 'https://android.googleapis.com/gcm/send';
        $fields = array(
            'registration_ids' => $registrationIDs,
            'data' => array("message" => $message),
        );

        $api_key = $this->_objectManager->get('Simi\Simiconnector\Helper\Data')->getAndroidKeyConfig();
        $headers = array(
            'Authorization: key=' . $api_key,
            'Content-Type: application/json');

        $result = '';
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // Disabling SSL Certificate support temporarly
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {

        }

        $re = json_decode($result);

        if ($re == NULL || $re->success == 0) {
            return false;
        }
        return true;

    }

    /**
     * @param $data
     */
    public function checkIndex(&$data){
        if(!isset($data['type'])){
            $data['type'] = '';
        }
        if(!isset($data['product_id'])){
            $data['product_id'] = '';
        }
        if(!isset($data['category_id'])){
            $data['category_id'] = '';
        }
        if(!isset($data['category_name'])){
            $data['category_name'] = '';
        }
        if(!isset($data['has_child'])){
            $data['has_child'] = '';
        }
        if(!isset($data['image_url'])){
            $data['image_url'] = '';
        }
        if(!isset($data['height'])){
            $data['height'] = '';
        }
        if(!isset($data['width'])){
            $data['width'] = '';
        }
        if(!isset($data['show_popup'])){
            $data['show_popup'] = '';
        }
    }
}

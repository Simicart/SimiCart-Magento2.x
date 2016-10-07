<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

class Siminotification extends \Simi\Simiconnector\Helper\Data {

    public function sendNotice($data) {
        $trans = $this->send($data);
        // update notification history
        $history = $this->_objectManager->get('Simi\Simiconnector\Model\History');
        if (!$trans)
            $data['status'] = 0;
        else
            $data['status'] = 1;

        $history->setData($data);
        $history->save();
        return $trans;
    }

    public function send(&$data) {
        if (isset($data['category_id'])) {
            $categoryId = $data['category_id'];
            $category = $this->_objectManager->get('\Magento\Catalog\Model\Category')->load($categoryId);
            $categoryChildrenCount = $category->getChildrenCount();
            $categoryName = $category->getName();
            $data['category_name'] = $categoryName;
            if ($categoryChildrenCount > 0)
                $categoryChildrenCount = 1;
            else
                $categoryChildrenCount = 0;
            $data['has_child'] = $categoryChildrenCount;
            if (!$data['has_child']) {
                $data['has_child'] = '';
            }
        }
        if (isset($data['product_id'])) {
            $productId = $data['product_id'];
            $productName = $this->_objectManager->get('Magento\Catalog\Model\Product')->load($productId)->getName();
            $data['product_name'] = $productName;
        }
        $this->checkIndex($data);
        $deviceArray = explode(',', str_replace(' ', '', $data['devices_pushed']));

        $collectionDevice = $this->_objectManager->get('Simi\Simiconnector\Model\Device')->getCollection()->addFieldToFilter('device_id', array('in' => $deviceArray));
        $collectionDevice2 = $this->_objectManager->get('Simi\Simiconnector\Model\Device')->getCollection()->addFieldToFilter('device_id', array('in' => $deviceArray));

        switch ($data['notice_sanbox']) {
            case '1':
                $collectionDevice->addFieldToFilter('is_demo', 1);
                $collectionDevice2->addFieldToFilter('is_demo', 1);
                break;
            case '2':
                $collectionDevice->addFieldToFilter('is_demo', 0);
                $collectionDevice2->addFieldToFilter('is_demo', 0);
                break;
            default:
        }

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
            $collectionDevice->addFieldToFilter('plaform_id', array('neq' => 3));
            $collectionDevice2->addFieldToFilter('plaform_id', array('eq' => 3));
            $resultIOS = $this->sendIOS($collectionDevice, $data);
            $resultAndroid = $this->sendAndroid($collectionDevice2, $data);
            if ($resultIOS || $resultAndroid)
                return true;
            else
                return false;
        }
    }

    public function sendIOS($collectionDevice, $data) {
        $ch = $this->getDirPEMfile($data);
        $dir = $this->getDirPEMPassfile();
        $message = $data['notice_content'];
        $body['aps'] = array(
            'alert' => $data['notice_title'],
            'sound' => 'default',
            'badge' => 1,
            'title' => $data['notice_title'],
            'message' => $message,
            'url' => $data['notice_url'],
            'type' => $data['type'],
            'productID' => $data['product_id'],
            'categoryID' => $data['category_id'],
            'categoryName' => $data['category_name'],
            'has_child' => $data['has_child'],
            'imageUrl' => $data['image_url'],
            'height' => $data['height'],
            'width' => $data['width'],
            'show_popup' => $data['show_popup'],
        );
        /*
          echo 'iOS push:';
          zend_debug::dump($body);
          die;
         * 
         */
        $payload = json_encode($body);
        $totalDevice = 0;

        $i = 0;
        $tokenArray = array();
        $sentsuccess = true;
        foreach ($collectionDevice as $item) {
            if ($i == 100) {
                $result = $this->repeatSendiOS($tokenArray, $payload, $ch, $dir);
                if (!$result)
                    $sentsuccess = false;
                $i = 0;
                $tokenArray = array();
            }
            if (strlen($item->getDeviceToken()) < 70)
                $tokenArray[] = $item->getDeviceToken();
            $i++;
            $totalDevice++;
        }
        if ($i < 100)
            $result = $this->repeatSendiOS($tokenArray, $payload, $ch, $dir);
        if (!$result)
            $sentsuccess = false;

        if ($sentsuccess)
            $this->_objectManager->get('\Magento\Framework\Message\ManagerInterface')->addSuccess(__('Message successfully delivered to %1 devices (IOS)', $totalDevice));
        return true;
    }

    public function repeatSendiOS($tokenArray, $payload, $ch, $dir) {
        $ctx = @stream_context_create();
        @stream_context_set_option($ctx, 'ssl', 'local_cert', $ch);
        $fp = @stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
        if (!$fp) {
            $this->_objectManager->get('\Magento\Framework\Message\ManagerInterface')->addError(__('Failed to connect:') . $err . $errstr . PHP_EOL . "(IOS)");
            return;
        }
        foreach ($tokenArray as $deviceToken) {
            $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
            // Send it to the server
            $result = fwrite($fp, $msg, strlen($msg));
            if (!$result) {
                $this->_objectManager->get('\Magento\Framework\Message\ManagerInterface')->addError(__('Message not delivered (IOS)') . PHP_EOL);
                return false;
            }
        }
        fclose($fp);
        return true;
    }

    public function repeatSendAnddroid($total, $collectionDevice, $message) {
        $size = $total;
        $total-= 1;
        while (true) {
            $from_user = 0;
            $check = $total - 999;
            if ($check <= 0) {
                //send to  (total+from_user) user from_user
                $is = $this->sendTurnAnroid($collectionDevice, $from_user, $from_user + $total, $message);
                if ($is == false) {
                    $this->_objectManager->get('\Magento\Framework\Message\ManagerInterface')->addError(__('Message not delivered (Android)'));
                    return false;
                }
                $this->_objectManager->get('\Magento\Framework\Message\ManagerInterface')->addError(__('Message successfully delivered to %s devices (Android)', $size));
                return true;
            } else {
                //send to 100 user from_user
                $is = $this->sendTurnAnroid($collectionDevice, $from_user, $from_user + 999, $message);
                if ($is == false) {
                    $this->_objectManager->get('\Magento\Framework\Message\ManagerInterface')->addError(__('Message not delivered (Android)'));
                    return false;
                }
                $total = $check;
                $from_user += 999;
            }
        }
    }

    public function sendTurnAnroid($collectionDevice, $from, $to, $message) {
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

        $api_key = $this->getConfig('simi_notifications/notification/android_secret_key', $collectionDevice[0]['storeview_id']);
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

    public function sendAndroid($collectionDevice, $data) {
        if ($collectionDevice->count() == 0)
            return false;
        $total = count($collectionDevice);
        $message = array(
            'message' => $data['notice_content'],
            'url' => $data['notice_url'],
            'title' => $data['notice_title'],
            'type' => $data['type'],
            'productID' => $data['product_id'],
            'categoryID' => $data['category_id'],
            'categoryName' => $data['category_name'],
            'has_child' => $data['has_child'],
            'imageUrl' => $data['image_url'],
            'height' => $data['height'],
            'width' => $data['width'],
            'show_popup' => $data['show_popup'],
        );

        $this->repeatSendAnddroid($total, $collectionDevice->getData(), $message);
        return true;
    }

    public function checkIndex(&$data) {
        if (!isset($data['type'])) {
            $data['type'] = '';
        }
        if (!isset($data['product_id'])) {
            $data['product_id'] = '';
        }
        if (!isset($data['category_id'])) {
            $data['category_id'] = '';
        }
        if (!isset($data['category_name'])) {
            $data['category_name'] = '';
        }
        if (!isset($data['has_child'])) {
            $data['has_child'] = '';
        }
        if (!isset($data['image_url'])) {
            $data['image_url'] = '';
        }
        if (!isset($data['height'])) {
            $data['height'] = '';
        }
        if (!isset($data['width'])) {
            $data['width'] = '';
        }
        if (!isset($data['show_popup'])) {
            $data['show_popup'] = '';
        }
    }

    public function getDirPEMfile($data) {
        switch ($data['notice_sanbox']) {
            case '1': 
                if (!$this->getConfig("simi_notifications/notification/upload_pem_file_test", $data['storeview_id']))
                    return BP . '/app/code/Simi/Simiconnector/view/adminhtml/web/pem/' . 'push.pem';
                else
                    return BP . '/pub/media/simi/simiconnector/pem/' . $this->getConfig("simi_notifications/notification/upload_pem_file_test", $data['storeview_id']);
                break;
            case '2':
                return BP . '/pub/media/simi/simiconnector/pem/manual' . $this->getConfig("simi_notifications/notification/upload_pem_file", $data['storeview_id']);
                break;
            default:
        }
    }

    public function getDirPEMPassfile() {
        return BP . '/pub/media/simi/simiconnector/pem/pass_pem.config';
    }

    public function getConfig($nameConfig, $storeid) {
        return $this->_scopeConfig->getValue($nameConfig, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeid);
    }

}

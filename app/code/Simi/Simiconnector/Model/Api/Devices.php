<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Devices extends Apiabstract
{

    public $DEFAULT_ORDER = 'device_id';

    public function setBuilderQuery()
    {
        $data = $this->getData();
        if ($data['resourceid']) {
            if ($data['resourceid'] == 'device_token') {
                $this->builderQuery = $this->simiObjectManager
                    ->get('\Simi\Simiconnector\Model\Device')
                    ->getCollection()
                    ->getItemByColumnValue('device_token', $data['params']['device_token']);
            } else {
               $this->builderQuery = $this->simiObjectManager
                    ->get('Simi\Simiconnector\Model\Device')->load($data['resourceid']);
            }            
        }else{
            $this->builderQuery = $this->simiObjectManager->get('Simi\Simiconnector\Model\Device')->getCollection();
        }
    }

    public function show()
    {  
        $entity = $this->builderQuery;
        $info = [];
        $info['device_id'] = $entity->getDeviceId();
        $info['storeview_id'] = $entity->getStoreviewId();
        $info['noti_unread'] = $entity->getNotiUnread();
        $info['device_token'] = $entity->getDeviceToken();
        $listString = $entity->getNotiUnread();
        if ($listString == '') {
            $arrayNotiUnread = [];
        }else{
            $arrayNotiUnread = explode(',', str_replace(' ', '', $listString));
        }
        $info['number_noti'] = sizeof($arrayNotiUnread);

        return $this->getDetail($info);
    }

    public function store()
    {
        $data               = $this->getData();
        $device             = $this->simiObjectManager->get('Simi\Simiconnector\Model\Device');
        $device->saveDevice($data);
        $this->builderQuery = $device;
        return $this->show();
    }


    public function update(){
        $data               = $this->getData();
         if ($data['resourceid'] && $data['resourceid'] == 'device_token') {
            $deviceModel = $this->builderQuery;
                if ($deviceModel) {
                    $listString = $deviceModel->getData('noti_unread');
                if ($listString == '') {
                    $arrayNotiUnread = [];
                }else{
                    $arrayNotiUnread = explode(',', str_replace(' ', '', $listString));
                }
                $parameters = (array) $data['contents'];

                if (in_array($parameters['history_id'], $arrayNotiUnread)) {
                    $arrayNotiUnread = array_diff($arrayNotiUnread, [$parameters['history_id']]);
                }

                $deviceModel->setData('noti_not_read', implode(", ",$arrayNotiUnread));
                $deviceModel->save();
                return $this->show();
            }
           
        }
    }
}

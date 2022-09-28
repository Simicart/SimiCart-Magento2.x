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
    }

    public function store()
    {
        $data = $this->getData();
        $device = $this->simiObjectManager->get('Simi\Simiconnector\Model\Device');
        $device->saveDevice($data);
        $this->builderQuery = $device;
        return $this->show();
    }

    public function show() {
        if (!$this->builderQuery) {
            return array();
        }
        return parent::show();
    }

    public function index() {
        $result = array();
        $result['all_ids'] = [];
        $result['devices'] = [];
        $result['total'] = 0;
        $result['page_size'] = 15;
        $result['from'] = 0;
        return $result;
    }
}

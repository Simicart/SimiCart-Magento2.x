<?php

/**
 *
 * Copyright © 2016 Simicommerce. All rights reserved.
 */

namespace Simi\Simiconnector\Controller\Index;

class CheckInstall extends \Magento\Framework\App\Action\Action
{

    public function execute()
    {
        $arr               = [];
        $arr['is_install'] = "1";
        $key               = $this->getRequest()->getParam('key');
        if ($key == null || $key == '') {
            $arr["website_key"] = "0";
        } else {
            $simiObjectManager = $this->_objectManager;
            $encodeMethod = 'md5';
            $keySecret = $encodeMethod($simiObjectManager
                    ->get('\Magento\Framework\App\Config\ScopeConfigInterface')
                    ->getValue('simiconnector/general/secret_key'));
            if (strcmp($key, $keySecret) == 0) {
                $arr["website_key"] = "1";
            } else {
                $arr["website_key"] = "0";
            }
        }
        return $this->getResponse()->setBody(json_encode($arr));
    }
}

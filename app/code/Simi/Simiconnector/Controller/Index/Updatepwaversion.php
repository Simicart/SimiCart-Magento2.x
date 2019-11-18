<?php

/**
 *
 * Copyright © 2016 Simicommerce. All rights reserved.
 */

namespace Simi\Simiconnector\Controller\Index;

class Updatepwaversion extends \Magento\Framework\App\Action\Action
{
    public $simiObjectManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {

        parent::__construct($context);
        $this->simiObjectManager  = $context->getObjectManager();
    }


    public function execute()
    {
        $arr               = [];
        $newValue = time();
        $this->simiObjectManager
            ->get('Magento\Framework\App\Config\Storage\WriterInterface')
            ->save('simiconnector/general/pwa_studio_client_ver_number', $newValue);
        $arr['new_value'] = $newValue;
        $this->simiObjectManager
            ->get('Magento\Framework\App\Cache\TypeListInterface')
            ->cleanType('config');
        return $this->getResponse()->setBody(json_encode($arr));
    }
}

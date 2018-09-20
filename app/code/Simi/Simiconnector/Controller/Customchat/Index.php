<?php

namespace Simi\Simiconnector\Controller\Customchat;

class Index extends \Magento\Framework\App\Action\Action
{
    public $data;
    public $simiObjectManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {

        parent::__construct($context);
        $this->simiObjectManager  = $context->getObjectManager();
    }

    public function execute()
    {
        $block = $this->simiObjectManager->create('Simi\Simiconnector\Block\Customchat');
        $html = $block->toHtml();
        return $this->getResponse()->setBody($html);
    }
}

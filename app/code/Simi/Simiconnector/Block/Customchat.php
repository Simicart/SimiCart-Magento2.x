<?php

namespace Simi\Simiconnector\Block;

class Customchat extends \Magento\Framework\View\Element\Template
{

    public $storeManager;
    public $scopeConfig;
    public $simiObjectManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->simiObjectManager = $objectManager;
        $this->scopeConfig = $this->simiObjectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->storeManager = $this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface');
        parent::__construct($context);
        $this->setTemplate('custom_chat/script.phtml');
        //$this->setTemplate('Magento_Catalog::product/list/items.phtml');
    }

    public function getStoreConfig($path)
    {
        return $this->scopeConfig->getValue($path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getCode());
    }

    public function getHeadScript(){
        return $this->getStoreConfig('simiconnector/customchat/head_script');
    }

    public function getBodyScript()
    {
        return $this->getStoreConfig('simiconnector/customchat/body_script');
    }

    public function isEnabledChat()
    {
        return $this->getStoreConfig('simiconnector/customchat/enable');
    }
}

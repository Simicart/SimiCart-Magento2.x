<?php

namespace Simi\Simiconnector\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

class AccountConfirmDispatch implements ObserverInterface
{

    protected $_redirect;
    protected $_url;

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\App\Response\Http $redirect
    )
    {
        $this->simiObjectManager = $simiObjectManager;
        $this->_url = $url;
        $this->_redirect = $redirect;
    }

    public function execute(Observer $observer)
    {
        $pwa_studio_url = $this->simiObjectManager
            ->get('\Magento\Framework\App\Config\ScopeConfigInterface')
            ->getValue('simiconnector/general/pwa_studio_url');
        if ($pwa_studio_url) {
            $CustomRedirectionUrl = $this->_url->getUrl('simiconnector/account/confirm');
            $this->_redirect->setRedirect($CustomRedirectionUrl);
        }
    }
}
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
        \Magento\Framework\App\Response\Http $redirect
    )
    {
        $this->_url = $url;
        $this->_redirect = $redirect;
    }

    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $CustomRedirectionUrl = $this->_url->getUrl('simiconnector/account/confirm');
        $this->_redirect->setRedirect($CustomRedirectionUrl);

    }
}
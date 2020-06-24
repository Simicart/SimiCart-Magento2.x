<?php

namespace Simi\Simiconnector\Controller\Checkout;

class Index extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        $checkoutUrl = $this->getStoreConfig('simiconnector/checkout_config/checkout_page_url');
        if(!$checkoutUrl){
			$checkoutUrl = 'checkout';
		}
		$this->_redirect($checkoutUrl,['_secure' => true ]);
    }

    public function getStoreConfig($path)
    {
        return $this->_objectManager
                ->get('\Magento\Framework\App\Config\ScopeConfigInterface')
                ->getValue($path);
    }
}

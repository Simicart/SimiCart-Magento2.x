<?php

namespace Simi\Simiconnector\Controller\Index;

class Redirect extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        $token = $this->getRequest()->getParam('token');

        $salt = $this->getRequest()->getParam('salt');
        if (!$token) {
            $this->_redirect('checkout/cart', ['_secure'=>true]);
        }

        $token = base64_decode($token);
        
        $secretKey = (String )$this->getStoreConfig('simiconnector/general/secret_key');
        $encodeMethod = 'md5';
        $secretKeyEncrypted = $encodeMethod($secretKey);

        $key  = substr_replace($secretKeyEncrypted,$salt,strlen($salt),0);

        $paramsEncrypted =str_replace($key, "", $token);

        $paramsJson = base64_decode($paramsEncrypted);

        $params =json_decode($paramsJson, 1);

        $email =false;
        $password =false;

        $quoteId =base64_decode($params['quote_id']);
        $url =base64_decode($params['redirect_url']);
        if(!$url) {
            $url = 'checkout/cart';
        }

        if (isset($params['email']) && $params['email']) {
            $email = $params['email'];
        }

        if (isset($params['password']) && $params['password']) {
            $password = $params['password'];
        }

        $session = $this->_getCheckoutSession();
        $quote = $this->_objectManager->create('\Magento\Quote\Model\Quote')->load($quoteId);

        $session->replaceQuote($quote);

        if ($email && $password) {
            $storeManager = $this->_objectManager->create('\Magento\Store\Model\StoreManagerInterface');
            $websiteId = $storeManager->getStore()->getWebsiteId();
            $store = $storeManager->getStore();
            $customer = $this->_objectManager->create('\Magento\Customer\Model\Customer');
            $customer->website_id = $websiteId;
            $customer->setStore($store);
            
            try {
                $customer->loadByEmail($email);
                if ($customer->authenticate($email, $password)) {
                    $session = $this->_objectManager->create('\Magento\Customer\Model\Session')
                                ->setCustomerAsLoggedIn($customer);
                }
            } catch (\Exception $e) {
                $this->_redirect($url, ['_secure' => true]);
            }
        }
        $this->_redirect($url, ['_secure' => true]);
    }

    private function _getCheckoutSession()
    {
        return $this->_objectManager->get('\Magento\Checkout\Model\Session');
    }

    public function getStoreConfig($path)
    {
        return $this->_objectManager
                ->get('\Magento\Framework\App\Config\ScopeConfigInterface')
                ->getValue($path);
    }
}

<?php


namespace Simi\Simiconnector\Observer;

use Magento\Framework\Event\ObserverInterface;

class SystemRestModify implements ObserverInterface
{
    private $simiObjectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager
    ) {
        $this->simiObjectManager = $simiObjectManager;
    }


    public function execute(\Magento\Framework\Event\Observer $observer) {
       $obj = $observer->getObject();
       $routeData = $observer->getData('routeData');
       $requestContent = $observer->getData('requestContent');
       $request = $observer->getData('request');
       $contentArray = $obj->getContentArray();
       if ($routeData && isset($routeData['routePath'])){
           if (
               strpos($routeData['routePath'], 'V1/guest-carts/:cartId') !== false ||
               strpos($routeData['routePath'], 'V1/carts/mine') !== false
           ) {
               $this->_addDataToQuoteItem($contentArray);
           } else if (strpos($routeData['routePath'], 'integration/customer/token') !== false) {
               $this->_addCustomerIdentity($contentArray, $requestContent, $request);
           }
       }
       $obj->setContentArray($contentArray);
    }

    //modify quote item
    private function _addDataToQuoteItem(&$contentArray) {
        if (isset($contentArray['items']) && is_array($contentArray['items'])) {
            foreach ($contentArray['items'] as $index => $item) {
                $quoteItem = $this->simiObjectManager
                    ->get('Magento\Quote\Model\Quote\Item')->load($item['item_id']);
                if ($quoteItem->getId()) {
                    $product = $this->simiObjectManager
                        ->create('Magento\Catalog\Model\Product')
                        ->load($quoteItem->getData('product_id'));
                    $item['simi_image']  = $this->simiObjectManager
                        ->create('Simi\Simiconnector\Helper\Products')
                        ->getImageProduct($product);
                    $item['simi_sku']  = $product->getData('sku');
                    $contentArray['items'][$index] = $item;
                }
            }
        }
    }

    //add SessionId to login api of system rest
    private function _addCustomerIdentity(&$contentArray, $requestContent, $request) {
        if (is_string($contentArray) && $request->getParam('getSessionId') && $requestContent['username']) {
            $storeManager = $this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $requestCustomer = $this->simiObjectManager->get('Magento\Customer\Model\Customer')
                ->setWebsiteId($storeManager->getStore()->getWebsiteId())
                ->loadByEmail($requestContent['username']);
            $tokenCustomerId = $this->simiObjectManager->create('Magento\Integration\Model\Oauth\Token')
                ->loadByToken($contentArray)->getData('customer_id');
            if ($requestCustomer && $requestCustomer->getId() == $tokenCustomerId) {
                $this->simiObjectManager
                    ->get('Magento\Customer\Model\Session')
                    ->setCustomerAsLoggedIn($requestCustomer);
                $contentArray = array(
                    'customer_access_token' => $contentArray,
                    'customer_identity' => $this->simiObjectManager
                        ->get('Magento\Customer\Model\Session')
                        ->getSessionId()
                );
            }
        }
    }
}

<?php


namespace Simi\Simiconnector\Observer;

use Magento\Framework\Event\ObserverInterface;

class SystemRestModify implements ObserverInterface
{
    private $simiObjectManager;
    private $inputParamsResolver;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Webapi\Controller\Rest\InputParamsResolver $inputParamsResolver
    ) {
        $this->simiObjectManager = $simiObjectManager;
        $this->inputParamsResolver = $inputParamsResolver;
    }


    public function execute(\Magento\Framework\Event\Observer $observer) {
       $obj = $observer->getObject();
       $routeData = $observer->getData('routeData');
       $requestContent = $observer->getData('requestContent');
       $request = $observer->getData('request');
       $contentArray = $obj->getContentArray();
       if ($routeData && isset($routeData['routePath'])){
           if (
               strpos($routeData['routePath'], 'V1/guest-carts/:cartId/payment-methods') !== false ||
               strpos($routeData['routePath'], 'V1/carts/mine/payment-methods') !== false ||
               strpos($routeData['routePath'], 'V1/guest-carts/:cartId/shipping-information') !== false ||
               strpos($routeData['routePath'], 'V1/carts/mine/shipping-information') !== false
           ) {
               if ( isset($contentArray['payment_methods']) &&
                    (strpos($routeData['routePath'], 'V1/guest-carts/:cartId/shipping-information') !== false ||
                     strpos($routeData['routePath'], 'V1/carts/mine/shipping-information') !== false)){
                   $this->_addDataToPayment($contentArray['payment_methods'], $routeData);
               }else{
                   $this->_addDataToPayment($contentArray, $routeData);
               }
               if (isset($contentArray['totals']['items'])) {
                   $totalData = $contentArray['totals'];
                   $this->_addDataToQuoteItem($totalData);
                   $contentArray['totals'] = $totalData;
               }
           } else if (
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

    //modify payment api
    private function _addDataToPayment(&$contentArray, $routeData) {
        if (is_array($contentArray) && $routeData && isset($routeData['serviceClass'])) {
            $inputParams = $this->inputParamsResolver->resolve();
            if ($inputParams && is_array($inputParams) && isset($inputParams[0])) {
                $quoteId = $inputParams[0];
                $quoteIdMask = $this->simiObjectManager->get('Magento\Quote\Model\QuoteIdMask');
                if ($quoteIdMask->load($quoteId, 'masked_id')) {
                    if ($quoteIdMask && $maskQuoteId = $quoteIdMask->getData('quote_id'))
                        $quoteId = $maskQuoteId;
                }
                $quoteModel = $this->simiObjectManager->get('Magento\Quote\Model\Quote')->load($quoteId);
                if ($quoteModel->getId() && $quoteModel->getData('is_active')) {
                    $this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->setQuoteToSession($quoteModel);
                }
            }

            $paymentHelper = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Checkout\Payment');
            foreach ($paymentHelper->getMethods() as $method) {
                foreach ($contentArray as $index=>$restPayment) {
                    if ($method->getCode() == $restPayment['code']) {
                        $restPayment['simi_payment_data'] = $paymentHelper->getDetailsPayment($method);
                    }
                    $contentArray[$index] = $restPayment;
                }
            }
        }
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
                    $item['url_key']  = $product->getData('url_key');
                    $item['name']  = $product->getName();

                    $parentProducts = $this->simiObjectManager
                        ->create('Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable')
                        ->getParentIdsByChild($product->getId());
                    $imageProductModel = $product;
                    if($parentProducts && isset($parentProducts[0])){
                        $media_gallery = $imageProductModel->getMediaGallery();
                        $parentProductModel = $this->simiObjectManager->create('\Magento\Catalog\Model\Product')->load($parentProducts[0]);
                        if ($media_gallery && isset($media_gallery['images']) && is_array($media_gallery['images']) && !count($media_gallery['images'])) {
                            $imageProductModel = $parentProductModel;
                        }
                        $item['url_key'] = $parentProductModel->getData('url_key');
                    }
                    $item['image'] =  $this->simiObjectManager
                        ->create('Simi\Simiconnector\Helper\Products')
                        ->getImageProduct($imageProductModel);


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

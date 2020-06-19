<?php

namespace Simi\Simiconnector\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Authorization\Model\UserContextInterface;

class CustomerSessionInit implements ObserverInterface
{
    //set currency and storeview if sent (graphql and system rest)

    private $simiObjectManager;
    private $request;
    public $storeManager;
    public $storeRepository;
    public $storeCookieManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->simiObjectManager = $simiObjectManager;
        $this->request = $request;
        $this->storeManager = $this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $this->storeRepository = $this->simiObjectManager->get('\Magento\Store\Api\StoreRepositoryInterface');
        $this->storeCookieManager = $this->simiObjectManager->get('\Magento\Store\Api\StoreCookieManagerInterface');
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->isVersion(array('2.0', '2.1')))
            return;
        $state = $this->simiObjectManager->get('Magento\Framework\App\State');
        if (
            $state->getAreaCode() !== \Magento\Framework\App\Area::AREA_WEBAPI_REST &&
            $state->getAreaCode() !== 'graphql' //AREA_GRAPHQL not available before 2.3.1
        )
            return;
        
        $contents            = $this->request->getContent();
        $contents_array      = [];
        if ($contents && ($contents != '')) {
            $contents_parser = urldecode($contents);
            $contents_array = json_decode($contents_parser, true);
        }
        $this->simiObjectManager->create('\Magento\Framework\Session\SessionManager');
        $storeManager = $this->simiObjectManager
            ->get('Magento\Store\Model\StoreManagerInterface');
        $simiStoreId = $this->request->getParam('simiStoreId');
        $simiCurrency = $this->request->getParam('simiCurrency');
        if ($contents_array) {
            if (!$simiStoreId && isset($contents_array['variables']['simiStoreId'])) {
                $simiStoreId = $contents_array['variables']['simiStoreId'];
            }
            if (!$simiStoreId && isset($contents_array['variables']['storeId'])) {
                $simiStoreId = $contents_array['variables']['storeId'];
            }
            if (!$simiStoreId && isset($contents_array['simiStoreId'])) {
                $simiStoreId = $contents_array['simiStoreId'];
            }
            if (!$simiCurrency && isset($contents_array['variables']['simiCurrency'])) {
                $simiCurrency = $contents_array['variables']['simiCurrency'];
            }
            if (!$simiCurrency && isset($contents_array['variables']['currency'])) {
                $simiCurrency = $contents_array['variables']['currency'];
            }
            if (!$simiCurrency && isset($contents_array['simiCurrency'])) {
                $simiCurrency = $contents_array['simiCurrency'];
            }
        }
        //in case of GET graphQL
        $graphQLVariables = $this->request->getParam('variables');
        if ($graphQLVariables) {
            $graphQLVariables = json_decode($graphQLVariables, true);
            if ($graphQLVariables && is_array($graphQLVariables)) {
                if (isset($graphQLVariables['simiStoreId']))
                    $simiStoreId = $graphQLVariables['simiStoreId'];
                if (isset($graphQLVariables['simiCurrency']))
                    $simiCurrency = $graphQLVariables['simiCurrency'];
            }
        }

        if ($simiStoreId && $simiStoreId != '' && (int)$storeManager->getStore()->getId() != (int)$simiStoreId) {
            try {
                $storeCode = $this->simiObjectManager
                    ->get('Magento\Store\Model\StoreManagerInterface')->getStore($simiStoreId)->getCode();

                $store = $this->storeRepository->getActiveStoreByCode($storeCode);
                $defaultStoreView = $this->storeManager->getDefaultStoreView();
                if ($defaultStoreView->getId() == $store->getId()) {
                    $this->storeCookieManager->deleteStoreCookie($store);
                } else {
                    $this->storeCookieManager->setStoreCookie($store);
                }
                $this->storeManager->setCurrentStore(
                    $this->simiObjectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore($simiStoreId)
                );

                $storeKey = \Magento\Store\Model\StoreManagerInterface::CONTEXT_STORE;
                $this->httpContext = $this->simiObjectManager->get('Magento\Framework\App\Http\Context');
                $this->httpContext->setValue($storeKey, $storeCode, $this->storeManager->getDefaultStoreView()->getCode());
            } catch (\Exception $e) {

            }
        }
        if ($simiCurrency && $simiCurrency != '' && $simiCurrency != $storeManager->getStore()->getCurrentCurrencyCode()) {
            try {
                $this->storeManager->getStore()->setCurrentCurrencyCode($simiCurrency);
            } catch (\Exception $e) {

            }
        }
    }

}

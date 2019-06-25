<?php

namespace Simi\Simiconnector\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Authorization\Model\UserContextInterface;

class CustomerSessionInit implements ObserverInterface
{

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
            if (isset($contents_array['variables']['simiStoreId'])) {
                $simiStoreId = $contents_array['variables']['simiStoreId'];
            }
            if (isset($contents_array['variables']['simiCurrency'])) {
                $simiCurrency = $contents_array['variables']['simiCurrency'];
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

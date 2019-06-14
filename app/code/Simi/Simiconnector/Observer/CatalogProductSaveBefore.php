<?php

/**
 * Created by PhpStorm.
 * User: trueplus
 * Date: 4/19/16
 * Time: 08:52
 */

namespace Simi\Simiconnector\Observer;

use Magento\Framework\Event\ObserverInterface;

class CatalogProductSaveBefore implements ObserverInterface
{

    private $simiObjectManager;
    public $new_added_product_sku = '';

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager
    ) {
   
        $this->simiObjectManager = $simiObjectManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->sendNotificationProductChangePrice($observer);
        $this->sendNotificationNewProduct($observer);
    }

    private function sendNotificationProductChangePrice($observer)
    {
        $helper              = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Siminotification');
        $storeViewCollection = $this->simiObjectManager->get('\Magento\Store\Model\Store')->getCollection();
        $newProduct = $observer->getProduct();
        $oldProduct = $this->simiObjectManager
                        ->create('Magento\Catalog\Model\Product')->load($newProduct->getId());
        foreach ($storeViewCollection as $storeview) {
            $storeviewId = $storeview->getId();
            if ($helper->getConfig('simi_notifications/notice_price/noti_price_enable', $storeviewId)) {
                if (!in_array($storeview->getWebsiteId(), $newProduct->getWebsiteIds())) {
                    continue;
                }
                $newPrice        = $newProduct->getData('price');
                $newSpecialPrice = $newProduct->getData('special_price');
                $oldPrice        = $oldProduct->getData('price');
                $oldSpecialPrice = $oldProduct->getData('special_price');
                if ($oldSpecialPrice != $newSpecialPrice
                        && $newProduct->getId() > 0
                        && $newProduct->getStatus() == '1' && $newProduct->getVisibility() != '1') {
                    $data                   = [];
                    $content                = __(
                        $helper->getConfig('simi_notifications/notice_price/noti_price_message', $storeviewId),
                        $newProduct->getName(),
                        $this->formatPrice($oldSpecialPrice),
                        $this->formatPrice($newSpecialPrice)
                    );
                    $data['website_id']     = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_website', $storeviewId);
                    $data['show_popup']     = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_showpopup', $storeviewId);
                    $data['notice_title']   = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_title', $storeviewId);
                    $data['notice_url']     = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_url', $storeviewId);
                    $data['notice_content'] = $content;
                    $data['device_id']      = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_platform', $storeviewId);
                    $data['notice_sanbox']  = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_sandbox', $storeviewId);
                    $data['type']           = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_type', $storeviewId);
                    $data['product_id']     = $newProduct->getId();
                    $data['category_id']    = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_category_id', $storeviewId);
                    $data['category_name']  = $this
                            ->getCategoryName($helper
                            ->getConfig('simi_notifications/notice_price/noti_price_category_id', $storeviewId));
                    $data['has_child']      = $this
                            ->getCategoryChildrenCount($helper
                            ->getConfig('simi_notifications/notice_price/noti_price_category_id', $storeviewId));
                    $data['created_time']   = time();
                    $data['notice_type']    = 1;
                    $data['notice_sanbox']  = '2';
                    $data['storeview_id']   = $storeviewId;
                    $data['devices_pushed'] = $this->getAllDeviceToPush($storeviewId);
                    if ($data['devices_pushed']) {
                        $helper->sendNotice($data);
                    }
                } elseif ($oldPrice != $newPrice && $newProduct->getId() > 0
                        && $newProduct->getStatus() == '1' && $newProduct->getVisibility() != '1') {
                    $data                   = [];
                    $content                = __(
                        $helper->getConfig('simi_notifications/notice_price/noti_price_message', $storeviewId),
                        $newProduct->getName(),
                        $this->formatPrice($oldPrice),
                        $this->formatPrice($newPrice)
                    );
                    $data['website_id']     = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_website', $storeviewId);
                    $data['show_popup']     = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_showpopup', $storeviewId);
                    $data['notice_title']   = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_title', $storeviewId);
                    $data['notice_url']     = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_url', $storeviewId);
                    $data['notice_content'] = $content;
                    $data['device_id']      = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_platform', $storeviewId);
                    $data['notice_sanbox']  = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_sandbox', $storeviewId);
                    $data['type']           = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_type', $storeviewId);
                    $data['product_id']     = $newProduct->getId();
                    $data['category_id']    = $helper
                            ->getConfig('simi_notifications/notice_price/noti_price_category_id', $storeviewId);
                    $data['category_name']  = $this
                            ->getCategoryName($helper
                            ->getConfig('simi_notifications/notice_price/noti_price_category_id', $storeviewId));
                    $data['has_child']      = $this
                            ->getCategoryChildrenCount($helper
                            ->getConfig('simi_notifications/notice_price/noti_price_category_id', $storeviewId));
                    $data['created_time']   = time();
                    $data['notice_type']    = 1;
                    $data['notice_sanbox']  = '2';
                    $data['storeview_id']   = $storeviewId;
                    $data['devices_pushed'] = $this->getAllDeviceToPush($storeviewId);
                    if ($data['devices_pushed']) {
                        $helper->sendNotice($data);
                    }
                } elseif (!$newProduct->getId()) {
                    $this->new_added_product_sku = $newProduct->getSku();
                }
            }
        }
    }

    private function sendNotificationNewProduct($observer)
    {
        if ($this->new_added_product_sku == '') {
            return;
        }
        $helper              = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Siminotification');
        $storeViewCollection = $this->simiObjectManager->get('\Magento\Store\Model\Store')->getCollection();
        $productCollection = $this->simiObjectManager->create('Magento\Catalog\Model\Product')->getCollection()
                                ->setOrder('entity_id', 'desc');
        foreach ($productCollection as $product) {
            $lastProductId = $product->getId();
            break;
        }
        foreach ($storeViewCollection as $storeview) {
            $storeviewId = $storeview->getId();
            if ($helper->getConfig('simi_notifications/noti_new_product/new_product_enable', $storeviewId)) {
                $newProduct = $observer->getProduct();
                if (!in_array($storeview->getWebsiteId(), $newProduct->getWebsiteIds())) {
                    continue;
                }
                if ($newProduct->getId() && $newProduct->getId() == $lastProductId && $newProduct->getStatus() == '1'
                        && $newProduct->getVisibility() != '1'
                        && $newProduct->getSku() == $this->new_added_product_sku) {
                    $content                = __(
                        $helper->getConfig('simi_notifications/noti_new_product/new_product_message', $storeviewId),
                        $newProduct->getName()
                    );
                    $data                   = [];
                    $data['website_id']     = $helper
                            ->getConfig('simi_notifications/noti_new_product/new_product_website', $storeviewId);
                    $data['show_popup']     = $helper
                            ->getConfig('simi_notifications/noti_new_product/new_product_showpopup', $storeviewId);
                    $data['notice_title']   = $helper
                            ->getConfig('simi_notifications/noti_new_product/new_product_title', $storeviewId);
                    $data['notice_url']     = $helper
                            ->getConfig('simi_notifications/noti_new_product/new_product_url', $storeviewId);
                    $data['notice_content'] = $content;
                    $data['device_id']      = $helper
                            ->getConfig('simi_notifications/noti_new_product/new_product_platform', $storeviewId);
                    $data['notice_sanbox']  = $helper
                            ->getConfig('simi_notifications/noti_new_product/new_product_sandbox', $storeviewId);
                    $data['type']           = $helper
                            ->getConfig('simi_notifications/noti_new_product/new_product_type', $storeviewId);
                    $data['product_id']     = $newProduct->getId();
                    $data['category_id']    = $helper
                            ->getConfig('simi_notifications/noti_new_product/new_product_category_id', $storeviewId);
                    $data['category_name']  = $this
                            ->getCategoryName($helper
                            ->getConfig('simi_notifications/noti_new_product/new_product_category_id', $storeviewId));
                    $data['has_child']      = $this
                            ->getCategoryChildrenCount($helper
                            ->getConfig('simi_notifications/noti_new_product/new_product_category_id', $storeviewId));
                    $data['created_time']   = time();
                    $data['notice_type']    = 2;
                    $data['notice_sanbox']  = '2';
                    $data['storeview_id']   = $storeviewId;
                    $data['devices_pushed'] = $this->getAllDeviceToPush($storeviewId);
                    if ($data['devices_pushed']) {
                        $helper->sendNotice($data);
                    }
                }
            }
        }
    }

    private function getCategoryName($categoryId)
    {
        $category = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')->load($categoryId);
        if (!$category->getId()) {
            return '';
        }
        $categoryName = $category->getName();
        return $categoryName;
    }

    private function getCategoryChildrenCount($categoryId)
    {
        $category = $this->simiObjectManager->create('\Magento\Catalog\Model\Category')->load($categoryId);
        if (!$category->getId()) {
            return 0;
        }
        $categoryChildrenCount = $category->getChildrenCount();
        if ($categoryChildrenCount > 0) {
            $categoryChildrenCount = 1;
        } else {
            $categoryChildrenCount = 0;
        }
        return $categoryChildrenCount;
    }

    private function formatPrice($price)
    {
        return $price;
    }

    private function getAllDeviceToPush($storeview_id)
    {
        $idArray    = [];
        $tokenArray = [];
        foreach ($this->simiObjectManager->get('Simi\Simiconnector\Model\Device')
                ->getCollection()->addFieldToFilter('storeview_id', $storeview_id) as $device) {
            if (!in_array($device->getData('device_token'), $idArray)) {
                $idArray[]    = $device->getId();
                $tokenArray[] = $device->getData('device_token');
            }
        }
        return implode(',', $idArray);
    }
}

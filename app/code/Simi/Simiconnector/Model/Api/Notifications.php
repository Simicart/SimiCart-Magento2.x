<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Notifications extends Apiabstract
{

    public $DEFAULT_ORDER = 'notice_id';

    public function setBuilderQuery()
    {
        $data = $this->getData();
        if ($data['resourceid']) {
            $this->builderQuery = $this->simiObjectManager
                    ->get('\Simi\Simiconnector\Model\Siminotification')->load($data['resourceid']);
        } else {
            $deviceModel = $this->simiObjectManager
                    ->get('\Simi\Simiconnector\Model\Device')
                    ->getCollection()
                    ->getItemByColumnValue('device_token', $data['params']['device_token']);
            if (!$deviceModel || !($deviceModel->getId())) {
                $this->builderQuery = $this->simiObjectManager->get('\Simi\Simiconnector\Model\Siminotification')
                        ->getCollection();
                return;
            }
            $shownList = [];
            foreach ($this->simiObjectManager
                    ->get('\Simi\Simiconnector\Model\History')->getCollection() as $noticeHistory) {
                $noticeId = $noticeHistory->getData('notice_id');
                if ($noticeId && !in_array($noticeId, $shownList)) {
                    if (in_array($deviceModel->getId(), explode(',', str_replace(' ', '', $noticeHistory
                            ->getData('devices_pushed'))))) {
                        $shownList[] = $noticeHistory->getData('notice_id');
                    }
                }
            }
            $this->builderQuery = $this->simiObjectManager->get('\Simi\Simiconnector\Model\Siminotification')
                    ->getCollection()->addFieldToFilter('notice_id', ['in' => $shownList]);
        }
    }

    public function index()
    {
        $result = parent::index();
        foreach ($result['notifications'] as $index => $notification) {
            if (!$notification['type']) {
                $notification['type'] = '1';
            }
            if ($notification['image_url']) {
                $imageHelper               = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Data');
                $notification['image_url'] = $imageHelper->getBaseUrl(false) . $notification['image_url'];
                $list                      = getimagesize($notification['image_url']);
                $notification['width']     = $list[0];
                $notification['height']    = $list[1];
            }
            if ($notification['category_id']) {
                $categoryId                    = $notification['category_id'];
                $category                      = $this->loadCategoryWithId($categoryId);
                $categoryChildrenCount         = $category->getChildrenCount();
                $categoryName                  = $category->getName();
                $notification['category_name'] = $categoryName;
                if ($categoryChildrenCount > 0) {
                    $categoryChildrenCount = 1;
                } else {
                    $categoryChildrenCount = 0;
                }
                $notification['has_child'] = $categoryChildrenCount;
                if (!$notification['has_child']) {
                    $notification['has_child'] = '';
                }
            }
            if ($notification['product_id']) {
                $productId                    = $notification['product_id'];
                $productName                  = $this->loadProductWithId($productId)->getName();
                $notification['product_name'] = $productName;
            }
            $result['notifications'][$index] = $notification;
        }

        return $result;
    }
    
    public function loadCategoryWithId($id)
    {
        $categoryModel    = $this->simiObjectManager
                ->create('\Magento\Catalog\Model\Category')->load($id);
        return $categoryModel;
    }
    
    public function loadProductWithId($id)
    {
        $categoryModel    = $this->simiObjectManager
                ->create('Magento\Catalog\Model\Product')->load($id);
        return $categoryModel;
    }
}

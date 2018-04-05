<?php

namespace Simi\Simiconnector\Model\Api;

class Downloadableproducts extends Apiabstract
{
    public $DEFAULT_ORDER = 'item_id';
    public $purchased = '';


    public function setBuilderQuery()
    {
        $data = $this->getData();
        if (isset($data['resourceid']) && $data['resourceid']) {
        } else {
            $this->builderQuery = $this->getCollectionItems();
        }
    }

    public function index()
    {
        $result = parent::index();
        foreach ($result['downloadableproducts'] as $index => $item) {
            $_item = $this->getCollectionItems()->addFieldToFilter('item_id', $item['item_id'])->getFirstItem();
            $fileName = '';
            if ($_item->getData('link_file')) {
                $fileName = $_item->getData('link_file');
                $fileName = explode('/', $fileName);
                $fileName = end($fileName);
            }

            $itDe = $this->getPurchased()->getItemById($_item->getPurchasedId());
            $data = array(
                'order_id' => $itDe->getOrderIncrementId(),
                'order_date' => $itDe->getCreatedAt(),
                'order_name' => $itDe->getProductName(),
                'order_link' => $this->getDownloadUrl($_item),
                'order_file' => $fileName,
                'order_status' => $_item->getStatus(),
                'order_remain' => $this->getRemainingDownloads($_item)
            );
            $item = array_merge($item, $data);
            $result['downloadableproducts'][$index] = $item;
        }

        return $result;
    }

    public function getCollectionItems()
    {
        $session = $this->simiObjectManager->get('Magento\Customer\Model\Session');
        $purchased = $this->simiObjectManager
            ->create('\Magento\Downloadable\Model\ResourceModel\Link\Purchased\Collection')
            ->addFieldToFilter('customer_id', $session->getCustomerId())
            ->addOrder('created_at', 'desc');
        $this->setPurchased($purchased);
        $purchasedIds = array();
        foreach ($purchased as $_item) {
            $purchasedIds[] = $_item->getId();
        }

        if (empty($purchasedIds)) {
            $purchasedIds = array(null);
        }
        $purchasedItems = $this->simiObjectManager
            ->create('\Magento\Downloadable\Model\Link\Purchased\Item')
            ->getCollection()
            ->addFieldToFilter('purchased_id', array('in' => $purchasedIds))
            ->addFieldToFilter(
                'status', array(
                    'nin' => array(
                        \Magento\Downloadable\Model\Link\Purchased\Item::LINK_STATUS_PENDING_PAYMENT,
                        \Magento\Downloadable\Model\Link\Purchased\Item::LINK_STATUS_PAYMENT_REVIEW
                    )
                )
            )
            ->setOrder('item_id', 'desc');
        return $purchasedItems;
    }

    public function getDownloadUrl($item)
    {
        return $this->simiObjectManager->get('Magento\Framework\UrlInterface')
            ->getUrl('downloadable/download/link', ['id' => $item->getLinkHash(), '_secure' => true]);
    }

    public function getRemainingDownloads($item)
    {
        if ($item->getNumberOfDownloadsBought()) {
            $downloads = $item->getNumberOfDownloadsBought() - $item->getNumberOfDownloadsUsed();
            return $downloads;
        }

        return __('Unlimited');
    }

    public function getPurchased()
    {
        return $this->purchased;
    }

    public function setPurchased($value)
    {
        $this->purchased = $value;
    }
}

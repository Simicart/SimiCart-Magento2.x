<?php

namespace MobileApp\Connector\Controller\Config\Get\Spot\Products;

class V2 extends \MobileApp\Connector\Controller\Catalog\Products
{
    const BEST_SELLER = 'mobileapp_spotproducts/spot/best_sellers';
    const MOST_VIEWED = 'mobileapp_spotproducts/spot/most_viewed';
    const NEWLY_UPDATED = 'mobileapp_spotproducts/spot/newly_updated';
    const RECENTLY_ADDED = 'mobileapp_spotproducts/spot/recently_updated';
    /**
     *
     * @return void
     */
    public function execute()
    {
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $result = [];
        if($this->scopeConfig->getValue(self::BEST_SELLER, $scope)){
            $result[] = [
                'title' => __('Best sellers'),
                'key'   => 'best_seller',
                'data'  => $this->_getBestSellerCollection(),
            ];
        }

        if($this->scopeConfig->getValue(self::MOST_VIEWED, $scope)){
            $result[] = [
                'title' => __('Most viewed'),
                'key'   => 'most_viewed',
                'data'  => $this->_getMostViewedCollection(),
            ];
        }

        if($this->scopeConfig->getValue(self::RECENTLY_ADDED, $scope)){
            $result[] = [
                'title' => __('Recently added'),
                'key'   => 'recently_added',
                'data'  => $this->_getRecentlyAddedCollection(),
            ];
        }

        if($this->scopeConfig->getValue(self::NEWLY_UPDATED, $scope)){
            $result[] = [
                'title' => __('Newly Updated'),
                'key'   => 'newly_updated',
                'data'  => $this->_getNewlyUPdatedCollection(),
            ];
        }

        $outputData = ['data' => $result, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
        /** @param \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData($outputData);
    }

    /*
     * Get newly updated collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getNewlyUpdatedCollection(){

        $newlyUpdatedCollection = $this->_getProductCollection();
        $newlyUpdatedCollection->getSelect()
            ->order('updated_at DESC');

        return $this->_parseData($newlyUpdatedCollection);
    }

    /*
     * Get recently added collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getRecentlyAddedCollection(){

        $recentlyAddedCollection = $this->_getProductCollection();
        $recentlyAddedCollection->getSelect()
            ->order('created_at DESC');

        return $this->_parseData($recentlyAddedCollection);
    }

    /*
     * Get most viewed collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getMostViewedCollection(){
        $eventTypes = $this->_objectManager
            ->create('Magento\Reports\Model\ResourceModel\Event\Type\Collection');

        foreach ($eventTypes as $eventType) {
            if ($eventType->getEventName() == 'catalog_product_view') {
                $productViewEvent = (int)$eventType->getId();
                break;
            }
        }

        $mostViewedCollection = $this->_getProductCollection();
        $mostViewedCollection->getSelect()
            ->join(
                ['report_table_views' => $mostViewedCollection->getTable('report_event')],
                'e.entity_id = report_table_views.object_id',
                ['views' => 'COUNT(report_table_views.event_id)'])
            ->where('report_table_views.event_type_id = ?', $productViewEvent)
            ->group('e.entity_id')
            ->order('views DESC')
            ->having('COUNT(report_table_views.event_id) > ?', 0);

        return $this->_parseData($mostViewedCollection);
    }

    /*
     * Get best seller collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getBestSellerCollection(){
        $bestSellerCollection = $this->_getProductCollection();
        $bestSellerCollection->getSelect()
            ->join(
                ['order_items' => $bestSellerCollection->getTable('sales_order_item')],
                'e.entity_id = order_items.product_id',
                ['ordered_qty' => 'SUM(order_items.qty_ordered)'])
            ->join(
                ['order' => $bestSellerCollection->getTable('sales_order')],
                'order.entity_id = order_items.order_id',
                [])
            ->where('parent_item_id IS NULL AND order.state <> ?', \Magento\Sales\Model\Order::STATE_CANCELED)
            ->group('e.entity_id')
            ->order('ordered_qty DESC')
            ->having('SUM(order_items.qty_ordered) > ?', 0);

        return $this->_parseData($bestSellerCollection);
    }


    /*
     * Format collection
     *
     * @param $collection \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @return array
     */
    protected function _parseData($collection){
        $items = [];
        foreach($collection as $item){
            $items[] = [
                'product_id' => $item->getSku(),
                'product_name' => $item->getName(),
                'product_image' => $this->_getImageUrl($item->getImage()),
                'product_regular_price' => $item->getData('price'),
                'product_price' => $item->getData('final_price'),
            ];
        }
        return $items;
    }

    /*
     * Get product collection
     *
     * @return
     */
    protected function _getProductCollection(){
        $data = $this->getRequest()->getParam('data');
        $params = $data?json_decode($data, true):[];

        $collection = $this->_objectManager
            ->create('Magento\Catalog\Model\ResourceModel\Product\Collection')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('image')
            ->addStoreFilter()
            ->addAttributeToFilter('status', 1)
            ->setVisibility([3, 4])
            ->addFinalPrice();

        $collection = $this->_paging($collection, $params);

        return $collection;
    }
}

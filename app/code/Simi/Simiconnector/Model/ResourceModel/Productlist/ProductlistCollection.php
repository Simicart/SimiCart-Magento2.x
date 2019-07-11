<?php

/**
 * Connector Resource Collection
 */

namespace Simi\Simiconnector\Model\ResourceModel\Productlist;

class ProductlistCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Resource initialization
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\Productlist', 'Simi\Simiconnector\Model\ResourceModel\Productlist');
    }
    
    public function getProductCollection($listModel, $simiObjectManager)
    {
        return $this->getProductCollectionByType(
            $listModel->getData('list_type'),
            $simiObjectManager,
            $listModel->getData('list_products'),
            $listModel
        );
    }

    public function getProductCollectionByType($type, $simiObjectManager, $listProduct = '', $listModel = null)
    {
        $collection = $simiObjectManager->create('Magento\Catalog\Model\Product')->getCollection()
            ->addAttributeToSelect($simiObjectManager->get('Magento\Catalog\Model\Config')
                ->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite();
        switch ($type) {
            //Product List
            case 1:
                $collection->addFieldToFilter(
                    'entity_id',
                    ['in' => explode(',', $listProduct)]
                );
                break;
            //Best seller
            case 2:
                $orderItemTable   = $simiObjectManager->create('\Magento\Framework\App\ResourceConnection')
                    ->getTableName('sales_order_item');
                $collection       = $simiObjectManager->create('Magento\Catalog\Model\Product')->getCollection();
                $select           = $collection->getSelect()
                    ->join(
                        ['order_item' => $orderItemTable],
                        'order_item.product_id = entity_id',
                        ['order_item.product_id', 'order_item.qty_ordered']
                    )
                    ->columns('SUM(qty_ordered) as total_ordered');
                $groupFunction = 'group';
                $select->$groupFunction('order_item.product_id');
                $select->order(['total_ordered DESC']);
                $collection
                    ->addAttributeToSelect($simiObjectManager->get('Magento\Catalog\Model\Config')
                        ->getProductAttributes())
                    ->addMinimalPrice()
                    ->addFinalPrice()
                    ->addTaxPercents()
                    ->addUrlRewrite();
                break;
            //Most Viewed
            case 3:
                $productViewTable = $simiObjectManager->create('\Magento\Framework\App\ResourceConnection')
                    ->getTableName('report_viewed_product_aggregated_yearly');
                $collection       = $simiObjectManager
                    ->create('Magento\Catalog\Model\Product')->getCollection();
                $select           = $collection->getSelect()
                    ->join(
                        ['product_viewed' => $productViewTable],
                        'product_viewed.product_id = entity_id',
                        ['product_viewed.product_id', 'product_viewed.views_num']
                    )
                    ->columns('SUM(views_num) as total_viewed');
                $groupFunction = 'group';
                $select->$groupFunction('product_viewed.product_id');
                $select->order(['total_viewed DESC']);
                $collection
                    ->addAttributeToSelect($simiObjectManager->get('Magento\Catalog\Model\Config')
                        ->getProductAttributes())
                    ->addMinimalPrice()
                    ->addFinalPrice()
                    ->addTaxPercents()
                    ->addUrlRewrite();
                break;
            //New Updated
            case 4:
                $collection->setOrder('updated_at', 'desc');
                break;
            //Recently Added
            case 5:
                $collection->setOrder('created_at', 'desc');
                break;
            //Recently Added
            case 6:
                if ($listModel && $cateId = $listModel->getData('category_id')) {
                    $categoryModel = $simiObjectManager->create('\Magento\Catalog\Model\Category')->load($cateId);
                    if ($categoryModel->getId())
                        $collection->addCategoryFilter($categoryModel);
                    $collection->setOrder('cat_index_position', 'asc');
                }
                break;
            default:
                break;
        }
        $collection->setVisibility(['2', '4']);
        if (!$simiObjectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface')
            ->getValue('cataloginventory/options/show_out_of_stock')) {
            $simiObjectManager->get('Magento\CatalogInventory\Helper\Stock')
                ->addInStockFilterToCollection($collection);
        }
        return $collection;
    }
}

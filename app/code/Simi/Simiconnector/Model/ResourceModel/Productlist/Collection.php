<?php

/**
 * Connector Resource Collection
 */

namespace Simi\Simiconnector\Model\ResourceModel\Productlist;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
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
    
    public function applyAPICollectionFilter($visibilityTable, $typeID, $storeId)
    {
        $this->getSelect()
            ->join(
                ['visibility' => $visibilityTable],
                'visibility.item_id = main_table.productlist_id AND visibility.content_type = '
                . $typeID . ' AND visibility.store_view_id =' . $storeId
            );
        return $this;
    }
}

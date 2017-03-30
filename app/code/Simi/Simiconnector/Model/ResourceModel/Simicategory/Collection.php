<?php

/**
 * Connector Resource Collection
 */

namespace Simi\Simiconnector\Model\ResourceModel\Simicategory;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Resource initialization
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\Simicategory', 'Simi\Simiconnector\Model\ResourceModel\Simicategory');
    }
    
    public function applyAPICollectionFilter($visibilityTable, $typeID, $storeID)
    {
        $this->getSelect()
                ->join(
                    ['visibility' => $visibilityTable],
                    'visibility.item_id = main_table.simicategory_id AND visibility.content_type = '
                    . $typeID . ' AND visibility.store_view_id =' . $storeID
                );
        return $this;
    }
}

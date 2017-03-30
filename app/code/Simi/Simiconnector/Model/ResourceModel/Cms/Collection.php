<?php

/**
 * Connector Resource Collection
 */

namespace Simi\Simiconnector\Model\ResourceModel\Cms;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Resource initialization
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\Cms', 'Simi\Simiconnector\Model\ResourceModel\Cms');
    }
    
    public function applyAPICollectionFilter($visibilityTable, $typeID, $storeID)
    {
        $this->getSelect()
                ->join(
                    ['visibility' => $visibilityTable],
                    'visibility.item_id = main_table.cms_id AND visibility.content_type = '
                    . $typeID . ' AND visibility.store_view_id =' . $storeID
                );
        return $this;
    }
    
    public function getCategoryCMSPages($visibilityTable, $typeID, $storeID)
    {
        $this->getSelect()
                ->join(
                    ['visibility' => $visibilityTable],
                    'visibility.item_id = main_table.cms_id AND visibility.content_type = ' . $typeID
                    . ' AND visibility.store_view_id =' . $storeID
                );
        return $this;
    }
}

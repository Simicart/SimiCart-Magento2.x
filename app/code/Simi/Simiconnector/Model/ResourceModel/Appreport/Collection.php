<?php

/**
 * Connector Resource Collection
 */

namespace Simi\Simiconnector\Model\ResourceModel\Appreport;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Resource initialization
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\Appreport', 'Simi\Simiconnector\Model\ResourceModel\Appreport');
    }
    
    public function getGridCollection($inputObjectManager)
    {
        $orderGrid_table = $inputObjectManager->create('Magento\Framework\App\ResourceConnection')
                ->getTableName('sales_order_grid');
        $this->join(
            ['ordergrid' => $orderGrid_table],
            'main_table.order_id = ordergrid.entity_id',
            ['*']
        );
        return $this;
    }
}

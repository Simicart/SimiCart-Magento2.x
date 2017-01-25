<?php

namespace Simi\Simiconnector\Model\ResourceModel;

/**
 * Connector Resource Model
 */
class Storeviewmultiselect extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $_objectManager;
    
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
    }
    
    public function toArray()
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $groupCollection = $this->_objectManager->get('\Magento\Store\Model\Group')->getCollection();
        $storeCollection = $this->_objectManager->get('\Magento\Store\Model\Store')->getCollection();
        $returnArray = [];
        
        foreach ($groupCollection as $group) {
            $groupOption = ['label'=>$group->getName()];
            $childStore = [];
            foreach ($storeCollection as $store) {
                if ($store->getData('group_id') == $group->getId()) {
                    $childStore[] = ['value'=>$store->getId(), 'label'=>$store->getName()];
                }
            }
            $groupOption['value'] = $childStore;
            $returnArray[]=$groupOption;
        }
        return $returnArray;
    }
}

<?php

namespace Simi\Simiconnector\Model\ResourceModel;

/**
 * Connector Resource Model
 */
class Storeviewmultiselect extends \Magento\Framework\Model\AbstractModel
{

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    public $simiObjectManager;

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
   
        $this->simiObjectManager = $simiObjectManager;
        $this->storeManager     = $this->simiObjectManager->get('Magento\Store\Model\StoreManagerInterface');
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function toOptionArray()
    {
        $groupCollection = $this->simiObjectManager->get('\Magento\Store\Model\Group')->getCollection();
        $storeCollection = $this->simiObjectManager->get('\Magento\Store\Model\Store')->getCollection();
        $returnArray     = [];

        foreach ($groupCollection as $group) {
            $groupOption = ['label' => $group->getName()];
            $childStore  = [];
            foreach ($storeCollection as $store) {
                if ($store->getData('group_id') == $group->getId()) {
                    $childStore[] = ['value' => $store->getId(), 'label' => $store->getName()];
                }
            }
            $groupOption['value'] = $childStore;
            $returnArray[]        = $groupOption;
        }
        return $returnArray;
    }
}

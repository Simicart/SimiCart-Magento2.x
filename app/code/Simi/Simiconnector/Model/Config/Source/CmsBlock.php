<?php

namespace Simi\Simiconnector\Model\Config\Source;

class CmsBlock implements \Magento\Framework\Option\ArrayInterface
{
    protected $collectionFactory;

    public function __construct(
        \Magento\Cms\Model\ResourceModel\Block\CollectionFactory $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
    }


    public function toOptionArray()
    {
        $cmsBlock = $this->collectionFactory->create()->addFieldToFilter('is_active', 1);
        $options = [
            ['value' => '', 'label' => __('Please select block cms')]
        ];

        foreach ($cmsBlock as $block) {
            $options[] = ['value' => $block->getIdentifier(), 'label' => $block->getTitle()];
        }

        return $options;
    }
}
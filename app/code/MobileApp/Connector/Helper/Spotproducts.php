<?php

/**
 * Connector spotproducts helper
 */
namespace MobileApp\Connector\Helper;

class Spotproducts extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var best seller collection
     */
    protected $_bestsellerCollectionFactory;

    /**
     * @var most viewed collection
     */
    protected $_mostviewedCollectionFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory $bestsellerCollectionFactory,
        \Magento\Reports\Model\ResourceModel\Report\Product\Viewed\CollectionFactory $viewedCollectionFactory
    )
    {
        $this->_bestsellerCollectionFactory = $bestsellerCollectionFactory;
        $this->_mostviewedCollectionFactory = $viewedCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @return Best Seller Collection
     */
    public function getBestSellerCollection(){
        $collection = $this->_bestsellerCollectionFactory->create()->setModel(
            'Magento\Catalog\Model\Product'
        );

        return $collection;
    }

    /**
     * @return Most Viewed Collection
     */
    public function getMostViewedCollection(){
        $collection = $this->_mostviewedCollectionFactory->create();

        return $collection;
    }

}

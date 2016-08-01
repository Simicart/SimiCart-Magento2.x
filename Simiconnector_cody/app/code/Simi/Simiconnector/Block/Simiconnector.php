<?php

namespace Simi\Simisimiconnector\Block;

/**
 * Simisimiconnector content block
 */
class Simisimiconnector extends \Magento\Framework\View\Element\Template
{
    /**
     * Simisimiconnector collection
     *
     * @var Simi\Simisimiconnector\Model\ResourceModel\Simisimiconnector\Collection
     */
    protected $_simiconnectorCollection = null;
    
    /**
     * Simisimiconnector factory
     *
     * @var \Simi\Simisimiconnector\Model\SimisimiconnectorFactory
     */
    protected $_simiconnectorCollectionFactory;
    
    /** @var \Simi\Simisimiconnector\Helper\Data */
    protected $_dataHelper;
    
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Simi\Simisimiconnector\Model\ResourceModel\Simisimiconnector\CollectionFactory $simiconnectorCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Simi\Simisimiconnector\Model\ResourceModel\Simisimiconnector\CollectionFactory $simiconnectorCollectionFactory,
        \Simi\Simisimiconnector\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->_simiconnectorCollectionFactory = $simiconnectorCollectionFactory;
        $this->_dataHelper = $dataHelper;
        parent::__construct(
            $context,
            $data
        );
    }
    
    /**
     * Retrieve simiconnector collection
     *
     * @return Simi\Simisimiconnector\Model\ResourceModel\Simisimiconnector\Collection
     */
    protected function _getCollection()
    {
        $collection = $this->_simiconnectorCollectionFactory->create();
        return $collection;
    }
    
    /**
     * Retrieve prepared simiconnector collection
     *
     * @return Simi\Simisimiconnector\Model\ResourceModel\Simisimiconnector\Collection
     */
    public function getCollection()
    {
        if (is_null($this->_simiconnectorCollection)) {
            $this->_simiconnectorCollection = $this->_getCollection();
            $this->_simiconnectorCollection->setCurPage($this->getCurrentPage());
            $this->_simiconnectorCollection->setPageSize($this->_dataHelper->getSimisimiconnectorPerPage());
            $this->_simiconnectorCollection->setOrder('published_at','asc');
        }

        return $this->_simiconnectorCollection;
    }
    
    /**
     * Fetch the current page for the simiconnector list
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->getData('current_page') ? $this->getData('current_page') : 1;
    }
    
    /**
     * Return URL to item's view page
     *
     * @param Simi\Simisimiconnector\Model\Simisimiconnector $simiconnectorItem
     * @return string
     */
    public function getItemUrl($simiconnectorItem)
    {
        return $this->getUrl('*/*/view', array('id' => $simiconnectorItem->getId()));
    }
    
    /**
     * Return URL for resized Simisimiconnector Item image
     *
     * @param Simi\Simisimiconnector\Model\Simisimiconnector $item
     * @param integer $width
     * @return string|false
     */
    public function getImageUrl($item, $width)
    {
        return $this->_dataHelper->resize($item, $width);
    }
    
    /**
     * Get a pager
     *
     * @return string|null
     */
    public function getPager()
    {
        $pager = $this->getChildBlock('simiconnector_list_pager');
        if ($pager instanceof \Magento\Framework\Object) {
            $simiconnectorPerPage = $this->_dataHelper->getSimisimiconnectorPerPage();

            $pager->setAvailableLimit([$simiconnectorPerPage => $simiconnectorPerPage]);
            $pager->setTotalNum($this->getCollection()->getSize());
            $pager->setCollection($this->getCollection());
            $pager->setShowPerPage(TRUE);
            $pager->setFrameLength(
                $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->setJump(
                $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame_skip',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            );

            return $pager->toHtml();
        }

        return NULL;
    }
}

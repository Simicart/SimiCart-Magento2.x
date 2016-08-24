<?php

/**
 * Connector website helper
 */
namespace Simi\Simiconnector\Helper;

class Website extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Simi\Simiconnector\Model\Simiconnector
     */
    protected $_websiteFactory;

    /**
     * @var \Simi\Simiconnector\Model\Simiconnector
     */
    protected $_websiteRepositoryFactory;

    /**
     * @var https|http
     */
    protected $_request;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\Collection
     **/
    protected $_countryCollectionFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\ResourceModel\Website\CollectionFactory $websiteFactory,
        \Magento\Store\Model\WebsiteRepositoryFactory $websiteRepositoryFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
    )
    {
        $this->_request = $request;
        $this->_websiteFactory = $websiteFactory;
        $this->_websiteRepositoryFactory = $websiteRepositoryFactory;
        $this->_countryCollectionFactory = $countryCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @return int|mixed
     */
    public function getWebsiteIdFromUrl()
    {
        $website_id = $this->_request->getParam('website_id');
        if ($website_id != null)
            return $website_id;
        else
            return $this->getDefaultWebsite()->getId();
    }

    /**
     * @return \Magento\Framework\DataObject|\Magento\Store\Api\Data\WebsiteInterface[]
     */
    public function getDefaultWebsite()
    {
        $website = $this->_websiteRepositoryFactory->create()->getDefault();
        return $website;
    }

    /**
     * @return \Magento\Store\Model\ResourceModel\Website\Collection
     */
    public function getWebsiteCollection(){
        return $this->_websiteFactory->create();
    }

    /**
     * @return \Magento\Directory\Model\ResourceModel\Country\Collection
     */
    public function getCountryCollection(){
        return $this->_countryCollectionFactory->create();
    }
}

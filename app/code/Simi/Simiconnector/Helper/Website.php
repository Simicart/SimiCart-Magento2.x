<?php

/**
 * Connector website helper
 */

namespace Simi\Simiconnector\Helper;

class Website extends \Magento\Framework\App\Helper\AbstractHelper
{

    public $simiObjectManager;
    /**
     * @var \Simi\Simiconnector\Model\Simiconnector
     */
    public $websiteFactory;

    /**
     * @var \Simi\Simiconnector\Model\Simiconnector
     */
    public $websiteRepositoryFactory;

    /**
     * @var https|http
     */
    public $request;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\Collection
     * */
    public $countryCollectionFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\ResourceModel\Website\CollectionFactory $websiteFactory,
        \Magento\Store\Model\WebsiteRepositoryFactory $websiteRepositoryFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
    ) {
   
        $this->simiObjectManager  = $simiObjectManager;
        $this->request                  = $this->simiObjectManager->get('\Magento\Framework\App\Request\Http');
        $this->websiteFactory           = $websiteFactory;
        $this->websiteRepositoryFactory = $websiteRepositoryFactory;
        $this->countryCollectionFactory = $countryCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @return int|mixed
     */
    public function getWebsiteIdFromUrl()
    {
        $website_id = $this->request->getParam('website_id');
        if ($website_id != null) {
            return $website_id;
        } else {
            return $this->getDefaultWebsite()->getId();
        }
    }

    /**
     * @return \Magento\Framework\DataObject|\Magento\Store\Api\Data\WebsiteInterface[]
     */
    public function getDefaultWebsite()
    {
        $website = $this->websiteRepositoryFactory->create()->getDefault();
        return $website;
    }

    /**
     * @return \Magento\Store\Model\ResourceModel\Website\Collection
     */
    public function getWebsiteCollection()
    {
        return $this->websiteFactory->create();
    }

    /**
     * @return \Magento\Directory\Model\ResourceModel\Country\Collection
     */
    public function getCountryCollection()
    {
        return $this->countryCollectionFactory->create();
    }
}

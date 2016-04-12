<?php
namespace MobileApp\Connector\Block\Adminhtml\Connector;

/**
 * Admin Connector page
 *
 */
class Website extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \MobileApp\Connector\Model\Connector
     */
    protected $_websiteFactory;


    /** @var \MobileApp\Connector\Helper\Website */
    protected $_websiteHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Model\ResourceModel\Website\CollectionFactory $websiteFactory,
        \MobileApp\Connector\Helper\Website $websiteHelper,
        array $data = []
    ) {
        $this->_websiteFactory = $websiteFactory;
        $this->_websiteHelper = $websiteHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Store\Model\ResourceModel\Website\Collection
     */
    public function getWebsiteCollection()
    {
        $collection = $this->_websiteFactory->create();
        return $collection;
    }

    /**
     * @return string
     */
    public function getFormUrl(){
        return $this->getUrl('*/*/');
    }

    /**
     * @return mixed
     */
    public function getWebsiteIdFromUrl(){
        return $this->_websiteHelper->getWebsiteIdFromUrl();
    }

}

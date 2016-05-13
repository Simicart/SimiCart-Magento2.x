<?php
namespace MobileApp\Connector\Block\Adminhtml\Connector;

/**
 * Admin Connector page
 *
 */
class Key extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \MobileApp\Connector\Model\Connector
     */
    protected $_websiteFactory;


    /** @var \MobileApp\Connector\Helper\Website */
    protected $_websiteHelper;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Model\ResourceModel\Website\CollectionFactory $websiteFactory,
        \MobileApp\Connector\Helper\Website $websiteHelper,
        \Magento\Framework\Data\Form\FormKey $formKey,
        array $data = []
    ) {
        $this->_websiteFactory = $websiteFactory;
        $this->_websiteHelper = $websiteHelper;
        $this->formKey = $formKey;
        parent::__construct($context, $data);
    }

    /**
     * Get form key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * @return string
     */
    public function getFormUrl(){
        return $this->getUrl('*/*/saveKey');
    }

    /**
     * @return mixed
     */
    public function getWebsiteIdFromUrl(){
        return $this->_websiteHelper->getWebsiteIdFromUrl();
    }

}



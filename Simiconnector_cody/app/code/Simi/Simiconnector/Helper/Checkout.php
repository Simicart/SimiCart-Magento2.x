<?php

/**
 * Connector data helper
 */
namespace Simi\Simiconnector\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Checkout extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_scopeConfig;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\File\Size $fileSize,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\Filesystem\Io\File $ioFile,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Image\Factory $imageFactory
    ) {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }
    
   
    /*
     * Get Checkout Terms And Conditions
     */
    public function getCheckoutTermsAndConditions() {
        if (!$this->getStoreConfig('simiconnector/terms_conditions/enable_terms'))
            return NULL;
        $data = array();
        $data['title'] = $this->getStoreConfig('simiconnector/terms_conditions/term_title');
        $data['content'] = $this->getStoreConfig('simiconnector/terms_conditions/term_html');
        return $data;
    }

    
    public function getStoreConfig($path) {
        return $this->_scopeConfig->getValue($path);
    }
}


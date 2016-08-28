<?php

/**
 * Connector data helper
 */
namespace Simi\Simiconnector\Helper;


class Options extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_objectManager;
    protected $_catalogHelper;
    protected $_scopeConfig;
    public $priceCurrency;
    public $priceHelper;
    
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\File\Size $fileSize,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\Filesystem\Io\File $ioFile,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Image\Factory $imageFactory,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_scopeConfig = $this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->filesystem = $filesystem;
        $this->httpFactory = $httpFactory;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_ioFile = $ioFile;
        $this->_storeManager = $storeManager;
        $this->_imageFactory = $imageFactory;
        $this->_catalogHelper = $catalogData;
        $this->priceCurrency = $priceCurrency;
        $this->priceHelper = $pricingHelper;
        parent::__construct($context);
    }
    
    public function helper($helper)
    {
        return $this->_objectManager->get('Simi\Simiconnector\Helper\Options\\'.$helper);
    }
    
    public function currency($value, $format = true, $includeContainer = true)
    {
        return $this->priceHelper->currencyByStore($value, $format, $includeContainer);
    }
    
    public function getOptions($product)
    {
        $type = $product->getTypeId();
        switch ($type) {
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
                return $this->helper('Simple')->getOptions($product);
                break;
            case \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE :
                return $this->helper('Bundle')->getOptions($product);
                break;
            case 'grouped' :
                return $this->helper('Grouped')->getOptions($product);
                break;
            case 'configurable' :
                return $this->helper('Configurable')->getOptions($product);
                break;
            case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL :
                return $this->helper('Simple')->getOptions($product);
                break;
            case "downloadable" :
                return $this->helper('Download')->getOptions($product);
                break;
        }
    }
    
}


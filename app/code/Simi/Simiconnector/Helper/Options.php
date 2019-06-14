<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

class Options extends \Magento\Framework\App\Helper\AbstractHelper
{

    public $simiObjectManager;
    public $catalogHelper;
    public $scopeConfig;
    public $priceCurrency;
    public $priceHelper;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Image\Factory $imageFactory,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper
    ) {
   
        $this->simiObjectManager    = $simiObjectManager;
        $this->scopeConfig         = $this->simiObjectManager
                ->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->filesystem           = $filesystem;
        $this->httpFactory          = $httpFactory;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->storeManager        = $storeManager;
        $this->_imageFactory        = $imageFactory;
        $this->catalogHelper       = $catalogData;
        $this->priceCurrency        = $priceCurrency;
        $this->priceHelper          = $pricingHelper;
        parent::__construct($context);
    }

    public function helper($helper)
    {
        return $this->simiObjectManager->get('Simi\Simiconnector\Helper\Options\\' . $helper);
    }

    public function currency($value, $format = true, $includeContainer = true)
    {
        return $this->priceHelper->currencyByStore($value, null, $format, $includeContainer);
    }

    public function getOptions($product)
    {
        $type = $product->getTypeId();
        switch ($type) {
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
                return $this->helper('Simple')->getOptions($product);
            case \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE:
                return $this->helper('Bundle')->getOptions($product);
            case 'grouped':
                return $this->helper('Grouped')->getOptions($product);
            case 'configurable':
                return $this->helper('Configurable')->getOptions($product);
            case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL:
                return $this->helper('Simple')->getOptions($product);
            case "downloadable":
                return $this->helper('Download')->getOptions($product);
        }
    }
}

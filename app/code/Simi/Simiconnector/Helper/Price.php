<?php

/**
 * Connector data helper
 */
namespace Simi\Simiconnector\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Price extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_product = null;
    protected $_catalogHelper = null;
    protected $_coreRegistry;
    protected $_scopeConfig;


    public $priceCurrency = null;
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
        \Magento\Framework\Pricing\Helper\Data $pricingHelper, 
        \Magento\Framework\Registry $registry
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_scopeConfig = $this->_objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->filesystem = $filesystem;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->httpFactory = $httpFactory;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_ioFile = $ioFile;
        $this->_storeManager = $storeManager;
        $this->_imageFactory = $imageFactory;
        $this->_catalogHelper = $catalogData;
        $this->priceCurrency = $priceCurrency;
        $this->priceHelper = $pricingHelper;
	$this->_coreRegistry = $registry;
        parent::__construct($context);
    }
    
    public function getData()
    {
        return $this->_coreRegistry->registry('simidata');
    }
    
    public function helper($helper)
    {
        return $this->_objectManager->create($helper);
    }

    public function currency($value, $format = true, $includeContainer = true)
    {
        return $this->priceHelper->currencyByStore($value, $format, $includeContainer);
    }
    
    public function getProductAttribute($attribute) {
        return $this->_product->getResource()->getAttribute($attribute);
    }

    
    public function convertPrice($price, $format = false)
    {
        $data = $this->getData();
        if (isset($data['resourceid']) && ($data['resourceid'] == 'products'))
            return $price;
        return $format
            ? $this->priceCurrency->convertAndFormat($price)
            : $this->priceCurrency->convert($price);
    }
    
    public function formatPriceFromProduct($_product, $is_detail=false)
    {
        $priveV2 = array();
        $_product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($_product->getId());
        $this->_product = $_product;

        $_weeeHelper = $this->helper('Magento\Weee\Helper\Data');
        $_taxHelper = $this->helper('Magento\Tax\Helper\Data');
        
        $_simplePricesTax = ($_taxHelper->displayPriceIncludingTax() || $_taxHelper->displayBothPrices());
        $_minimalPrice = $this->convertPrice($_product->getMinimalPrice());
        $_convertedFinalPrice = $this->convertPrice($_product->getFinalPrice());
        $_specialPriceStoreLabel = $this->getProductAttribute('special_price')->getStoreLabel();

        if ($_product->getTypeId() == "bundle") {
            return $this->helper('\Simi\Simiconnector\Helper\Bundle\Price')->formatPriceFromProduct($_product, $is_detail);
        }

        if ($_product->getTypeId()!= 'grouped') {
            $_weeeTaxAmount = $_weeeHelper->getAmountExclTax($_product);
            $_weeeTaxAttributes = $_weeeHelper->getProductWeeeAttributesForRenderer($_product, null, null, null, true);
            $_weeeTaxAmountInclTaxes = $_weeeTaxAmount;
            
            if ($_weeeHelper->isTaxable()) {
                //$_weeeTaxAmountInclTaxes = $_weeeHelper->getAmountInclTaxes($_weeeTaxAttributes);
            }
            $_weeeTaxAmount = $this->convertPrice($_weeeTaxAmount);
            $_weeeTaxAmountInclTaxes = $this->convertPrice($_weeeTaxAmountInclTaxes);

            //price box
            $_convertedPrice = $this->convertPrice($_product->getData('price'));
            $_price = $this->_catalogHelper->getTaxPrice($_product, $_convertedPrice);
            $_regularPrice = $this->_catalogHelper->getTaxPrice($_product, $_convertedPrice, $_simplePricesTax);
            $_finalPrice = $this->_catalogHelper->getTaxPrice($_product, $_convertedFinalPrice);
            $_finalPriceInclTax = $this->_catalogHelper->getTaxPrice($_product, $_convertedFinalPrice, true);
            $_weeeDisplayType = $_weeeHelper->getPriceDisplayType();
            if ($_finalPrice >= $_price) {
                $priveV2['has_special_price'] = 0;
                if ($_taxHelper->displayBothPrices()) {
                    $priveV2['show_ex_in_price'] = 1;
                    if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 0)) {
                        $_exclTax = $_price + $_weeeTaxAmount;
                        $_inclTax = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
                        $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
                    }elseif($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 1)){
                        $wee ='';
                        foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
                            $wee .= $_weeeTaxAttribute->getName();
                            $wee .= ": ";
                            $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
                            $wee .= " + ";
                            $priveV2["weee"] = $wee;
                        }
                        $this->setWeePrice($priveV2, $wee);
                        $_exclTax = $_price + $_weeeTaxAmount;
                        $_inclTax = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
                        $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
                        //$priveV2['show_type'] = 1;
                        $priveV2['show_weee_price'] = 1;
                    }elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 4)){
                        $wee ='';
                        foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
                            $wee .= $_weeeTaxAttribute->getName();
                            $wee .= ": ";
                            $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
                            $wee .= " + ";
                            $priveV2["weee"] = $wee;
                        }
                        $this->setWeePrice($priveV2, $wee);
                        $_exclTax = $_price + $_weeeTaxAmount;
                        $_inclTax = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
                        $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
                        //$priveV2['show_type'] = 2;
                        $priveV2['show_weee_price'] = 2;
                    }elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 2)){
                        $wee ='';
                        foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
                            $wee .= $_weeeTaxAttribute->getName();
                            $wee .= ": ";
                            $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
                            $wee .= " <br/> ";
                            $priveV2["weee"] = $wee;
                        }
                        $this->setWeePrice($priveV2, $wee);
                        $_exclTax = $_price;
                        $_inclTax = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
                        $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
                        $priveV2['show_weee_price'] = 1;
                    }else{
                        $_exclTax = $_finalPrice;
                        if ($_finalPrice == $_price){
                            $_exclTax = $_price;
                        }
                        $_inclTax = $_finalPriceInclTax;
                        $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
                    }
                }else{
                    $priveV2['show_ex_in_price'] = 0;
                    if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, array(0, 1))){
                        $priveV2['price_label'] = __('Regular Price');
                        $weeeAmountToDisplay = $_taxHelper->displayPriceIncludingTax() ? $_weeeTaxAmountInclTaxes : $_weeeTaxAmount;
                        $this->setTaxReguarlPrice($priveV2, $_price + $weeeAmountToDisplay);
                        if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 1)){
                            $wee ='';
                            foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
                                $wee .= $_weeeTaxAttribute->getName();
                                $wee .= ": ";
                                $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
                                $wee .= " + ";
                                $priveV2["weee"] = $wee;
                            }
                            //$priveV2['show_type'] = 4;
                            $priveV2['show_weee_price'] = 1;
                        }
                    }elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 4)){
                        $priveV2['price_label'] = __('Regular Price');
                        $this->setTaxReguarlPrice($priveV2, $_price + $_weeeTaxAmount);
                        if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 1)){
                            $wee ='';
                            foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
                                $wee .= $_weeeTaxAttribute->getName();
                                $wee .= ": ";
                                $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
                                $wee .= " + ";
                                $priveV2["weee"] = $wee;
                            }
                            $priveV2['show_weee_price'] = 1;
                        }
                    }elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 2)){
                        $priveV2['price_label'] = __('Regular Price');
                        $weeeAmountToDisplay = $_taxHelper->displayPriceIncludingTax() ? $_weeeTaxAmountInclTaxes : $_weeeTaxAmount;
                        $this->setTaxReguarlPrice($priveV2, $_price + $weeeAmountToDisplay);
                        if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 1)){
                            $wee ='';
                            foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
                                $wee .= $_weeeTaxAttribute->getName();
                                $wee .= ": ";
                                $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
                                $wee .= " <br/> ";
                                $priveV2["weee"] = $wee;
                            }
                            $priveV2['show_weee_price'] = 2;
                        }
                    }else{
						$priveV2['price_label'] = __('Regular Price');
                        if ($_finalPrice == $_price){
                            $this->setTaxPrice($priveV2, $_finalPrice);
                        }else{
                            $this->setTaxPrice($priveV2, $_price);
                        }
                    }
                }
            }else{  /* if ($_finalPrice == $_price): */
                $priveV2['has_special_price'] = 1;
                $_originalWeeeTaxAmount = $_weeeHelper->getAmountExclTax($_product);
                $_originalWeeeTaxAmount = $this->convertPrice($_originalWeeeTaxAmount);
                if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 0)){
                    $priveV2['price_label'] = __('Regular Price');
                    $this->setTaxReguarlPrice($priveV2, $_regularPrice + $_originalWeeeTaxAmount);
                    if ($_taxHelper->displayBothPrices()){
                        $priveV2['show_ex_in_price'] = 1;
                        $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                        $_exclTax = $_finalPrice + $_weeeTaxAmount;
                        $_inclTax = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
                        $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
                    }else{
                        $priveV2['show_ex_in_price'] = 0;
                        $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                        $this->setTaxPrice($priveV2, $_finalPrice + $_weeeTaxAmountInclTaxes);
                    }
                }elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 1)){
                    $priveV2['price_label'] = __('Regular Price');
                    $this->setTaxReguarlPrice($priveV2, $_regularPrice + $_originalWeeeTaxAmount);
                    if ($_taxHelper->displayBothPrices()){
                        $priveV2['show_ex_in_price'] = 1;
                        $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                        $_exclTax = $_finalPrice + $_weeeTaxAmount;
                        $_inclTax = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
                        $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
                        $wee ='';
                        foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
                            $wee .= $_weeeTaxAttribute->getName();
                            $wee .= ": ";
                            $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
                            $wee .= " + ";
                            $priveV2["weee"] = $wee;
                        }
                        $this->setWeePrice($priveV2, $wee);
                        $priveV2['show_weee_price'] = 1;
                    }else{
                        $priveV2['show_ex_in_price'] = 0;
                        $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                        $this->setTaxPrice($priveV2, $_finalPrice + $_weeeTaxAmountInclTaxes);
                        $wee ='';
                        foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
                            $wee .= $_weeeTaxAttribute->getName();
                            $wee .= ": ";
                            $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
                            $wee .= " + ";
                            $priveV2["weee"] = $wee;
                        }
                        $this->setWeePrice($priveV2, $wee);
                        $priveV2['show_weee_price'] = 1;
                    }
                }elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 4)){
                    $priveV2['show_ex_in_price'] = 1;
                    $priveV2['price_label'] = __('Regular Price');
                    $this->setTaxReguarlPrice($priveV2, $_regularPrice + $_originalWeeeTaxAmount);
                    $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                    $_exclTax = $_finalPrice + $_weeeTaxAmount;
                    $_inclTax = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
                    $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
                    $wee ='';
                    foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
                        $wee .= $_weeeTaxAttribute->getName();
                        $wee .= ": ";
                        $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
                        $wee .= " + ";
                        $priveV2["weee"] = $wee;
                    }
                    $this->setWeePrice($priveV2, $wee);
                    $priveV2['show_weee_price'] = 1;
                }elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, 2)){
                    $priveV2['show_ex_in_price'] = 1;
                    $priveV2['price_label'] = __('Regular Price');
                    $this->setTaxReguarlPrice($priveV2, $_regularPrice);
                    $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                    $_exclTax = $_finalPrice;
                    $_inclTax = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
                    $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
                    $wee ='';
                    foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
                        $wee .= $_weeeTaxAttribute->getName();
                        $wee .= ": ";
                        $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
                        $wee .= " <br/> ";
                        $priveV2["weee"] = $wee;
                    }
                    $this->setWeePrice($priveV2, $wee);
                    $priveV2['show_weee_price'] = 1;
                }else{
                    $priveV2['price_label'] = __('Regular Price');
                    $this->setTaxReguarlPrice($priveV2, $_regularPrice);
                    if ($_taxHelper->displayBothPrices()){
                        $priveV2['show_ex_in_price'] = 1;
                        $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                        $_exclTax = $_finalPrice;
                        $_inclTax = $_finalPriceInclTax;
                        $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
                    }else{
                        $priveV2['show_ex_in_price'] = 0;
                        $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                        $this->setTaxPrice($priveV2, $_finalPrice);
                    }
                }
            }//end /* if ($_finalPrice == $_price): */
            if ($this->getDisplayMinimalPrice() && $_minimalPrice && $_minimalPrice < $_convertedFinalPrice){
                $_minimalPriceDisplayValue = $_minimalPrice;
                if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($_product, array(0, 1, 4))){
                    $_minimalPriceDisplayValue = $_minimalPrice + $_weeeTaxAmount;
                    $priveV2['is_low_price'] = 1;
                    $priveV2['low_price_label'] = __('As low as');
                    $this->setTaxLowPrice($priveV2, $_minimalPriceDisplayValue);
                }
            }
        } else { // group product
            $showMinPrice = $this->getDisplayMinimalPrice();
            if ($showMinPrice && $_minimalPrice) {
                $_exclTax = $this->_catalogHelper->getTaxPrice($_product, $_minimalPrice);
                $_inclTax = $this->_catalogHelper->getTaxPrice($_product, $_minimalPrice, true);
                $price = $showMinPrice ? $_minimalPrice : 0;
            } else {
                $price = $_convertedFinalPrice;
                $_exclTax = $this->_catalogHelper->getTaxPrice($_product, $price);
                $_inclTax = $this->_catalogHelper->getTaxPrice($_product, $price, true);
            }

            if ($price) {
                if ($showMinPrice) {
                    $priveV2['price_label'] = __('Starting at');
                }
                if ($_taxHelper->displayBothPrices()) {
                    $priveV2['show_ex_in_price'] = 1;
                    $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
                } else {
                    $priveV2['show_ex_in_price'] = 0;
                    $_showPrice = $_inclTax;
                    if (!$_taxHelper->displayPriceIncludingTax()) {
                        $_showPrice = $_exclTax;
                    }
                    $this->setTaxPrice($priveV2, $_showPrice);
                }
            }
        }
        
        return $priveV2;
    }

    public function getDisplayMinimalPrice()
    {
        if ($this->_product)
            return $this->_product->getMinimalPrice();
        return 0;
    }

    /**
     * @param $price
     * @param $_price
     * show type
     * 3 show price only.
     * 4 show price - wee.
     * 5 show wee - price.
     */
    public function setTaxReguarlPrice(&$price, $_price)
    {
        $price['regular_price'] = $_price;
    }

    /**
     * @param $price
     * @param $_price
     * show type
     * 3 show price only.
     * 4 show price - wee.
     * 5 show wee - price.
     */
    public function setTaxPrice(&$price, $_price)
    {
        $price['price'] = $_price;
    }

    public function setTaxLowPrice(&$price, $_price)
    {
        $price['low_price'] = $_price;
    }
    /**
     * @param $price
     * @param $_exclTax
     * @param $_inclTax
     * type
     * 0 show price only
     * 1 show ex + wee + in
     * 2 show  ex + in + wee
     */
    public function setBothTaxPrice(&$price, $_exclTax, $_inclTax)
    {
        $price['price_excluding_tax'] = array(
            'label' => __('Excl. Tax'),
            'price' => $_exclTax,
        );
        $price['price_including_tax'] = array(
            'label' => __('Incl. Tax'),
            'price' => $_inclTax,
        );
    }

    public function setWeePrice(&$price, $wee)
    {
        $price['wee'] = $wee;
    }
    
}


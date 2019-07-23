<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Price extends \Magento\Framework\App\Helper\AbstractHelper
{

    public $product       = null;
    public $catalogHelper = null;
    public $coreRegistry;
    public $scopeConfig;
    public $priceCurrency  = null;
    public $priceHelper;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Image\Factory $imageFactory,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\Registry $registry
    ) {

        $this->simiObjectManager        = $simiObjectManager;
        $this->scopeConfig         = $this->simiObjectManager
            ->create('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->filesystem           = $filesystem;
        $this->mediaDirectory       = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->httpFactory          = $httpFactory;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->storeManager        = $storeManager;
        $this->_imageFactory        = $imageFactory;
        $this->catalogHelper       = $catalogData;
        $this->priceCurrency        = $priceCurrency;
        $this->priceHelper          = $pricingHelper;
        $this->coreRegistry        = $registry;
        parent::__construct($context);
    }

    public function getData()
    {
        return $this->coreRegistry->registry('simidata');
    }

    public function helper($helper)
    {
        return $this->simiObjectManager->create($helper);
    }

    public function currency($value, $format = true, $includeContainer = true)
    {
        return $this->priceHelper->currencyByStore($value, $format, $includeContainer);
    }

    public function convertPrice($price, $format = false)
    {
        if ($format) {
            return $this->priceCurrency->convertAndFormat($price);
        } else {
            $price = $this->priceCurrency->convert($price);
            return $this->priceCurrency->round($price);
        }
    }

    public function getProductAttribute($attribute)
    {
        return $this->product->getResource()->getAttribute($attribute);
    }

    public function getStoreConfig($path)
    {
        return $this->scopeConfig->getValue($path,\Magento\Store\Model\ScopeInterface::SCOPE_STORE,$this->storeManager->getStore()->getCode());
    }

    public function formatPriceFromProduct($product, $is_detail = false)
    {
        $priveV2        = [];
        //$product       = $this->simiObjectManager->create('Magento\Catalog\Model\Product')->load($product->getId());
        $this->product = $product;
        $_taxHelper  = $this->helper('Magento\Tax\Helper\Data');
        /*
        * Rounded final price excluded tax
        */
        $finalPrice = $this->product->getPriceInfo()->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE);
        $_convertedFinalPrice = $this->priceCurrency->round($finalPrice->getAmount()->getBaseAmount());
        $_specialPriceStoreLabel = $this->getProductAttribute('special_price')->getStoreLabel();

        /*
        *   Bundle Product
        */
        if ($product->getTypeId() == "bundle") {
            return $this->helper('\Simi\Simiconnector\Helper\Bundle\Price')
                ->formatPriceFromProduct($product, $is_detail);
        }
        /*
        *   Simple/Configurable/Virtual/Downloadable
        */
        if ($product->getTypeId() != 'grouped') {
            if($product->getTypeId() == 'configurable'){
                $_price  = $this->priceCurrency->round($product->getPriceInfo()
                    ->getPrice(\Magento\ConfigurableProduct\Pricing\Price\ConfigurableRegularPrice::PRICE_CODE)
                    ->getAmount()->getBaseAmount());
                $_regularPrice      = $this->priceCurrency->round($product->getPriceInfo()
                    ->getPrice(\Magento\ConfigurableProduct\Pricing\Price\ConfigurableRegularPrice::PRICE_CODE)
                    ->getAmount()->getValue());
            } else {
                $_price             = $this->priceCurrency->round($product->getPriceInfo()
                    ->getPrice(\Magento\Catalog\Pricing\Price\RegularPrice::PRICE_CODE)
                    ->getAmount()->getBaseAmount());
                $_regularPrice      = $this->priceCurrency->round($product->getPriceInfo()
                    ->getPrice(\Magento\Catalog\Pricing\Price\RegularPrice::PRICE_CODE)
                    ->getAmount()->getValue());
            }
            $_weeeHelper = $this->helper('Magento\Weee\Helper\Data');
            $_weeeTaxAmount          = $_weeeHelper->getAmountExclTax($product);
            $_weeeTaxAttributes      = $_weeeHelper
                ->getProductWeeeAttributesForRenderer($product, null, null, null, true);
            $_weeeTaxAmountInclTaxes = $_weeeTaxAmount;
            $_weeeTaxAmount          = $this->convertPrice($_weeeTaxAmount);
            $_weeeTaxAmountInclTaxes = $this->convertPrice($_weeeTaxAmountInclTaxes);

            $_finalPrice        = $_convertedFinalPrice;
            $_finalPriceInclTax = $this->priceCurrency->round($finalPrice->getAmount()->getValue());
            /*
            * compare final price (excluded tax) with price (excluded tax) to decide if it has special price
            *
            */
            if (
                //(!$is_detail && ($product->getTypeId() == 'configurable')) || 
                ($_finalPriceInclTax >= $_regularPrice)
            ) {
                $priveV2['has_special_price'] = 0;
                if ($_taxHelper->displayBothPrices()) {
                    $this->displayBothPrice(
                        $priveV2,
                        $_weeeTaxAmount,
                        $_weeeHelper,
                        $_price,
                        $_finalPriceInclTax,
                        $product,
                        $_weeeTaxAttributes,
                        $_weeeTaxAmountInclTaxes,
                        $_finalPrice
                    );
                } else {
                    $this->displaySinglePrice(
                        $priveV2,
                        $_weeeTaxAmount,
                        $_weeeHelper,
                        $product,
                        $_weeeTaxAttributes,
                        $_weeeTaxAmountInclTaxes,
                        $_price,
                        $_regularPrice,
                        $_finalPrice,
                        $_finalPriceInclTax,
                        $_taxHelper
                    );
                }
            } else {
                $this->displaySpecialPrice(
                    $priveV2,
                    $_weeeTaxAmount,
                    $_weeeHelper,
                    $_finalPriceInclTax,
                    $product,
                    $_weeeTaxAttributes,
                    $_weeeTaxAmountInclTaxes,
                    $_finalPrice,
                    $_regularPrice,
                    $_specialPriceStoreLabel,
                    $_taxHelper
                );
            }
            if ($_taxHelper->displayPriceExcludingTax()) 
                $minimalPrice = $this->getMinimalPrice($_finalPrice, $is_detail);
            else
                $minimalPrice = $this->getMinimalPrice($_finalPriceInclTax, $is_detail);
            if ($minimalPrice) {
                $_minimalPriceDisplayValue  = $minimalPrice + $_weeeTaxAmount;
                $priveV2['is_low_price']    = 1;
                $priveV2['low_price_label'] = __('As low as');
                $this->setTaxLowPrice($priveV2, $_minimalPriceDisplayValue);
            }
        }
        /*
        *   Group Product
        */
        else {
            $this->displayGroupPrice($priveV2, $_convertedFinalPrice, $product, $_taxHelper, $is_detail);
        }
        return $priveV2;
    }

    public function displayBothPrice(
        &$priveV2,
        $_weeeTaxAmount,
        $_weeeHelper,
        $_price,
        $_finalPriceInclTax,
        $product,
        $_weeeTaxAttributes,
        $_weeeTaxAmountInclTaxes,
        $_finalPrice
    ) {
        $priveV2['show_ex_in_price'] = 1;
        if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 0)) {
            $_exclTax = $_price + $_weeeTaxAmount;
            $_inclTax = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
            $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
        } elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 1)) {
            $wee = '';
            $this->getWeeeValue($wee, $priveV2, $_weeeTaxAttributes);
            $this->setWeePrice($priveV2, $wee);
            $_exclTax                   = $_price + $_weeeTaxAmount;
            $_inclTax                   = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
            $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
            $priveV2['show_weee_price'] = 1;
        } elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 4)) {
            $wee = '';
            $this->getWeeeValue($wee, $priveV2, $_weeeTaxAttributes);
            $this->setWeePrice($priveV2, $wee);
            $_exclTax                   = $_price + $_weeeTaxAmount;
            $_inclTax                   = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
            $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
            $priveV2['show_weee_price'] = 2;
        } elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 2)) {
            $wee = '';
            foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
                $wee .= $_weeeTaxAttribute->getName();
                $wee .= ": ";
                $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
                $wee .= " <br/> ";
                $priveV2["weee"] = $wee;
            }
            $this->setWeePrice($priveV2, $wee);
            $_exclTax                   = $_price;
            $_inclTax                   = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
            $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
            $priveV2['show_weee_price'] = 1;
        } else {
            $_exclTax = $_finalPrice;
            if ($_finalPrice == $_price) {
                $_exclTax = $_price;
            }
            $_inclTax = $_finalPriceInclTax;
            $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
        }
    }

    public function displaySinglePrice(
        &$priveV2,
        $_weeeTaxAmount,
        $_weeeHelper,
        $product,
        $_weeeTaxAttributes,
        $_weeeTaxAmountInclTaxes,
        $_price,
        $_regularPrice,
        $_finalPrice,
        $_finalPriceInclTax,
        $_taxHelper
    ) {
        $priveV2['show_ex_in_price'] = 0;
        $regularPrice = $_taxHelper->displayPriceIncludingTax() ?
            $_regularPrice : $_price;
        if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, [0, 1])) {
            $priveV2['price_label'] = __('Regular Price');
            $weeeAmountToDisplay    = $_taxHelper->displayPriceIncludingTax() ?
                $_weeeTaxAmountInclTaxes : $_weeeTaxAmount;
            $this->setTaxRegularPrice($priveV2, $regularPrice + $weeeAmountToDisplay);
            if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 1)) {
                $wee = '';
                $this->getWeeeValue($wee, $priveV2, $_weeeTaxAttributes);
                $priveV2['show_weee_price'] = 1;
            }
        } elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 4)) {
            $priveV2['price_label'] = __('Regular Price');
            $this->setTaxRegularPrice($priveV2, $regularPrice + $_weeeTaxAmount);
            if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 1)) {
                $wee = '';
                $this->getWeeeValue($wee, $priveV2, $_weeeTaxAttributes);
                $priveV2['show_weee_price'] = 1;
            }
        } elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 2)) {
            $priveV2['price_label'] = __('Regular Price');
            $weeeAmountToDisplay    = $_taxHelper->displayPriceIncludingTax() ?
                $_weeeTaxAmountInclTaxes : $_weeeTaxAmount;
            $this->setTaxRegularPrice($priveV2, $regularPrice + $weeeAmountToDisplay);
            if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 1)) {
                $wee = '';
                foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
                    $wee .= $_weeeTaxAttribute->getName();
                    $wee .= ": ";
                    $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
                    $wee .= " <br/> ";
                    $priveV2["weee"] = $wee;
                }
                $priveV2['show_weee_price'] = 2;
            }
        } else {
            $priveV2['price_label'] = __('Regular Price');
            if ($_taxHelper->displayPriceExcludingTax()) {
                $this->setTaxPrice($priveV2, $_finalPrice);
            } else {
                $this->setTaxPrice($priveV2, $_finalPriceInclTax);
            }
        }
    }

    public function getWeeeValue(&$wee, &$priveV2, &$_weeeTaxAttributes)
    {
        foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
            $wee .= $_weeeTaxAttribute->getName();
            $wee .= ": ";
            $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
            $wee .= " + ";
            $priveV2["weee"] = $wee;
        }
    }

    public function displaySpecialPrice(
        &$priveV2,
        $_weeeTaxAmount,
        $_weeeHelper,
        $_finalPriceInclTax,
        $product,
        $_weeeTaxAttributes,
        $_weeeTaxAmountInclTaxes,
        $_finalPrice,
        $_regularPrice,
        $_specialPriceStoreLabel,
        $_taxHelper
    ) {
        $priveV2['has_special_price'] = 1;
        $_originalWeeeTaxAmount       = $_weeeHelper->getAmountExclTax($product);
        $_originalWeeeTaxAmount       = $this->convertPrice($_originalWeeeTaxAmount);
        if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 0)) {
            $priveV2['price_label'] = __('Regular Price');
            $this->setTaxRegularPrice($priveV2, $_regularPrice + $_originalWeeeTaxAmount);
            if ($_taxHelper->displayBothPrices()) {
                $priveV2['show_ex_in_price']    = 1;
                $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                $_exclTax                       = $_finalPrice + $_weeeTaxAmount;
                $_inclTax                       = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
                $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
            } else {
                $priveV2['show_ex_in_price']    = 0;
                $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                $this->setTaxPrice($priveV2, $_finalPrice + $_weeeTaxAmountInclTaxes);
            }
        } elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 1)) {
            $priveV2['price_label'] = __('Regular Price');
            $this->setTaxRegularPrice($priveV2, $_regularPrice + $_originalWeeeTaxAmount);
            if ($_taxHelper->displayBothPrices()) {
                $priveV2['show_ex_in_price']    = 1;
                $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                $_exclTax                       = $_finalPrice + $_weeeTaxAmount;
                $_inclTax                       = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
                $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
                $wee                            = '';
                $this->getWeeeValue($wee, $priveV2, $_weeeTaxAttributes);
                $this->setWeePrice($priveV2, $wee);
                $priveV2['show_weee_price'] = 1;
            } else {
                $priveV2['show_ex_in_price']    = 0;
                $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                $this->setTaxPrice($priveV2, $_finalPrice + $_weeeTaxAmountInclTaxes);
                $wee                            = '';
                $this->getWeeeValue($wee, $priveV2, $_weeeTaxAttributes);
                $this->setWeePrice($priveV2, $wee);
                $priveV2['show_weee_price'] = 1;
            }
        } elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 4)) {
            $priveV2['show_ex_in_price']    = 1;
            $priveV2['price_label']         = __('Regular Price');
            $this->setTaxRegularPrice($priveV2, $_regularPrice + $_originalWeeeTaxAmount);
            $priveV2['special_price_label'] = $_specialPriceStoreLabel;
            $_exclTax                       = $_finalPrice + $_weeeTaxAmount;
            $_inclTax                       = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
            $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
            $wee                            = '';
            $this->getWeeeValue($wee, $priveV2, $_weeeTaxAttributes);
            $this->setWeePrice($priveV2, $wee);
            $priveV2['show_weee_price'] = 1;
        } elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 2)) {
            $priveV2['show_ex_in_price']    = 1;
            $priveV2['price_label']         = __('Regular Price');
            $this->setTaxRegularPrice($priveV2, $_regularPrice);
            $priveV2['special_price_label'] = $_specialPriceStoreLabel;
            $_exclTax                       = $_finalPrice;
            $_inclTax                       = $_finalPriceInclTax + $_weeeTaxAmountInclTaxes;
            $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
            $wee                            = '';
            foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
                $wee .= $_weeeTaxAttribute->getName();
                $wee .= ": ";
                $wee .= $this->currency($_weeeTaxAttribute->getAmount(), true, false);
                $wee .= " <br/> ";
                $priveV2["weee"] = $wee;
            }
            $this->setWeePrice($priveV2, $wee);
            $priveV2['show_weee_price'] = 1;
        } else {
            $priveV2['price_label'] = __('Regular Price');
            $this->setTaxRegularPrice($priveV2, $_regularPrice);
            $_exclTax                       = $_finalPrice;
            $_inclTax                       = $_finalPriceInclTax;
            if ($_taxHelper->displayBothPrices()) {
                $priveV2['show_ex_in_price']    = 1;
                $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
            } else {
                $priveV2['show_ex_in_price']    = 0;
                $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                if ($_taxHelper->displayPriceExcludingTax()) {
                    $this->setTaxPrice($priveV2, $_exclTax);
                } else {
                    $this->setTaxPrice($priveV2, $_inclTax);
                }
            }
        }
    }

    public function displayGroupPrice(
        &$priveV2,
        $_convertedFinalPrice,
        $product,
        $_taxHelper,
        $is_detail
    ) {
        $minimalPriceCalculator = $this->simiObjectManager->get('Magento\Catalog\Pricing\Price\MinimalPriceCalculatorInterface');
        $_minimalPrice = 0;
        if($minimalAmount = $minimalPriceCalculator->getAmount($product)){
            $_minimalPrice = $minimalAmount->getValue();
        }
        if ($_minimalPrice) {
            $_exclTax = $this->catalogHelper->getTaxPrice($product, $_minimalPrice);
            $_inclTax = $this->catalogHelper->getTaxPrice($product, $_minimalPrice, true);
            $price    = $_minimalPrice;
        } else {
            $price    = $_convertedFinalPrice;
            $_exclTax = $this->catalogHelper->getTaxPrice($product, $price);
            $_inclTax = $this->catalogHelper->getTaxPrice($product, $price, true);
        }

        if ($price) {
            if ($_minimalPrice) {
                $priveV2['price_label'] = __('Starting at');
            }
            if ($_taxHelper->displayBothPrices()) {
                $priveV2['show_ex_in_price'] = 1;
                $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
            } else {
                $priveV2['show_ex_in_price'] = 0;
                $_showPrice                  = $_inclTax;
                if (!$_taxHelper->displayPriceIncludingTax()) {
                    $_showPrice = $_exclTax;
                }
                $this->setTaxPrice($priveV2, $_showPrice);
            }
        }
    }
    /*
     * Get minimal price for configurable/simple/virtual/downloadable (exclude group and bundle)
     * @param int/float $finalPrice (included tax)
     * @return bool
     */
    public function getMinimalPrice($finalPriceValue, $is_detail)
    {
        try {
            $minimalPriceCalculator = $this->simiObjectManager->get('Magento\Catalog\Pricing\Price\MinimalPriceCalculatorInterface');
            if ($this->product) {
                $minTierPrice = $minimalPriceCalculator->getValue($this->product);
                //if (!$is_detail && ($this->product->getTypeId() == 'configurable'))
                //    return $finalPriceValue;
                if (
                    $minTierPrice && 
                    $minTierPrice < $finalPriceValue
                )
                    return $minTierPrice;
            }
        } catch (\Exception $e) {

        }
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
    public function setTaxRegularPrice(&$price, $_price)
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
        $price['price_excluding_tax'] = [
            'label' => __('Excl. Tax'),
            'price' => $_exclTax,
        ];
        $price['price_including_tax'] = [
            'label' => __('Incl. Tax'),
            'price' => $_inclTax,
        ];
    }

    public function setWeePrice(&$price, $wee)
    {
        $price['wee'] = $wee;
    }

    public function getProductTierPricesLabel($product){
        $currencyCode   = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $currency       = $this->simiObjectManager->create('Magento\Directory\Model\CurrencyFactory')
        ->create()->load($currencyCode);
        $currencySymbol = $currency->getCurrencySymbol();
        
        $result =[];
        $tierPriceModel = $product->getPriceInfo()->getPrice(\Magento\Catalog\Pricing\Price\TierPrice::PRICE_CODE);
        $msrpShowOnGesture = $product->getPriceInfo()->getPrice('msrp_price')->isShowPriceOnGesture();
        $tierPrices = $tierPriceModel->getTierPriceList();
        if(count($tierPrices)){
            foreach ($tierPrices as $index => $price) {
                if ($msrpShowOnGesture && $price['price']->getValue() < $product->getMsrp()){
                    $result[] =__('Buy %1 for: ', $price['price_qty']);
                } else if($product->getTypeId() == "bundle") {
                    $result[] = __(
                                   'Buy %1 with %2% discount each',
                                   $price['price_qty'],
                                   intval($price['percentage_value'])
                                   );
                } else {
                    $result[] = __(
                                   'Buy %1 for %2 each and save %3%',
                                   $price['price_qty'],
                                   $currencySymbol . $price['price']->getValue(),
                                   $tierPriceModel->getSavePercent($price['price'])
                                   );
                }
            }
        }
        
        return $result;
    }
}

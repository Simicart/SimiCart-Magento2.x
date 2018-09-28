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

    public function getProductAttribute($attribute)
    {
        return $this->product->getResource()->getAttribute($attribute);
    }

    public function convertPrice($price, $format = false)
    {
        $data = $this->getData();
        if (isset($data['resourceid']) && ($data['resourceid'] == 'products')) {
            return $price;
        }
        return $format ? $this->priceCurrency->convertAndFormat($price) : $this->priceCurrency->convert($price);
    }

    public function formatPriceFromProduct($product, $is_detail = false)
    {
        $priveV2        = [];
        $product       = $this->simiObjectManager->create('Magento\Catalog\Model\Product')->load($product->getId());
        $this->product = $product;

        $_weeeHelper = $this->helper('Magento\Weee\Helper\Data');
        $_taxHelper  = $this->helper('Magento\Tax\Helper\Data');

        $minimalPriceCalculator = $this->simiObjectManager->get('Magento\Catalog\Pricing\Price\MinimalPriceCalculatorInterface');
        $_minimalPrice = 0;
        if($minimalAmount = $minimalPriceCalculator->getAmount($product)){
            $_minimalPrice = $minimalAmount->getValue();
        }
        // $_minimalPrice           = $this->convertPrice($product->getMinimalPrice());
        $_simplePricesTax        = ($_taxHelper->displayPriceIncludingTax() || $_taxHelper->displayBothPrices());
        $finalPrice = $this->product->getPriceInfo()->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE);
        $_convertedFinalPrice = $finalPrice->getAmount()->getValue();

        // $_convertedFinalPrice    = $this->convertPrice($product->getFinalPrice());
        if($product->getTypeId() == 'configurable'){
            $_convertedFinalPrice    = $product->getFinalPrice();
        }
        $_specialPriceStoreLabel = $this->getProductAttribute('special_price')->getStoreLabel();

        if ($product->getTypeId() == "bundle") {
            return $this->helper('\Simi\Simiconnector\Helper\Bundle\Price')
                ->formatPriceFromProduct($product, $is_detail);
        }

        if ($product->getTypeId() != 'grouped') {
            $_weeeTaxAmount          = $_weeeHelper->getAmountExclTax($product);
            $_weeeTaxAttributes      = $_weeeHelper
                ->getProductWeeeAttributesForRenderer($product, null, null, null, true);
            $_weeeTaxAmountInclTaxes = $_weeeTaxAmount;

            $_weeeTaxAmount          = $this->convertPrice($_weeeTaxAmount);
            $_weeeTaxAmountInclTaxes = $this->convertPrice($_weeeTaxAmountInclTaxes);

            //price box
            $_convertedPrice    = $this->convertPrice($product->getData('price'));
            if($product->getTypeId() == 'configurable'){
                $_convertedPrice    = $this->convertPrice($product->getPriceInfo()->getPrice(\Magento\ConfigurableProduct\Pricing\Price\ConfigurableRegularPrice::PRICE_CODE)->getValue());
            }
            $_price             = $this->catalogHelper->getTaxPrice($product, $_convertedPrice);
            $_regularPrice      = $this->catalogHelper->getTaxPrice($product, $_convertedPrice, $_simplePricesTax);
            $_finalPrice        = $this->catalogHelper->getTaxPrice($product, $_convertedFinalPrice);
            $_finalPriceInclTax = $this->catalogHelper->getTaxPrice($product, $_convertedFinalPrice, true);
            $_weeeDisplayType   = $_weeeHelper->getPriceDisplayType();
            if ($_finalPrice >= $_price) {
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
                        $_price,
                        $product,
                        $_weeeTaxAttributes,
                        $_weeeTaxAmountInclTaxes,
                        $_finalPrice,
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
            }//end /* if ($_finalPrice == $_price): */
            if ($this->getDisplayMinimalPrice($is_detail) && $_minimalPrice && $_minimalPrice < $_convertedFinalPrice) {
                $_minimalPriceDisplayValue = $_minimalPrice;
                // if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, [0, 1, 4])) {
                $_minimalPriceDisplayValue  = $_minimalPrice + $_weeeTaxAmount;
                $priveV2['is_low_price']    = 1;
                $priveV2['low_price_label'] = __('As low as');
                $this->setTaxLowPrice($priveV2, $_minimalPriceDisplayValue);
                // }
            }
        } else { // group product
            $this->displayGroupPrice($priveV2, $_minimalPrice, $_convertedFinalPrice, $product, $_taxHelper, $is_detail);
        }

        return $priveV2;
    }

    public function displayBothPrice(
        &$priveV2,
        &$_weeeTaxAmount,
        $_weeeHelper,
        &$_price,
        $_finalPriceInclTax,
        &$product,
        $_weeeTaxAttributes,
        $_weeeTaxAmountInclTaxes,
        &$_finalPrice
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
        &$_weeeTaxAmount,
        $_weeeHelper,
        &$_price,
        &$product,
        $_weeeTaxAttributes,
        $_weeeTaxAmountInclTaxes,
        &$_finalPrice,
        $_taxHelper
    ) {
        $priveV2['show_ex_in_price'] = 0;
        if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, [0, 1])) {
            $priveV2['price_label'] = __('Regular Price');
            $weeeAmountToDisplay    = $_taxHelper->displayPriceIncludingTax() ?
                $_weeeTaxAmountInclTaxes : $_weeeTaxAmount;
            $this->setTaxReguarlPrice($priveV2, $_price + $weeeAmountToDisplay);
            if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 1)) {
                $wee = '';
                $this->getWeeeValue($wee, $priveV2, $_weeeTaxAttributes);
                $priveV2['show_weee_price'] = 1;
            }
        } elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 4)) {
            $priveV2['price_label'] = __('Regular Price');
            $this->setTaxReguarlPrice($priveV2, $_price + $_weeeTaxAmount);
            if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 1)) {
                $wee = '';
                $this->getWeeeValue($wee, $priveV2, $_weeeTaxAttributes);
                $priveV2['show_weee_price'] = 1;
            }
        } elseif ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 2)) {
            $priveV2['price_label'] = __('Regular Price');
            $weeeAmountToDisplay    = $_taxHelper->displayPriceIncludingTax() ?
                $_weeeTaxAmountInclTaxes : $_weeeTaxAmount;
            $this->setTaxReguarlPrice($priveV2, $_price + $weeeAmountToDisplay);
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
            if ($_finalPrice == $_price) {
                $this->setTaxPrice($priveV2, $_finalPrice);
            } else {
                $this->setTaxPrice($priveV2, $_price);
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
        &$_weeeTaxAmount,
        $_weeeHelper,
        $_finalPriceInclTax,
        &$product,
        $_weeeTaxAttributes,
        $_weeeTaxAmountInclTaxes,
        &$_finalPrice,
        &$_regularPrice,
        $_specialPriceStoreLabel,
        $_taxHelper
    ) {
        $priveV2['has_special_price'] = 1;
        $_originalWeeeTaxAmount       = $_weeeHelper->getAmountExclTax($product);
        $_originalWeeeTaxAmount       = $this->convertPrice($_originalWeeeTaxAmount);
        if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 0)) {
            $priveV2['price_label'] = __('Regular Price');
            $this->setTaxReguarlPrice($priveV2, $_regularPrice + $_originalWeeeTaxAmount);
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
            $this->setTaxReguarlPrice($priveV2, $_regularPrice + $_originalWeeeTaxAmount);
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
            $this->setTaxReguarlPrice($priveV2, $_regularPrice + $_originalWeeeTaxAmount);
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
            $this->setTaxReguarlPrice($priveV2, $_regularPrice);
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
            $this->setTaxReguarlPrice($priveV2, $_regularPrice);
            if ($_taxHelper->displayBothPrices()) {
                $priveV2['show_ex_in_price']    = 1;
                $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                $_exclTax                       = $_finalPrice;
                $_inclTax                       = $_finalPriceInclTax;
                $this->setBothTaxPrice($priveV2, $_exclTax, $_inclTax);
            } else {
                $priveV2['show_ex_in_price']    = 0;
                $priveV2['special_price_label'] = $_specialPriceStoreLabel;
                $this->setTaxPrice($priveV2, $_finalPrice);
            }
        }
    }

    public function displayGroupPrice(
        &$priveV2,
        &$_minimalPrice,
        &$_convertedFinalPrice,
        &$product,
        $_taxHelper,
        $is_detail
    ) {
        $showMinPrice = $this->getDisplayMinimalPrice($is_detail);
        if ($showMinPrice && $_minimalPrice) {
            $_exclTax = $this->catalogHelper->getTaxPrice($product, $_minimalPrice);
            $_inclTax = $this->catalogHelper->getTaxPrice($product, $_minimalPrice, true);
            $price    = $showMinPrice ? $_minimalPrice : 0;
        } else {
            $price    = $_convertedFinalPrice;
            $_exclTax = $this->catalogHelper->getTaxPrice($product, $price);
            $_inclTax = $this->catalogHelper->getTaxPrice($product, $price, true);
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
                $_showPrice                  = $_inclTax;
                if (!$_taxHelper->displayPriceIncludingTax()) {
                    $_showPrice = $_exclTax;
                }
                $this->setTaxPrice($priveV2, $_showPrice);
            }
        }
    }

    public function getDisplayMinimalPrice($is_detail)
    {
        $minimalPriceCalculator = $this->simiObjectManager->get('Magento\Catalog\Pricing\Price\MinimalPriceCalculatorInterface');
        if ($this->product) {
            $minTierPrice = $minimalPriceCalculator->getValue($this->product);

            $finalPrice = $this->product->getPriceInfo()->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE);
            $finalPriceValue = $finalPrice->getAmount()->getValue();
            return !$is_detail
                && $minTierPrice !== null
                && $minTierPrice < $finalPriceValue;
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
        $result =[];
        $tierPriceModel = $product->getPriceInfo()->getPrice(\Magento\Catalog\Pricing\Price\TierPrice::PRICE_CODE);
        $msrpShowOnGesture = $product->getPriceInfo()->getPrice('msrp_price')->isShowPriceOnGesture();
        $tierPrices = $tierPriceModel->getTierPriceList();
        if(count($tierPrices)){
            foreach ($tierPrices as $index => $price) {
                if ($msrpShowOnGesture && $price['price']->getValue() < $product->getMsrp()){
                    $result[] =__('Buy %1 for: ', $price['price_qty']);
                }else{
                    $result[] = __(
                        'Buy %1 for %2 each and save %3%',
                        $price['price_qty'],
                        $this->priceHelper->currency($price['price']->getValue(),false),
                        $tierPriceModel->getSavePercent($price['price'])
                    );
                }
            }
        }

        return $result;
    }
}

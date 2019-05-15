<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper\Bundle;

use Magento\Framework\App\Filesystem\DirectoryList;

class Price extends \Simi\Simiconnector\Helper\Price
{

    public $product = null;
    public $minimalPriceTax = null;
    public $minimalPriceInclTax = null;

    public function getProductAttribute($attribute)
    {
        return $this->product->getResource()->getAttribute($attribute);
    }

    public function displayBothPrices()
    {
        return $this->helper('Magento\Tax\Helper\Data')->displayBothPrices();
    }

    public function formatPriceFromProduct($product, $is_detail = false)
    {
        $priceV2 = [];
        $this->product = $product;

        $_weeeHelper = $this->helper('Magento\Weee\Helper\Data');
        $_taxHelper = $this->helper('Magento\Tax\Helper\Data');

        $bundleObj = $product->getPriceInfo()->getPrice('final_price');
        $this->minimalPriceTax = $minimalPriceTax = $bundleObj->getMinimalPrice()->getValue();
        $_maximalPriceTax = $bundleObj->getMaximalPrice()->getValue();

        $this->minimalPriceInclTax = $minimalPriceInclTax = $this->catalogHelper
            ->getTaxPrice($product, $minimalPriceTax, true);
        $_maximalPriceInclTax = $this->catalogHelper->getTaxPrice($product, $_maximalPriceTax, true);

        $_weeeTaxAmount = 0;

        if ($product->getPriceType() == 1 && $_weeeHelper && is_callable($_weeeHelper, 'getAmountForDisplay')) {
            $_weeeTaxAmount = $_weeeHelper->getAmountForDisplay($product);
            $_weeeTaxAmountInclTaxes = $_weeeTaxAmount;
            if ($_weeeHelper->isTaxable()) {
                $_attributes = $_weeeHelper->getProductWeeeAttributesForRenderer($product, null, null, null, true);
            }
            if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, [0, 1, 4])) {
                $minimalPriceTax += $_weeeTaxAmount;
                $minimalPriceInclTax += $_weeeTaxAmountInclTaxes;
                $_maximalPriceTax += $_weeeTaxAmount;
                $_maximalPriceInclTax += $_weeeTaxAmountInclTaxes;
            }
            if ($_weeeTaxAmount && $_weeeHelper->typeOfDisplay($product, 2)) {
                $minimalPriceInclTax += $_weeeTaxAmountInclTaxes;
                $_maximalPriceInclTax += $_weeeTaxAmountInclTaxes;
            }

            if ($_weeeHelper->typeOfDisplay($product, [1, 2, 4])) {
                $_weeeTaxAttributes = $_weeeHelper
                    ->getProductWeeeAttributesForRenderer($product, null, null, null, true);
            }
        }
        if ($product->getPriceView()) {
            $this->getPriceAsLowAs(
                $priceV2,
                $minimalPriceTax,
                $minimalPriceInclTax,
                $_weeeTaxAmount,
                $product,
                $_weeeTaxAttributes,
                $_weeeHelper
            );
        } else {
            $priceV2['minimal_price'] = 0;
            if ($minimalPriceTax <> $_maximalPriceTax) {
                $this->getPriceWithFromToWithTax(
                    $priceV2,
                    $minimalPriceTax,
                    $minimalPriceInclTax,
                    $_weeeTaxAmount,
                    $product,
                    $_weeeTaxAttributes,
                    $_weeeHelper,
                    $_maximalPriceTax,
                    $_maximalPriceInclTax
                );
                //to price
            } else {
                //not show from and to with tax
                $this->getPriceWithoutFromToWithTax(
                    $priceV2,
                    $minimalPriceTax,
                    $minimalPriceInclTax,
                    $_weeeTaxAmount,
                    $product,
                    $_weeeTaxAttributes,
                    $_weeeHelper
                );
            }
        }
        if ($is_detail) {
            $this->minimalPriceInclTax = $minimalPriceInclTax;
            $this->minimalPriceTax = $minimalPriceTax;
            $priceV2['configure'] = $this->formatPriceFromProductDetail($product);
        }
        return $priceV2;
    }

//    public function getDisplayMinimalPrice()
//    {
//        if ($this->product) {
//            return $this->product->getMinimalPrice();
//        }
//        return 0;
//    }

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
        $price['price'] = $this->currency($_price, false, false);
    }

    public function setTaxPriceIn(&$price, $_price)
    {
        $price['price_in'] = $this->currency($_price, false, false);
    }

    public function setTaxFromPrice(&$price, $_price)
    {
        $price['from_price'] = $this->currency($_price, false, false);
    }

    public function setTaxToPrice(&$price, $_price)
    {
        $price['to_price'] = $this->currency($_price, false, false);
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
            'price' => $this->currency($_exclTax, false, false),
        ];
        $price['price_including_tax'] = [
            'label' => __('Incl. Tax'),
            'price' => $this->currency($_inclTax, false, false),
        ];
    }

    public function setBothTaxFromPrice(&$price, $_exclTax, $_inclTax)
    {
        $price['from_price_excluding_tax'] = [
            'label' => __('Excl. Tax'),
            'price' => $this->currency($_exclTax, false, false),
        ];
        $price['from_price_including_tax'] = [
            'label' => __('Incl. Tax'),
            'price' => $this->currency($_inclTax, false, false),
        ];
    }

    public function setBothTaxToPrice(&$price, $_exclTax, $_inclTax)
    {
        $price['to_price_excluding_tax'] = [
            'label' => __('Excl. Tax'),
            'price' => $this->currency($_exclTax, false, false),
        ];
        $price['to_price_including_tax'] = [
            'label' => __('Incl. Tax'),
            'price' => $this->currency($_inclTax, false, false),
        ];
    }

    public function setWeePrice(&$price, $wee)
    {
        $price['wee'] = $wee;
    }

    public function formatPriceFromProductDetail($product)
    {
        $priceV2 = [];

        $_weeeHelper = $this->helper('Magento\Weee\Helper\Data');
        $_taxHelper = $this->helper('Magento\Tax\Helper\Data');

        $msrp_price_base = $product->getPriceInfo()->getPrice('msrp_price')->getAmount()->getBaseAmount();
        $_finalPrice = $product->getFinalPrice() > $this->minimalPriceTax ?
            $product->getFinalPrice() : $this->minimalPriceTax;
        $_finalPriceInclTax = $product->getFinalPrice() > $this->minimalPriceInclTax ?
            $product->getFinalPrice() : $this->minimalPriceInclTax;
        $_weeeTaxAmount = 0;

        if ($product->getPriceType() == 1) {
            $_weeeTaxAmount = $_weeeHelper->getAmount($product);
            if ($_weeeHelper->typeOfDisplay($product, [1, 2, 4])) {
                $_weeeTaxAttributes = $_weeeHelper
                    ->getProductWeeeAttributesForRenderer($product, null, null, null, true);
            }
        }
        $isMAPTypeOnGesture = true;
        $canApplyMAP = $this->helper('Magento\Msrp\Helper\Data')->canApplyMsrp($product);
        if ($product->getCanShowPrice() !== false) {
            $priceV2['product_label'] = __('Price as configured');
            if ($isMAPTypeOnGesture) {
                if ($_taxHelper->displayBothPrices()) {
                    $priceV2['show_ex_in_price'] = 1;
                    if (!$canApplyMAP) {
                        $this->setBothTaxPrice($priceV2, $_finalPrice, $_finalPriceInclTax);
                    }
                    if ($_weeeTaxAmount && $product->getPriceType() == 1
                        && $_weeeHelper->typeOfDisplay($product, [2, 1, 4])
                    ) {
                        $wee = '';
                        $this->getBundleWeeeValue($wee, $priceV2, $_weeeTaxAttributes, $_weeeHelper, $product);
                        $this->setWeePrice($priceV2, $wee);
                        $priceV2['show_weee_price'] = 1;
                    }
                } else {
                    if (!$canApplyMAP) {
                        $this->setTaxPrice($priceV2, $_finalPrice);
                    }

                    if ($_weeeTaxAmount && $product->getPriceType() == 1
                        && $_weeeHelper->typeOfDisplay($product, [2, 1, 4])
                    ) {
                        $wee = '';
                        $this->setWeePrice($priceV2, $wee);
                        $priceV2['show_weee_price'] = 1;
                    }
                }
            }
        }
        return $priceV2;
    }

    public function getPriceAsLowAs(
        &$priceV2,
        &$minimalPriceTax,
        &$minimalPriceInclTax,
        &$_weeeTaxAmount,
        &$product,
        &$_weeeTaxAttributes,
        &$_weeeHelper
    ) {
    
        $_taxHelper = $this->helper('Magento\Tax\Helper\Data');
        $priceV2['price_label'] = __('As low as');
        $priceV2['minimal_price'] = 1;
        if ($this->displayBothPrices()) {
            $priceV2['show_ex_in_price'] = 1;
            $this->setBothTaxPrice($priceV2, $minimalPriceTax, $minimalPriceInclTax);
            if ($_weeeTaxAmount && $product->getPriceType() == 1
                && $_weeeHelper->typeOfDisplay($product, [2, 1, 4])
            ) {
                $wee = '';

                $this->getBundleWeeeValue($wee, $priceV2, $_weeeTaxAttributes, $_weeeHelper, $product);
                $this->setWeePrice($priceV2, $wee);
                $priceV2['show_weee_price'] = 1;
            }
        } else {
            $priceV2['show_ex_in_price'] = 0;
            if ($_taxHelper->displayPriceIncludingTax()) {
                $this->setTaxPrice($priceV2, $minimalPriceInclTax);
            } else {
                $this->setTaxPrice($priceV2, $minimalPriceTax);
            }
            if ($_weeeTaxAmount && $product->getPriceType() == 1
                && $_weeeHelper->typeOfDisplay($product, [2, 1, 4])
            ) {
                $wee = '';
                $this->getBundleWeeeValue($wee, $priceV2, $_weeeTaxAttributes, $_weeeHelper, $product);
                $this->setWeePrice($priceV2, $wee);
                $priceV2['show_weee_price'] = 1;
            }
            if ($_weeeHelper->typeOfDisplay($product, 2) && $_weeeTaxAmount) {
                $this->setTaxPriceIn($priceV2, $minimalPriceInclTax);
            }
        }
    }

    public function getPriceWithFromToWithTax(
        &$priceV2,
        &$minimalPriceTax,
        &$minimalPriceInclTax,
        &$_weeeTaxAmount,
        &$product,
        &$_weeeTaxAttributes,
        &$_weeeHelper,
        $_maximalPriceTax,
        $_maximalPriceInclTax
    ) {
    
        $_taxHelper = $this->helper('Magento\Tax\Helper\Data');
        $priceV2['product_from_label'] = __('From');
        $priceV2['product_to_label'] = __('To');
        $priceV2['show_from_to_tax_price'] = 1;
        if ($this->displayBothPrices()) {
            $priceV2['show_ex_in_price'] = 1;
            $this->setBothTaxFromPrice($priceV2, $minimalPriceTax, $minimalPriceInclTax);
            $this->setBothTaxToPrice($priceV2, $_maximalPriceTax, $_maximalPriceInclTax);
            if ($_weeeTaxAmount && $product->getPriceType() == 1
                && $_weeeHelper->typeOfDisplay($product, [2, 1, 4])
            ) {
                $wee = '';
                $this->getFromToWeeeValue($wee, $priceV2, $_weeeTaxAttributes, $_weeeHelper, $product);
                $this->setWeePrice($priceV2, $wee);
                $priceV2['show_weee_price'] = 1;
            }
        } else {
            $priceV2['show_ex_in_price'] = 0;
            if ($_taxHelper->displayPriceIncludingTax()) {
                $this->setTaxFromPrice($priceV2, $minimalPriceInclTax);
                $this->setTaxToPrice($priceV2, $_maximalPriceInclTax);
            } else {
                $this->setTaxFromPrice($priceV2, $minimalPriceTax);
                $this->setTaxToPrice($priceV2, $_maximalPriceTax);
            }

            if ($_weeeTaxAmount && $product->getPriceType() == 1
                && $_weeeHelper->typeOfDisplay($product, [2, 1, 4])
            ) {
                $wee = '';
                $this->getBundleWeeeValue($wee, $priceV2, $_weeeTaxAttributes, $_weeeHelper, $product);
                $this->setWeePrice($priceV2, $wee);
                $priceV2['show_weee_price'] = 1;
            }
            if ($_weeeHelper->typeOfDisplay($product, 2) && $_weeeTaxAmount) {
                $this->setTaxFromPrice($priceV2, $minimalPriceInclTax);
                $this->setTaxToPrice($priceV2, $_maximalPriceInclTax);
            }
        }
    }

    public function getPriceWithoutFromToWithTax(
        &$priceV2,
        &$minimalPriceTax,
        &$minimalPriceInclTax,
        &$_weeeTaxAmount,
        &$product,
        &$_weeeTaxAttributes,
        &$_weeeHelper
    ) {
    
        $_taxHelper = $this->helper('Magento\Tax\Helper\Data');
        //not show from and to with tax
        $priceV2['show_from_to_tax_price'] = 0;
        if ($this->displayBothPrices()) {
            $priceV2['show_ex_in_price'] = 1;
            $priceV2['product_from_label'] = __('From');
            $priceV2['product_to_label'] = __('To');

            $this->setTaxFromPrice($priceV2, $minimalPriceTax);
            $this->setTaxToPrice($priceV2, $minimalPriceInclTax);

            if ($_weeeTaxAmount && $product->getPriceType() == 1
                && $_weeeHelper->typeOfDisplay($product, [2, 1, 4])
            ) {
                $wee = '';
                $this->getBundleWeeeValue($wee, $priceV2, $_weeeTaxAttributes, $_weeeHelper, $product);
                $this->setWeePrice($priceV2, $wee);
                $priceV2['show_weee_price'] = 1;
            }
        } else {
            $this->setTaxPrice($priceV2, $minimalPriceTax);
            if ($_weeeTaxAmount && $product->getPriceType() == 1
                && $_weeeHelper->typeOfDisplay($product, [2, 1, 4])
            ) {
                $wee = '';
                $this->getBundleWeeeValue($wee, $priceV2, $_weeeTaxAttributes, $_weeeHelper, $product);
                $this->setWeePrice($priceV2, $wee);
                $priceV2['show_weee_price'] = 1;
            }
            if ($_weeeHelper->typeOfDisplay($product, 2) && $_weeeTaxAmount) {
                if ($_taxHelper->displayPriceIncludingTax()) {
                    $this->setTaxPrice($priceV2, $minimalPriceInclTax);
                } else {
                    $this->setTaxPrice($priceV2, $minimalPriceTax + $_weeeTaxAmount);
                }
            }
        }
    }

    public function getBundleWeeeValue(&$wee, &$priceV2, &$_weeeTaxAttributes, $_weeeHelper, $product)
    {
        foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
            if ($_weeeHelper->typeOfDisplay($product, [2, 4])) {
                $amount = $_weeeTaxAttribute->getAmount() + $_weeeTaxAttribute->getTaxAmount();
            } else {
                $amount = $_weeeTaxAttribute->getAmount();
            }
            $wee .= $_weeeTaxAttribute->getName();
            ;
            $wee .= ": ";
            $wee .= $this->currency($amount, true, false);
            $wee .= " + ";
            $priceV2["weee"] = $wee;
        }
    }

    public function getFromToWeeeValue(&$wee, &$priceV2, &$_weeeTaxAttributes, $_weeeHelper, $product)
    {
        foreach ($_weeeTaxAttributes as $_weeeTaxAttribute) {
            if ($_weeeHelper->typeOfDisplay($product, [2, 4])) {
                $amount = $_weeeTaxAttribute->getAmount() + $_weeeTaxAttribute->getTaxAmount();
            } else {
                $amount = $_weeeTaxAttribute->getAmount();
            }
            $wee .= $_weeeTaxAttribute->getName();
            $wee .= ": ";
            $wee .= $this->currency($amount, true, false);
            $wee .= " + ";
            $priceV2["weee_from"] = $wee;
            $priceV2["weee_to"] = $wee;
        }
    }
}

<?php

/**
 * Connector data helper
 */
namespace Simi\Simiconnector\Helper\Options;

class Grouped extends \Simi\Simiconnector\Helper\Options
{
    public function helper($helper)
    {
        return $this->_objectManager->get($helper);
    }
    
    public function getPrice($product, $price, $includingTax = null)
    {
        if (!is_null($includingTax)) {
            $price = $this->_catalogHelper->getTaxPrice($product, $price, true);
        } else {
            $price = $this->_catalogHelper->getTaxPrice($product, $price);
        }
        return $price;
    }
    
    function getOptions($product){
        $info = array();
        $taxHelper = $this->helper('\Magento\Tax\Helper\Data');
        //Mage_Catalog_Block_Product_View_Type_Grouped
        $_associatedProducts = $product->getTypeInstance(true)
            ->getAssociatedProducts($product);
        $_hasAssociatedProducts = count($_associatedProducts) > 0;
        if ($_hasAssociatedProducts){
            foreach ($_associatedProducts as $_item){
                $op = array(
                    'id' => $_item->getId(),
                    'name' => $_item->getName(),
                    'is_salable' => $_item->isSaleable() ? "1":"0",
                    'qty' => is_null($_item->getData('qty')) ? "0" : $_item->getData('qty'),
                    'position' => is_null($_item->getData('position'))? "0": $_item->getData('position'),
                );

                $final_price = $_item->getFinalPrice();
                $price = $_item->getPrice();
                if($final_price < $price){
                    $op['price_label'] = __('Regular Price');
                    $op['regular_price'] = $price;
                    $op['has_special_price'] = 1;
                    $op['special_price_label'] = __('Special Price');
                    $_priceInclTax = $this->currency($this->getPrice($product, $final_price, true), false, false);
                    $_priceExclTax = $this->currency($this->getPrice($product, $final_price), false, false);
                }else{
                    $op['has_special_price'] = 0;
                    $_priceInclTax = $this->currency($this->getPrice($product, $price, true), false, false);
                    $_priceExclTax = $this->currency($this->getPrice($product, $price), false, false);

                }

                $op['show_ex_in_price'] = 0;
                if ($taxHelper->displayPriceIncludingTax()) {
                    $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($op, $_priceInclTax);
                } elseif ($taxHelper->displayPriceExcludingTax()) {
                    $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($op, $_priceExclTax);
                } elseif ($taxHelper->displayBothPrices()) {
                    $op['show_ex_in_price'] = 1;
                    $this->helper('Simi\Simiconnector\Helper\Price')->setBothTaxPrice($op, $_priceExclTax, $_priceInclTax);
                } else {
                    $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($op, $_priceInclTax);
                }
                $info[] = $op;
            }
        }
        $options = array();
        $options['grouped_options'] = $info;
        return $options;
    }
}
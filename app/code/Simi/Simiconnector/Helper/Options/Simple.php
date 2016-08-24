<?php

/**
 * Connector data helper
 */
namespace Simi\Simiconnector\Helper\Options;


class Simple extends \Simi\Simiconnector\Helper\Options
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
    
    
    public function getOptions($product)
    {
        $info = array();
        $taxHelper = $this->helper('Magento\Tax\Helper\Data');
        $layout = $this->_objectManager->get('Magento\Framework\View\LayoutInterface');
        $block_product = $layout->createBlock('Magento\Swatches\Block\Product\Renderer\Configurable\Interceptor');
        $options = $block_product->decorateArray($product->getOptions());
        
        
        foreach ($options as $option) {
            $item = array();
            $item['id'] = $option->getId();
            $item['title'] = $option->getTitle();
            $item['type'] = $option->getType();
            $item['position'] = $option->getSortOrder();
            $item['isRequired'] = $option->getIsRequire();
            if($option->getType() == "file"){
                $item['file_extension'] = $option->getFileExtension();
            }
            if ($option->getGroupByType() == \Magento\Catalog\Model\Product\Option::OPTION_GROUP_SELECT) {
                foreach ($option->getValues() as $value) {
                    /* @var $value Mage_Catalog_Model_Product_Option_Value */
                    $item_value = array(
                        'id' => $value->getId(),
                        'title' => $value->getTitle(),
                    );
                    $price = $value->getPrice(true);
                            
                    $_priceInclTax = $this->currency($this->getPrice($product, $price, true), false, false);
                    $_priceExclTax = $this->currency($this->getPrice($product, $price), false, false);

                    if ($taxHelper->displayPriceIncludingTax()) {
                        $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($item_value, $_priceInclTax);
                    } elseif ($taxHelper->displayPriceExcludingTax()) {
                        $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($item_value, $_priceExclTax);
                    } elseif ($taxHelper->displayBothPrices()) {
                        $this->helper('Simi\Simiconnector\Helper\Price')->setBothTaxPrice($item_value, $_priceExclTax, $_priceInclTax);
                    } else {
                        $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($item_value, $_priceInclTax);
                    }

                    $item['values'][] = $item_value;
                }
            } else {
                $item_value = array();
                $price = $option->getPrice(true);
                $_priceInclTax = $this->currency($this->getPrice($product, $price, true), false, false);
                $_priceExclTax = $this->currency($this->getPrice($product, $price), false, false);

                if ($taxHelper->displayPriceIncludingTax()) {
                    $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($item_value, $_priceInclTax);
                } elseif ($taxHelper->displayPriceExcludingTax()) {
                    $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($item_value, $_priceExclTax);
                } elseif ($taxHelper->displayBothPrices()) {
                    $this->helper('Simi\Simiconnector\Helper\Price')->setBothTaxPrice($item_value, $_priceExclTax, $_priceInclTax);
                } else {
                    $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($item_value, $_priceInclTax);
                }
                $item['values'][] = $item_value;
            }

            $info[] = $item;
        }
        $options = array();
        $options['custom_options'] = $info;
        return $options;
    }
    
    
}


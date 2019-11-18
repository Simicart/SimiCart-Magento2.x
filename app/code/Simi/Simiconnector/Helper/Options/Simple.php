<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper\Options;

class Simple extends \Simi\Simiconnector\Helper\Options
{

    public function helper($helper)
    {
        return $this->simiObjectManager->get($helper);
    }

    public function getPrice($product, $price, $includingTax = null)
    {
        if (!($includingTax === null)) {
            $price = $this->catalogHelper->getTaxPrice($product, $price, true);
        } else {
            $price = $this->catalogHelper->getTaxPrice($product, $price);
        }
        return $price;
    }

    public function getOptions($product)
    {
        $info          = [];
        $taxHelper     = $this->helper('Magento\Tax\Helper\Data');
        $layout        = $this->simiObjectManager->get('Magento\Framework\View\LayoutInterface');
        $block_product = $layout->createBlock('Magento\Swatches\Block\Product\Renderer\Configurable\Interceptor');
        $options       = $block_product->decorateArray($product->getOptions());

        if ($options && is_array($options)) {
            foreach ($options as $option) {
                $item               = [];
                $item['id']         = $option->getId();
                $item['title']      = $option->getTitle();
                $item['type']       = $option->getType();
                $item['position']   = $option->getSortOrder();
                $item['isRequired'] = $option->getIsRequire();
                $this->getExtraInfo($option, $item);
                if ($option->getGroupByType() == \Magento\Catalog\Model\Product\Option::OPTION_GROUP_SELECT) {
                    foreach ($option->getValues() as $value) {
                        $item_value = [
                            'id'    => $value->getId(),
                            'title' => $value->getTitle(),
                        ];
                        $price      = $value->getPrice(true);

                        $_priceInclTax = $this->currency($this->getPrice($product, $price, true), false, false);
                        $_priceExclTax = $this->currency($this->getPrice($product, $price), false, false);

                        if ($taxHelper->displayPriceIncludingTax()) {
                            $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($item_value, $_priceInclTax);
                        } elseif ($taxHelper->displayPriceExcludingTax()) {
                            $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($item_value, $_priceExclTax);
                        } elseif ($taxHelper->displayBothPrices()) {
                            $this->helper('Simi\Simiconnector\Helper\Price')
                                    ->setBothTaxPrice($item_value, $_priceExclTax, $_priceInclTax);
                        } else {
                            $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($item_value, $_priceInclTax);
                        }

                        $item['values'][] = $item_value;
                    }
                } else {
                    $item_value    = [];
                    $price         = $option->getPrice(true);
                    $_priceInclTax = $this->currency($this->getPrice($product, $price, true), false, false);
                    $_priceExclTax = $this->currency($this->getPrice($product, $price), false, false);

                    if ($taxHelper->displayPriceIncludingTax()) {
                        $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($item_value, $_priceInclTax);
                    } elseif ($taxHelper->displayPriceExcludingTax()) {
                        $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($item_value, $_priceExclTax);
                    } elseif ($taxHelper->displayBothPrices()) {
                        $this->helper('Simi\Simiconnector\Helper\Price')
                                ->setBothTaxPrice($item_value, $_priceExclTax, $_priceInclTax);
                    } else {
                        $this->helper('Simi\Simiconnector\Helper\Price')->setTaxPrice($item_value, $_priceInclTax);
                    }
                    $item['values'][] = $item_value;
                }

                $info[] = $item;
            }
        }
        $options                   = [];
        $options['custom_options'] = $info;
        return $options;
    }
    
    private function getExtraInfo($option, &$item)
    {
        if ($option->getType() == "file") {
            $item['image_size_x'] = $option->getData('image_size_x');
            $item['image_size_y'] = $option->getData('image_size_y');
            $item['sku'] = $option->getData('sku');
            $item['file_extension'] = $option->getFileExtension();
            $item['input_name'] = 'options_'.$option->getData('option_id').'_file';
        } else if($option->getData('max_characters')) {
            $item['max_characters'] = $option->getData('max_characters');
        }
    }
}

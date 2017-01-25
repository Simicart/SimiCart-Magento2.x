<?php

/**
 * Connector data helper
 */
namespace Simi\Simiconnector\Helper\Options;

class Configurable extends \Simi\Simiconnector\Helper\Options
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
        $layout = $this->_objectManager->get('Magento\Framework\View\LayoutInterface');
        $block = $layout->createBlock('Magento\ConfigurableProduct\Block\Product\View\Type\Configurable');
        
        $block->setProduct($product);
        $options = [];
        $configurable_options = json_decode($block->getJsonConfig());
        $options['configurable_options'] = $configurable_options;
        
        if (!is_null($product->getOptions()) && count($product->getOptions())) {
            $custom_options = $this->helper('Simi\Simiconnector\Helper\Options\Simple')->getOptions($product);
            $options['custom_options'] = $custom_options['custom_options'];
        }
        return $options;
    }
}

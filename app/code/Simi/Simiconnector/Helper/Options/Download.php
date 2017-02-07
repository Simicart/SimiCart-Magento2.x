<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper\Options;

class Download extends \Simi\Simiconnector\Helper\Options
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
        $info      = [];
        $taxHelper = $this->helper('Magento\Tax\Helper\Data');

        $layout = $this->simiObjectManager->get('Magento\Framework\View\LayoutInterface');

        $block                     = $layout->createBlock('Magento\Downloadable\Block\Catalog\Product\Links');
        $block->setProduct($product);
        $_links                    = $block->getLinks();
        $_linksPurchasedSeparately = $block->getLinksPurchasedSeparately();
        $_isRequired               = $block->getLinkSelectionRequired();
        if ($product->isSaleable() && $block->hasLinks()) {
            $item = [
                'title'                      => $block->getLinksTitle(),
                'type'                       => 'checkbox',
                'position'                   => '0',
                'links_purchased_separately' => $_linksPurchasedSeparately,
                'isRequired'                 => $_isRequired,
            ];

            foreach ($_links as $_link) {
                $value = [
                    'id'    => $_link->getId(),
                    'title' => $block->escapeHtml($_link->getTitle()),
                ];

                $price         = $_link->getPrice();
                $_priceInclTax = $this->currency($this->getPrice($product, $price, true), false, false);
                $_priceExclTax = $this->currency($this->getPrice($product, $price), false, false);

                if ($taxHelper->displayPriceIncludingTax()) {
                    $this->simiObjectManager->get('Simi\Simiconnector\Helper\Price')
                            ->setTaxPrice($value, $_priceInclTax);
                } elseif ($taxHelper->displayPriceExcludingTax()) {
                    $this->simiObjectManager->get('Simi\Simiconnector\Helper\Price')
                            ->setTaxPrice($value, $_priceExclTax);
                } elseif ($taxHelper->displayBothPrices()) {
                    $this->simiObjectManager->get('Simi\Simiconnector\Helper\Price')
                            ->setBothTaxPrice($value, $_priceExclTax, $_priceInclTax);
                } else {
                    $this->simiObjectManager->get('Simi\Simiconnector\Helper\Price')
                            ->setTaxPrice($value, $_priceInclTax);
                }
                $item['value'][] = $value;
            }
            $info[] = $item;
        }
        $options                     = [];
        $options['download_sample']  = $this->getSampleData($product);
        $options['download_options'] = $info;
        if (!($product->getOptions() == null) && $this->simiObjectManager
                ->get('Simi\Simiconnector\Helper\Data')->countArray($product->getOptions())) {
            $custom_options            = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Options\Simple')
                    ->getOptions($product);
            $options['custom_options'] = $custom_options['custom_options'];
        }
        return $options;
    }

    public function getSampleData($product)
    {
        $info = [];

        $layout = $this->simiObjectManager->get('Magento\Framework\View\LayoutInterface');
        $block  = $layout->createBlock('Magento\Downloadable\Block\Catalog\Product\Samples');
        $block->setProduct($product);
        if ($block->hasSamples()) {
            $_samples = $block->getSamples();
            $item     = [
                'title' => $block->getSamplesTitle(),
            ];
            foreach ($_samples as $_sample) {
                $value           = [
                    'url'   => $block->getSampleUrl($_sample),
                    'title' => $block->escapeHtml($_sample->getTitle()),
                ];
                $item['value'][] = $value;
                $info[]          = $item;
            }
        }
        return $info;
    }
}

<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Checkout extends \Simi\Simiconnector\Helper\Data
{
    /*
     * Get Checkout Terms And Conditions
     */

    public function getCheckoutTermsAndConditions()
    {
        if (!$this->getStoreConfig('simiconnector/terms_conditions/enable_terms')) {
            return null;
        }
        $data            = [];
        $data['title']   = $this->getStoreConfig('simiconnector/terms_conditions/term_title');
        $data['content'] = $this->getStoreConfig('simiconnector/terms_conditions/term_html');
        return $data;
    }

    public function getStoreConfig($path)
    {
        return $this->scopeConfig->getValue($path);
    }

    public function convertOptionsCart($options)
    {
        $data = [];
        foreach ($options as $option) {
            $item = [
                'option_title' => $option['label']
            ];
            if (is_array($option['value'])) {
                $item['option_value'] = strip_tags($option['value'][0]);
            } else {
                $item['option_value'] = strip_tags($option['value']);
            }
            $data[] = $item;
        }
        return $data;
    }
}

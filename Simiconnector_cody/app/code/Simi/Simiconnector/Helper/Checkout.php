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
    public function getCheckoutTermsAndConditions() {
        if (!$this->getStoreConfig('simiconnector/terms_conditions/enable_terms'))
            return NULL;
        $data = array();
        $data['title'] = $this->getStoreConfig('simiconnector/terms_conditions/term_title');
        $data['content'] = $this->getStoreConfig('simiconnector/terms_conditions/term_html');
        return $data;
    }

    
    public function getStoreConfig($path) {
        return $this->_scopeConfig->getValue($path);
    }
}


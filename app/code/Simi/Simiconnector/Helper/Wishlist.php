<?php

namespace Simi\Simiconnector\Helper;

class Wishlist extends Data
{

    /*
     * Get Wishlist Item Id
     * 
     * @param Product Model
     */
    public function getWishlistItemId($product) {
        $customer = $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomer();
        if ($customer->getId() && ($customer->getId() != '')) {
            $wishlist = $this->_objectManager->get('Magento\Wishlist\Model\Wishlist')->loadByCustomerId($customer->getId(), true);
            foreach ($wishlist->getItemCollection() as $item) {
                if ($item->getProduct()->getId() != $product->getId())
                    return $item->getId();
            }
        }
    }

    /*
     * @param:
     * $item - Wishlist Item
     */

    public function checkIfSelectedAllRequiredOptions($item, $options = null) {
        $selected = false;
        $product = $item->getProduct();
        if ($product->getTypeId() == 'simple') {
            $selected = true;
        }
        return $selected;
    }

    public function getOptionsSelectedFromItem($item, $product) {
        $options = array();
        $helper = $this->_objectManager->get('Magento\Catalog\Helper\Product\Configuration');
        if ($product->getTypeId() == "simple") {
            $options = $this->_objectManager->get('\Simi\Simiconnector\Helper\Checkout')->convertOptionsCart($helper->getCustomOptions($item));
        } elseif ($product->getTypeId() == "configurable") {
            $options = $this->_objectManager->get('\Simi\Simiconnector\Helper\Checkout')->convertOptionsCart($helper->getOptions($item));
        } 
        return $options;
    }
}

<?php

namespace Simi\Simiconnector\Helper;

class Wishlist extends Data
{
    /*
     * Get Wishlist Item Id
     *
     * @param Product Model
     */

    public function getWishlistItemId($product)
    {
        $customer = $this->simiObjectManager->create('Magento\Customer\Model\Session')->getCustomer();
        if ($customer->getId() && ($customer->getId() != '')) {
            $wishlist = $this->simiObjectManager->get('Magento\Wishlist\Model\Wishlist')
                    ->loadByCustomerId($customer->getId(), true);
            foreach ($wishlist->getItemCollection() as $item) {
                $wishlistItemId = $item->getId();
                $wishlistItemProductId = $item->getProduct()->getId();
                if ($wishlistItemProductId == $product->getId()) {
                    return $wishlistItemId;
                } else {
                    $parentProducts = $this->simiObjectManager->create('Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable')
                        ->getParentIdsByChild($product->getId());
                    if($parentProducts && isset($parentProducts[0])){
                        $parentProduct = $parentProducts[0];
                        if($parentProduct->getId() && $parentProduct->getId() == $wishlistItemProductId){ 
                            return $wishlistItemId;
                        }
                    }
                }
            }
        }
    }

    /*
     * @param:
     * $item - Wishlist Item
     */

    public function checkIfSelectedAllRequiredOptions($item)
    {
        $selected = false;
        $product  = $item->getProduct();
        if ($product->getTypeId() == 'simple') {
            $selected = true;
        }
        return $selected;
    }

    public function getOptionsSelectedFromItem($item, $product)
    {
        $options = [];
        $helper  = $this->simiObjectManager->get('Magento\Catalog\Helper\Product\Configuration');
        if ($product->getTypeId() == "simple") {
            $options = $this->simiObjectManager->get('\Simi\Simiconnector\Helper\Checkout')
                    ->convertOptionsCart($helper->getCustomOptions($item));
        } elseif ($product->getTypeId() == "configurable") {
            $options = $this->simiObjectManager->get('\Simi\Simiconnector\Helper\Checkout')
                    ->convertOptionsCart($helper->getOptions($item));
        }
        return $options;
    }
}

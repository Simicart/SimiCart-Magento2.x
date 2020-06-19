<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Wishlistitems extends Apiabstract
{

    public $DEFAULT_ORDER = 'wishlist_item_id';
    public $RETURN_MESSAGE;
    public $RETURN_URL;
    public $WISHLIST;

    public function setBuilderQuery()
    {
        $data     = $this->getData();
        $customer = $this->simiObjectManager->get('Magento\Customer\Model\Session')->getCustomer();
        $code = false;
        if (isset($data['params']) && isset($data['params']['code']))
            $code = $data['params']['code'];
        if ($code !== false ||
            ($customer->getId() && ($customer->getId() != ''))) {
            if ($code !== false)
                $this->WISHLIST = $this->simiObjectManager
                    ->get('Magento\Wishlist\Model\Wishlist')->loadByCode($code);
            else
                $this->WISHLIST = $this->simiObjectManager
                    ->get('Magento\Wishlist\Model\Wishlist')->loadByCustomerId($customer->getId(), true);
            //check if not shared
            if (!$this->WISHLIST->getShared()) {
                $this->WISHLIST->setShared('1');
                $this->WISHLIST->save();
            }
            $sharingCode           = $this->WISHLIST->getSharingCode();
            $this->RETURN_MESSAGE = $this->getStoreConfig('simiconnector/wishlist/sharing_message') . ' '
                    . $this->simiObjectManager->get('Magento\Framework\UrlInterface')
                    ->getUrl('wishlist/shared/index', ['code' => $sharingCode]);
            $this->RETURN_URL     = $this->simiObjectManager->get('Magento\Framework\UrlInterface')
                    ->getUrl('wishlist/shared/index', ['code' => $sharingCode]);
        } else {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Please login First.', 4));
        }
        if ($data['resourceid']) {
            if ($data['resourceid'] == 'add_all_tocart') {
                $this->addAllWishlistItemsToCart();
            } else if ($data['params']['add_to_cart']) {
                $this->addWishlistItemToCart($data['resourceid']);
            }
            $this->builderQuery = $this->WISHLIST->getItemCollection();
        } else {
            $this->builderQuery = $this->WISHLIST->getItemCollection();
        }
    }

    public function index()
    {
        $data                 = $this->getData();
        $parameters           = $data['params'];
        $result        = parent::index();
        $addition_info = [];
        foreach ($this->builderQuery as $itemModel) {
            $product    = $itemModel->getProduct();
            $isSaleAble = $product->isSaleable();
            if ($isSaleAble) {
                $itemOptions = $this->simiObjectManager->get('Magento\Wishlist\Model\Item\Option')->getCollection()
                        ->addItemFilter([$itemModel->getData('wishlist_item_id')]);
                foreach ($itemOptions as $itemOption) {
                    $optionProduct = $this->loadProductWithId($itemOption->getProductId());
                    if (!$optionProduct->isSaleable()) {
                        $isSaleAble = false;
                        break;
                    }
                }
            }

            $productSharingMessage = implode(
                ' ',
                [$this->getStoreConfig('simiconnector/wishlist/product_sharing_message'),
                $product->getProductUrl()]
            );
            $options               = $this->simiObjectManager
                    ->get('\Simi\Simiconnector\Helper\Wishlist')->getOptionsSelectedFromItem($itemModel, $product);
            
            $product = $this->loadProductWithId($product->getId());
            if (isset($parameters['image_width'])) {
                $width  = $parameters['image_width'];
                $height = $parameters['image_height'];
            } else {
                $width  = $height = 200;
            }
            $typeId = $product->getTypeId();
            if($typeId === 'bundle') {
                $budlePrice = $product->getPriceInfo()->getPrice('final_price');
                $product_price = $budlePrice->getMinimalPrice()->getValue();
                $product_regular_price = $budlePrice->getMaximalPrice()->getValue();
            } else {
                $product_regular_price = $product->getPrice();
                $product_price = $product->getFinalPrice();
            }
            $addition_info[$itemModel->getData('wishlist_item_id')] = [
                'type_id'                       => $product->getTypeId(),
                'product_regular_price'         => $product_regular_price,
                'product_price'                 => $product_price,
                'stock_status'                  => $isSaleAble,
                'product_image'                 => $this->simiObjectManager
                    ->get('\Simi\Simiconnector\Helper\Products')->getImageProduct($product, null, $width, $height),
                'is_show_price'                 => true,
                'options'                       => $options,
                'selected_all_required_options' => $this->simiObjectManager
                    ->get('\Simi\Simiconnector\Helper\Wishlist')
                    ->checkIfSelectedAllRequiredOptions($itemModel, $options),
                'product_sharing_message'       => $productSharingMessage,
                'product_sharing_url'           => $product->getProductUrl(),
                'product_url_key'               => $product->getData('url_key'),
                'product_sku'                   => $product->getSku(),
                'app_prices'                    => (isset($parameters['no_price']) && $parameters['no_price']) ?
                    array():
                    $this->simiObjectManager->get('\Simi\Simiconnector\Helper\Price')
                        ->formatPriceFromProduct($product, true),
            ];
        }
        foreach ($result['wishlistitems'] as $index => $item) {
            $result['wishlistitems'][$index] = array_merge($item, $addition_info[$item['wishlist_item_id']]);
        }
        return $result;
    }

    /*
     * Add To Wishlist
     */

    public function store()
    {
        $data    = $this->getData();
        $params  = $this->simiObjectManager
                ->get('\Simi\Simiconnector\Model\Api\Quoteitems')->convertParams((array) $data['contents']);
        $product = $this->simiObjectManager->create('Magento\Catalog\Model\Product')->load(($params['product']));
        $buyRequest = $this->simiObjectManager->create('\Magento\Framework\DataObject', ['data'=>$params]);
        $this->builderQuery = $this->WISHLIST->addNewItem($product, $buyRequest);
        return $this->show();
    }

    /*
     * Remove From Wishlist
     */

    public function destroy()
    {
        $data = $this->getData();
        $item = $this->simiObjectManager->create('Magento\Wishlist\Model\Item')->load($data['resourceid']);
        if ($item->getId()) {
            $item->delete();
            $this->WISHLIST->save();
            $this->simiObjectManager->get('Magento\Wishlist\Helper\Data')->calculate();
        }
        $this->builderQuery = $this->WISHLIST->getItemCollection();
        return $this->index();
    }

    /*
     * Add From Wishlist To Cart
     */

    public function addWishlistItemToCart($itemId)
    {
        foreach ($this->WISHLIST->getItemCollection() as $wishlistItem) {
            if ($wishlistItem->getData('wishlist_item_id') == $itemId) {
                $item = $wishlistItem;
            }
        }
        $product = $item->getProduct();
        $options = $this->simiObjectManager
                ->get('\Simi\Simiconnector\Helper\Wishlist')->getOptionsSelectedFromItem($item, $product);
        if ($item && ($this->simiObjectManager
                ->get('\Simi\Simiconnector\Helper\Wishlist')->checkIfSelectedAllRequiredOptions($item))) {
            $isSaleAble = $product->isSaleable();
            if ($isSaleAble) {
                $item    = $this->simiObjectManager->create('Magento\Wishlist\Model\Item')->load($itemId);
                $item->setQty('1');
                $cart    = $this->simiObjectManager->get('Magento\Checkout\Model\Cart');
                $options = $this->simiObjectManager->get('Magento\Wishlist\Model\Item\Option')->getCollection()
                        ->addItemFilter([$itemId]);
                $item->setOptions($options->getOptionsByItem($itemId));
                if ($item->addToCart($cart, true)) {
                    $cart->save()->getQuote()->collectTotals();
                }
                $this->WISHLIST->save();
                $this->simiObjectManager->get('Magento\Wishlist\Helper\Data')->calculate();
            }
        }
    }

    /*
     * Show An Item
     */

    public function show()
    {
        $data = $this->getData();
        $useIndex= false;
        if (isset($data['params']) && isset($data['params']['add_to_cart']) && $data['params']['add_to_cart'])
            $useIndex = true;
        if (isset($data['resourceid']) && isset($data['resourceid']) && ($data['resourceid'] == 'add_all_tocart'))
            $useIndex = true;

        if ($useIndex) {
            $this->builderQuery = $this->WISHLIST->getItemCollection();
            return $this->index();
        }
        
        return parent::show();
    }

    /*
     * Add All wishlist to cart
     */
    public function addAllWishlistItemsToCart()
    {
        $wishlist   = $this->WISHLIST;
        $this->RETURN_MESSAGE = '';

        $addedItems = array();

        $cart       = $this->simiObjectManager->get('Magento\Checkout\Model\Cart');
        $collection = $wishlist->getItemCollection()
            ->setVisibilityFilter();

        foreach ($collection as $item) {
            try {
                $disableAddToCart = $item->getProduct()->getDisableAddToCart();
                $item->unsProduct();

                $item->getProduct()->setDisableAddToCart($disableAddToCart);
                if ($item->addToCart($cart, true)) {
                    $addedItems[] = $item->getProduct();
                }

            } catch (\Exception $e) {
                $this->RETURN_MESSAGE .= $e->getMessage();
            }
        }

        if ($addedItems) {
            $wishlist->save();
            $products = array();
            foreach ($addedItems as $product) {
                $products[] = '"' . $product->getName() . '"';
            }
            $this->RETURN_MESSAGE =
                __('Products have been added to shopping cart');
            $cart->save()->getQuote()->collectTotals();
        }
        $this->simiObjectManager->get('Magento\Wishlist\Helper\Data')->calculate();
    }
    
    /*
     * Add Message
     */

    public function getList($info, $all_ids, $total, $page_size, $from)
    {
        $result = parent::getList($info, $all_ids, $total, $page_size, $from);
        if ($this->RETURN_MESSAGE) {
            $result['message'] = [$this->RETURN_MESSAGE];
        }
        if ($this->RETURN_URL) {
            $result['sharing_url'] = [$this->RETURN_URL];
        }
        return $result;
    }
    
    public function loadProductWithId($id)
    {
        $categoryModel    = $this->simiObjectManager
                ->create('Magento\Catalog\Model\Product')->load($id);
        return $categoryModel;
    }
}

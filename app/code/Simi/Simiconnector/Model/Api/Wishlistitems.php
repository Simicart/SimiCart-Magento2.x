<?php
/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Wishlistitems extends Apiabstract
{
    protected $_DEFAULT_ORDER = 'wishlist_item_id';
    protected $_RETURN_MESSAGE;
    protected $_RETURN_URL;
    protected $_WISHLIST;

    public function setBuilderQuery() {
        $data = $this->getData();
        $customer = $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomer();
        if ($customer->getId() && ($customer->getId() != '')) {
            $this->_WISHLIST = $this->_objectManager->get('Magento\Wishlist\Model\Wishlist')->loadByCustomerId($customer->getId(), true);
            //check if not shared
            if (!$this->_WISHLIST->getShared()) {
                $this->_WISHLIST->setShared('1');
                $this->_WISHLIST->save();
            }
            $sharingCode = $this->_WISHLIST->getSharingCode();
            $this->_RETURN_MESSAGE = $this->getStoreConfig('simiconnector/wishlist/sharing_message') . ' ' . $this->_objectManager->get('Magento\Framework\UrlInterface')->getUrl('*/shared/index', ['code' => $sharingCode]);
            $this->_RETURN_URL = $this->_objectManager->get('Magento\Framework\UrlInterface')->getUrl('wishlist/shared/index', ['code' => $sharingCode]);
        } else
            throw new \Exception(__('Please login First.', 4));
        if ($data['resourceid']) {
            $this->builderQuery = $this->_objectManager->create('Magento\Wishlist\Model\Item')->load($data['resourceid']);
            if ($data['params']['add_to_cart']) {
                $this->addWishlistItemToCart($data['resourceid']);
                $this->builderQuery = $this->_WISHLIST->getItemCollection();
            }
        } else {
            $this->builderQuery = $this->_WISHLIST->getItemCollection();
        }
    }

    public function index() {
        $result = parent::index();
        $addition_info = array();
        foreach ($this->builderQuery as $itemModel) {
            $product = $itemModel->getProduct();
            $isSaleAble = $product->isSaleable();
            if ($isSaleAble) {
                $itemOptions = $this->_objectManager->get('Magento\Wishlist\Model\Item\Option')->getCollection()
                        ->addItemFilter(array($itemModel->getData('wishlist_item_id')));
                foreach ($itemOptions as $itemOption) {
                    $optionProduct = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($itemOption->getProductId());
                    if (!$optionProduct->isSaleable()) {
                        $isSaleAble = false;
                        break;
                    }
                }
            }

            $productSharingMessage = implode(' ', array($this->getStoreConfig('simiconnector/wishlist/product_sharing_message'), $product->getProductUrl()));
            $options = $this->_objectManager->get('\Simi\Simiconnector\Helper\Wishlist')->getOptionsSelectedFromItem($itemModel, $product);
            if (isset($parameters['image_width'])) {
                $width = $parameters['image_width'];
                $height = $parameters['image_height'];
            } else {
                $width = $height = 200;
            }
            $addition_info[$itemModel->getData('wishlist_item_id')] = array(
                'type_id' => $product->getTypeId(),
                'product_regular_price' => $product->getPrice(),
                'product_price' => $product->getFinalPrice(),
                'stock_status' => $isSaleAble,
                'product_image' => $this->_objectManager->get('\Simi\Simiconnector\Helper\Products')->getImageProduct($product, null, $width, $height),
                'is_show_price' => true,
                'options' => $options,
                'selected_all_required_options' => $this->_objectManager->get('\Simi\Simiconnector\Helper\Wishlist')->checkIfSelectedAllRequiredOptions($itemModel, $options),
                'product_sharing_message' => $productSharingMessage,
                'product_sharing_url' => $product->getProductUrl(),
                'app_prices' => $this->_objectManager->get('\Simi\Simiconnector\Helper\Price')->formatPriceFromProduct($product, true),
            );
        }
        foreach ($result['wishlistitems'] as $index => $item) {
            $result['wishlistitems'][$index] = array_merge($item, $addition_info[$item['wishlist_item_id']]);
        }
        return $result;
    }

    /*
     * Add To Wishlist
     */

    public function store() {
        $data = $this->getData();
        $params = $this->_objectManager->get('\Simi\Simiconnector\Model\Api\Quoteitems')->convertParams((array) $data['contents']);
        $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load(($params['product']));
        if (isset($params['qty'])) {
            $params['qty'] = $this->_objectManager->create('Magento\Wishlist\Model\Wishlist')->process($params['qty']);
        }
        $buyRequest = new Varien_Object($params);
        $this->builderQuery = $this->_WISHLIST->addNewItem($product, $buyRequest);
        return $this->show();
    }

    /*
     * Remove From Wishlist
     */

    public function destroy() {
        $data = $this->getData();
        $item = $this->_objectManager->create('Magento\Wishlist\Model\Item')->load($data['resourceid']);
        if ($item->getId()) {
            $item->delete();
            $this->_WISHLIST->save();
            $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();
        }
        $this->builderQuery = $this->_WISHLIST->getItemCollection();
        return $this->index();
    }

    /*
     * Add From Wishlist To Cart
     */

    public function addWishlistItemToCart($itemId) {
        foreach ($this->_WISHLIST->getItemCollection() as $wishlistItem) {
            if ($wishlistItem->getData('wishlist_item_id') == $itemId)
                $item = $wishlistItem;
        }
        $product = $item->getProduct();
        $options = $this->_objectManager->get('\Simi\Simiconnector\Helper\Wishlist')->getOptionsSelectedFromItem($item, $product);
        if ($item && ($this->_objectManager->get('\Simi\Simiconnector\Helper\Wishlist')->checkIfSelectedAllRequiredOptions($item, $options))) {
            $isSaleAble = $product->isSaleable();
            if ($isSaleAble) {
                $item = $this->_objectManager->create('Magento\Wishlist\Model\Item')->load($itemId);
                $item->setQty('1');
                $cart = $this->_objectManager->create('Magento\Checkout\Model\Cart');
                $options = $this->_objectManager->get('Magento\Wishlist\Model\Item\Option')->getCollection()
                        ->addItemFilter(array($itemId));
                $item->setOptions($options->getOptionsByItem($itemId));
                if ($item->addToCart($cart, true)) {
                    $cart->save()->getQuote()->collectTotals();
                }
                $this->_WISHLIST->save();
                $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();
            }
        }
    }

    /*
     * Show An Item
     */

    public function show() {
        $data = $this->getData();
        if (isset($data['params']) && isset($data['params']['add_to_cart']) && $data['params']['add_to_cart']) {
            $this->builderQuery = $this->_WISHLIST->getItemCollection();
            return $this->index();
        }
        return parent::show();
    }

    /*
     * Add Message
     */

    public function getList($info, $all_ids, $total, $page_size, $from) {
        $result = parent::getList($info, $all_ids, $total, $page_size, $from);
        if ($this->_RETURN_MESSAGE) {
            $result['message'] = array($this->_RETURN_MESSAGE);
        }
        if ($this->_RETURN_URL) {
            $result['sharing_url'] = array($this->_RETURN_URL);
        }
        return $result;
    }
}

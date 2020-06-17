<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Quoteitems extends Apiabstract
{

    public $DEFAULT_ORDER = 'item_id';
    public $RETURN_MESSAGE;
    public $removed_items;
    public $detail_list;
    public $estimateShipping;
    public $estimateAddress;

    public function _getSession()
    {
        return $this->simiObjectManager->create('Magento\Checkout\Model\Session');
    }

    public function _getCart()
    {
        return $this->simiObjectManager->get('Magento\Checkout\Model\Cart');
    }

    public function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    public function setBuilderQuery()
    {
        $data = $this->getData();
        $quote              = $this->_getQuote();
//        $this->estimateShipping();
        if (isset($data['resourceid']) &&
            $data['resourceid'] && isset($data['params']) &&
            isset($data['params']['move_to_wishlist']) &&
            $data['params']['move_to_wishlist'] &&
            $this->simiObjectManager->get('Magento\Customer\Model\Session')->isLoggedIn()) {
            $this->moveToWishlist($data['resourceid']);
            $this->removed_items = [$data['resourceid']];
            $this->RETURN_MESSAGE = __('Item has been moved to Wishlist');
        }
        $this->builderQuery = $quote->getItemsCollection();
    }

    /*
     * Change Qty, Add/remove Coupon Code
     */

    public function update()
    {
        $data       = $this->getData();
        $parameters = (array) $data['contents'];
        if (isset($parameters['coupon_code'])) {
            $this->RETURN_MESSAGE = $this->simiObjectManager
                    ->get('Simi\Simiconnector\Helper\Coupon')->setCoupon($parameters['coupon_code']);
        }
        $this->_updateItems($parameters);
        return $this->index();
    }

    private function _updateItems($parameters)
    {
        $cartData = [];
        foreach ($parameters as $index => $qty) {
            $cartData[$index] = ['qty' => $qty];
        }
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($cartData)) {
            $filter       = $this->simiObjectManager
                    ->create('\Magento\Framework\Filter\LocalizedToNormalized', ['locale' => $this->simiObjectManager
                    ->create('Magento\Framework\Locale\ResolverInterface')->getLocale()]);
            $removedItems = [];
            foreach ($cartData as $index => $data) {
                if (isset($data['qty'])) {
                    $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                    if ($data['qty'] == 0) {
                        $removedItems[] = $index;
                    }
                }
            }
            $this->removed_items = $removedItems;
            $cart                 = $this->_getCart();
            if (!$cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                $cart->getQuote()->setCustomerId(null);
            }
            $cartData = $cart->suggestItemsQty($cartData);
            $cart->updateItems($cartData)->save();
            $this->_getSession()->setCartWasUpdated(true);
        }
    }

    /*
     * Add To Cart
     */

    public function store()
    {
        $this->addToCart();
        return $this->index();
    }

    public function addToCart()
    {
        $data = $this->getData();
        $cart = $this->_getCart();

        $controller = $data['controller'];

        /*
         * The same with param parsing on Simi\Simiconnector\Model\Server, but to Array instead
         */
        $params = isset($data['contents_array'])?$data['contents_array']:array();
        $params = isset($params)?$this->convertParams($params):array();
        if (isset($params['qty'])) {
            $filter        = $this->simiObjectManager
                    ->create('\Magento\Framework\Filter\LocalizedToNormalized', ['locale' => $this->simiObjectManager
                    ->create('Magento\Framework\Locale\ResolverInterface')->getLocale()]);
            $params['qty'] = $filter->filter($params['qty']);
        }

        $product               = $this->_initProduct($params['product']);
        $cart->addProduct($product, $params);
        $this->_getSession()->setCartWasUpdated(true);
        // $this->eventManager->dispatch(
        //     'checkout_cart_add_product_complete',
        //     ['product' => $product, 'request' => $controller->getRequest(),
        //     'response' => $controller->getResponse()]
        // );
        $this->RETURN_MESSAGE = __('You added %1 to your shopping cart.', $product->getName());
    }

    public function convertParams($params)
    {
        $convertList = [
            //Custom Option (Simple/Virtual/Downloadable)
            'options',
            //Configurable Product
            'super_attribute',
            //Group Product
            'super_group',
            //Bundle Product
            'bundle_option',
            //Bundle Product Qty
            'bundle_option_qty',
        ];
        foreach ($convertList as $type) {
            if (!isset($params[$type])) {
                continue;
            }
            $params[$type]  = (array) $params[$type];
            $convertedParam = [];
            foreach ($params[$type] as $index => $item) {
                $convertedParam[(int) $index] = $item;
            }
            $params[$type] = $convertedParam;
        }
        return $params;
    }

    public function _initProduct($productId)
    {
        if ($productId) {
            $storeId = $this->simiObjectManager
                    ->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getId();
            return $this->simiObjectManager
                    ->create('Magento\Catalog\Api\ProductRepositoryInterface')->getById($productId, false, $storeId);
        }
        return false;
    }

    /*
     * Return Cart Detail
     */

    public function show()
    {
        return $this->index();
    }

    public function index()
    {
        $this->estimateShipping();
        $this->_getQuote()->collectTotals()->save();
        $collection = $this->builderQuery;
        $collection->addFieldToFilter('item_id', ['nin' => $this->removed_items])
                ->addFieldToFilter('parent_item_id', ['null' => true]);

        $this->filter();
        $data       = $this->getData();
        $parameters = $data['params'];
        $page       = 1;
        
        $limit = self::DEFAULT_LIMIT;
        $offset = 0;
        $this->setPageSize($parameters, $limit, $offset, $collection, $page);
        $all_ids = [];
        $info    = [];
        $total   = $collection->getSize();
        if ($offset > $total) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Invalid method.'), 4);
        }

        $fields = [];
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }

        $check_limit  = 0;
        $check_offset = 0;
        
        $is_can_checkout = '1';

        /*
         * Add options and image
         */
        foreach ($collection as $entity) {
            if ((++$check_offset <= $offset) ||
                    ($entity->getData('parent_item_id') != null) ||
                    ($this->removed_items && in_array($entity->getData('item_id'), $this->removed_items))) {
                continue;
            }
            $options = [];
            switch ($entity->getProductType()) {
                case 'configurable':
                    $block   = $this->simiObjectManager
                        ->get('Magento\ConfigurableProduct\Block\Cart\Item\Renderer\Configurable');
                    $block->setItem($entity);
                    $options = $this->simiObjectManager
                            ->get('Simi\Simiconnector\Helper\Checkout')->convertOptionsCart($block->getOptionList());
                    break;
                case 'bundle':
                    $block   = $this->simiObjectManager
                        ->get('Magento\Bundle\Block\Checkout\Cart\Item\Renderer');
                    $block->setItem($entity);
                    $options = $this->simiObjectManager
                            ->get('Simi\Simiconnector\Helper\Checkout')->convertOptionsCart($block->getOptionList());
                    break;
                case 'downloadable':
                    $block   = $this->simiObjectManager
                        ->get('Magento\Downloadable\Block\Checkout\Cart\Item\Renderer');
                    $block->setItem($entity);
                    $options = $this->simiObjectManager
                            ->get('Simi\Simiconnector\Helper\Checkout')->convertOptionsCart($block->getOptionList());
                    break;
                default:
                    $block   = $this->simiObjectManager->get('Magento\Checkout\Block\Cart\Item\Renderer');
                    $block->setItem($entity);
                    $options = $this->simiObjectManager
                            ->get('Simi\Simiconnector\Helper\Checkout')->convertOptionsCart($block->getOptionList());
                    break;
            }

            $quoteitem           = $entity->toArray($fields);
            $quoteitem['option'] = $options;
            $quoteitem['image']  = $this->simiObjectManager
                    ->create('Simi\Simiconnector\Helper\Products')
                    ->getImageProduct(
                        $this->loadProductWithId($entity->getProduct()->getId()),
                        null,
                        $parameters['image_width'],
                        $parameters['image_height']
                    );
            
            $productModel    = $this->simiObjectManager->create('Magento\Catalog\Model\Product')->load($entity->getProduct()->getId());
            if (!$productModel->isSalable()) {
                $is_can_checkout = '0';
                $quoteitem['product']['is_salable'] = 0;
            } else {
                $quoteitem['product']['is_salable'] = 1;
            }
            
            $info[]              = $quoteitem;
            $all_ids[]           = $entity->getId();
        }
        $this->detail_list = $this->getList($info, $all_ids, $total, $limit, $offset, $is_can_checkout);
        $this->eventManager->dispatch(
            'simi_simiconnector_model_api_quoteitems_index_after',
            ['object' => $this, 'data' => $this->detail_list]
        );
        return $this->detail_list;
    }
    
    private function setPageSize($parameters, &$limit, &$offset, $collection, &$page)
    {
        if (isset($parameters[self::PAGE]) && $parameters[self::PAGE]) {
            $page = $parameters[self::PAGE];
        }
        if (isset($parameters[self::LIMIT]) && $parameters[self::LIMIT]) {
            $limit = $parameters[self::LIMIT];
        }
        $offset = $limit * ($page - 1);
        if (isset($parameters[self::OFFSET]) && $parameters[self::OFFSET]) {
            $offset = $parameters[self::OFFSET];
        }
        $collection->setPageSize($offset + $limit);
    }


    /*
     * Move to Wishlist
     */
    public function moveToWishlist($itemId) {
        $customer = $this->simiObjectManager->get('Magento\Customer\Model\Session')->getCustomer();
        if ($customer->getId() && ($customer->getId() != '')) {
            $wishlist = $this->simiObjectManager
                ->get('Magento\Wishlist\Model\Wishlist')->loadByCustomerId($customer->getId(), true);
        } else {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Please login first'), 4);
        }
        $cart = $this->_getCart();

        $item = $cart->getQuote()->getItemById($itemId);
        if (!$item) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Requested cart item doesn\'t exist'), 4);
        }
        $productId  = $item->getProductId();
        $buyRequest = $item->getBuyRequest();
        $wishlist->addNewItem($productId, $buyRequest);
        $productIds[] = $productId;
        $cart->getQuote()->removeItem($itemId);
        $cart->save();
        $this->simiObjectManager->get('Magento\Wishlist\Helper\Data')->calculate();
        $this->RETURN_MESSAGE = __("Item has been moved to wishlist");
        $wishlist->save();
    }

    /*
     * Add Message
     */

    public function getList($info, $all_ids, $total, $page_size, $from, $is_can_checkout = '1')
    {
        $result          = parent::getList($info, $all_ids, $total, $page_size, $from);
        $result['total'] = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Total')->getTotal();
        
        if($this->estimateAddress && $this->estimateShipping) {
            $result['estimate_shipping'] = [
                'address' => $this->estimateAddress,
                'shipping_method' => $this->estimateShipping
            ];
        }
        
        if ($this->RETURN_MESSAGE) {
            $result['message'] = [$this->RETURN_MESSAGE];
        }
        $session              = $this->_getSession();
        $result['cart_total'] = $this->_getCart()->getItemsCount();
        $result['quote_id']   = $session->getQuoteId();
        $result['is_can_checkout'] = $is_can_checkout;
        try {
            $result['masked_id'] = $this->simiObjectManager
                ->get('Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface')
                ->execute($result['quote_id']);
        } catch (\Exception $e) {

        }
        
        $customerSession = $this->simiObjectManager->get('Magento\Customer\Model\Session');
        $result['customer_email'] = $customerSession->isLoggedIn()?
            $customerSession->getCustomer()->getEmail():
            null;

        return $result;
    }
    
    public function loadProductWithId($id)
    {
        $categoryModel    = $this->simiObjectManager
                ->create('Magento\Catalog\Model\Product')->load($id);
        return $categoryModel;
    }
    
    protected function estimateShipping() {
        $quote              = $this->_getQuote();
        
        $customerSession = $this->simiObjectManager->get('Magento\Customer\Model\Session');
        if ($quote->getItemsCount() > 0 && $customerSession->isLoggedIn()) {
            $defaultShippingId = $customerSession->getCustomer()->getDefaultShipping();
            $addressArray = [];
            foreach ($customerSession->getCustomer()->getAddresses() as $index => $address) {
                $addressArray[] = $index;
            }
            if(!in_array($defaultShippingId, $addressArray)) {
                if(count($addressArray) > 0) {
                    $defaultShippingId = $addressArray[0];
                } else {
                    $defaultShippingId = null;
                }
            }
            if($defaultShippingId) {
                $defaultShipping = $this->simiObjectManager
                ->create('Magento\Customer\Model\Address')->load($defaultShippingId);
                if (!$quote->getIsVirtual()) {
                    $quote->getShippingAddress()->addData($defaultShipping->toArray());
                    $quote->getShippingAddress()
                    ->setCollectShippingRates(true)
                    ->collectShippingRates();
                    $shippingRates = $quote->getShippingAddress()->getGroupedAllShippingRates();
                    $shippingMethod = null;
                    foreach ($shippingRates as $carrierRates) {
                        foreach ($carrierRates as $rate) {
                            $shippingMethod = $rate;
                            break;
                        }
                        if($shippingMethod) {
                            break;
                        }
                    }
                    if($shippingMethod) {
                        $this->estimateShipping = $shippingMethod->toArray();
                        $this->estimateAddress = $defaultShipping->toArray();
                        $quote->getShippingAddress()->setShippingMethod($shippingMethod->getCode());
                    }
                    $quote->save();
                }
                $quote->collectTotals();
            } else {
                $quote->getShippingAddress()->setShippingMethod(null);
            }
        }
    }
}

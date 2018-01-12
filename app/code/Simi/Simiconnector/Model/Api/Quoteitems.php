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

    public function _getSession()
    {
        return $this->simiObjectManager->create('Magento\Checkout\Model\Session');
    }

    public function _getCart()
    {
        return $this->simiObjectManager->create('Magento\Checkout\Model\Cart');
    }

    public function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    public function setBuilderQuery()
    {
        $quote              = $this->_getQuote();
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
            $cart->updateItems($cartData);
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
        $zendHTTPRequestHttp = $this->simiObjectManager->get('Simi\Simiconnector\Helper\RequestHttp');
        $contents            = $zendHTTPRequestHttp->getRawBody();
        if ($contents && ($contents != '')) {
            $contents = urldecode($contents);
            $params   = json_decode($contents, true);
        }
        $params = $this->convertParams($params);

        if (isset($params['qty'])) {
            $filter        = $this->simiObjectManager
                    ->create('\Magento\Framework\Filter\LocalizedToNormalized', ['locale' => $this->simiObjectManager
                    ->create('Magento\Framework\Locale\ResolverInterface')->getLocale()]);
            $params['qty'] = $filter->filter($params['qty']);
        }

        $product               = $this->_initProduct($params['product']);
        $cart->addProduct($product, $params);
        $cart->save();
        $this->_getSession()->setCartWasUpdated(true);
        $this->eventManager->dispatch(
            'checkout_cart_add_product_complete',
            ['product' => $product, 'request' => $controller->getRequest(),
            'response' => $controller->getResponse()]
        );
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
            $info[]              = $quoteitem;
            $all_ids[]           = $entity->getId();
        }
        $this->detail_list = $this->getList($info, $all_ids, $total, $limit, $offset);
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
     * Add Message
     */

    public function getList($info, $all_ids, $total, $page_size, $from)
    {
        $result          = parent::getList($info, $all_ids, $total, $page_size, $from);
        $result['total'] = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Total')->getTotal();
        if ($this->RETURN_MESSAGE) {
            $result['message'] = [$this->RETURN_MESSAGE];
        }
        $session              = $this->_getSession();
        $result['cart_total'] = $this->_getCart()->getItemsCount();
        $result['quote_id']   = $session->getQuoteId();
        return $result;
    }
    
    public function loadProductWithId($id)
    {
        $categoryModel    = $this->simiObjectManager
                ->create('Magento\Catalog\Model\Product')->load($id);
        return $categoryModel;
    }
}

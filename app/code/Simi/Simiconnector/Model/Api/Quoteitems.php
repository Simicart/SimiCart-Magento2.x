<?php
/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;


class Quoteitems extends Apiabstract
{
    
    protected $_DEFAULT_ORDER = 'item_id';
    protected $_RETURN_MESSAGE;
    protected $_removed_items;
    public $detail_list;

    protected function _getSession() {
        return $this->_objectManager->create('Magento\Checkout\Model\Session');
    }

    protected function _getCart() {
        return $this->_objectManager->create('Magento\Checkout\Model\Cart');
    }

    protected function _getQuote() {
        return $this->_getCart()->getQuote();
    }

    public function setBuilderQuery() {
        $quote = $this->_getQuote();
        $this->builderQuery = $quote->getItemsCollection();
    }

    /*
     * Change Qty, Add/remove Coupon Code
     */

    public function update() {
        $data = $this->getData();
        $parameters = (array) $data['contents'];
        if (isset($parameters['coupon_code'])) {
            $this->_RETURN_MESSAGE = $this->_objectManager->get('Simi\Simiconnector\Helper\Coupon')->setCoupon($parameters['coupon_code']);
        }
        $this->_updateItems($parameters);
        return $this->index();
    }

    private function _updateItems($parameters) {
        $cartData = array();
        foreach ($parameters as $index => $qty) {
            $cartData[$index] = array('qty' => $qty);
        }
        if (count($cartData)) {
            $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->create('Magento\Framework\Locale\ResolverInterface')->getLocale()]
            );
            $removedItems = array();
            foreach ($cartData as $index => $data) {
                if (isset($data['qty'])) {
                    $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                    if ($data['qty'] == 0) {
                        $removedItems[] = $index;
                    }
                }
            }
            $this->_removed_items = $removedItems;
            $cart = $this->_getCart();
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

    public function store() {
        $this->addToCart();
        return $this->index();
    }

    public function addToCart() {
        $data = $this->getData();
        $cart = $this->_getCart();

        $controller = $data['controller'];
        
        /*
         * The same with param parsing on Simi\Simiconnector\Model\Server, but to Array instead
         */
        $zendHTTPRequestHttp = new \Zend_Controller_Request_Http;
        $contents = $zendHTTPRequestHttp->getRawBody();
        if ($contents && strlen($contents)) {
            $contents = urldecode($contents);
            $params = json_decode($contents, true);
        }
        $params = $this->convertParams($params);
        
        if (isset($params['qty'])) {
            $filter = new \Zend_Filter_LocalizedToNormalized(
                ['locale' => $this->_objectManager->create('Magento\Framework\Locale\ResolverInterface')->getLocale()]
            );
            $params['qty'] = $filter->filter($params['qty']);
        }
        
        $product = $this->_initProduct($params['product']);
        $cart->addProduct($product, $params);
        $cart->save();
        $this->_getSession()->setCartWasUpdated(true);
        $this->_eventManager->dispatch('checkout_cart_add_product_complete', array('product' => $product, 'request' => $controller->getRequest(), 'response' => $controller->getResponse()));
        $this->_RETURN_MESSAGE = __('You added %1 to your shopping cart.', $product->getName());
    }

    public function convertParams($params) {
        $convertList = array(
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
        );
        foreach ($convertList as $type) {
            if (!isset($params[$type])) {
                continue;
            }
            $params[$type] = (array) $params[$type];
            $convertedParam = array();
            foreach ($params[$type] as $index => $item) {
                $convertedParam[(int) $index] = $item;
            }
            $params[$type] = $convertedParam;
        }
        return $params;
    }

    protected function _initProduct($productId) {
        if ($productId) {
            $storeId = $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getId();
            return $this->_objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface')->getById($productId, false, $storeId);
        }
        return false;
    }

    /*
     * Return Cart Detail
     */

    public function show() {
        return $this->index();
    }

    public function index() {
        $this->_getQuote()->collectTotals()->save();
        $collection = $this->builderQuery;
        $collection->addFieldToFilter('item_id', array('nin' => $this->_removed_items))
                ->addFieldToFilter('parent_item_id', array('null' => true));

        $this->filter();
        $data = $this->getData();
        $parameters = $data['params'];
        $page = 1;
        if (isset($parameters[self::PAGE]) && $parameters[self::PAGE]) {
            $page = $parameters[self::PAGE];
        }

        $limit = self::DEFAULT_LIMIT;
        if (isset($parameters[self::LIMIT]) && $parameters[self::LIMIT]) {
            $limit = $parameters[self::LIMIT];
        }

        $offset = $limit * ($page - 1);
        if (isset($parameters[self::OFFSET]) && $parameters[self::OFFSET]) {
            $offset = $parameters[self::OFFSET];
        }
        $collection->setPageSize($offset + $limit);

        $all_ids = array();
        $info = array();
        $total = $collection->getSize();

        if ($offset > $total) {
            throw new \Exception(__('Invalid method.'), 4);
        }

        $fields = array();
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }

        $check_limit = 0;
        $check_offset = 0;
        
        /*
         * Add options and image
         */
        foreach ($collection as $entity) {
            if (++$check_offset <= $offset) {
                continue;
            }
            /*
            if (++$check_limit > $limit)
                break;
            */
            if ($entity->getData('parent_item_id') != NULL)
                continue;

            if ($this->_removed_items) {
                if (in_array($entity->getData('item_id'), $this->_removed_items)) {
                    continue;
                }
            }

            $options = array();
            
            if ($entity->getProductType() == "configurable") {
                $block = $this->_objectManager->get('Magento\ConfigurableProduct\Block\Cart\Item\Renderer\Configurable');
                $block->setItem($entity);
                $options = $this->_objectManager->get('Simi\Simiconnector\Helper\Checkout')->convertOptionsCart($block->getOptionList());
            } elseif ($entity->getProductType() == "bundle") {
                $block = $this->_objectManager->get('Magento\Bundle\Block\Checkout\Cart\Item\Renderer');
                $block->setItem($entity);
                $options = $this->_objectManager->get('Simi\Simiconnector\Helper\Checkout')->convertOptionsCart($block->getOptionList());
            } elseif ($entity->getProductType() == "downloadable") {
                $block = $this->_objectManager->get('Magento\Downloadable\Block\Checkout\Cart\Item\Renderer');
                $block->setItem($entity);
                $options = $this->_objectManager->get('Simi\Simiconnector\Helper\Checkout')->convertOptionsCart($block->getOptionList());
            } else {
                $block = $this->_objectManager->get('Magento\Checkout\Block\Cart\Item\Renderer');
                $block->setItem($entity);
                $options = $this->_objectManager->get('Simi\Simiconnector\Helper\Checkout')->convertOptionsCart($block->getOptionList());
            }
            
            
            $quoteitem = $entity->toArray($fields);
            $quoteitem['option'] = $options;
            $quoteitem['image'] = $this->_objectManager->create('Simi\Simiconnector\Helper\Products')->getImageProduct($this->_objectManager->create('Magento\Catalog\Model\Product')->load($entity->getProduct()->getId()), null, $parameters['image_width'], $parameters['image_height']);
            $info[] = $quoteitem;
            $all_ids[] = $entity->getId();
        }
        $this->detail_list = $this->getList($info, $all_ids, $total, $limit, $offset);
        $this->_eventManager->dispatch('simi_simiconnector_model_api_quoteitems_index_after', array('object' => $this, 'data' => $this->detail_list));
        return $this->detail_list;
    }

    /*
     * Add Message
     */

    public function getList($info, $all_ids, $total, $page_size, $from) {
        $result = parent::getList($info, $all_ids, $total, $page_size, $from);
        $result['total'] = $this->_objectManager->get('Simi\Simiconnector\Helper\Total')->getTotal();
        if ($this->_RETURN_MESSAGE) {
            $result['message'] = array($this->_RETURN_MESSAGE);
        }
        $session = $this->_getSession();
        $result['cart_total'] = $this->_getCart()->getItemsCount();
        $result['quote_id'] = $session->getQuoteId();
        return $result;
    }

}

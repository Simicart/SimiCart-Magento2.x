<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Orders extends Apiabstract
{

    public $DEFAULT_ORDER = 'entity_id';
    public $RETURN_MESSAGE;
    public $QUOTE_INITED  = false;
    public $detail_onepage;
    public $place_order;
    public $order_placed_info;
    public $time_zone = null;

    public function _getCart()
    {
        return $this->simiObjectManager->get('Magento\Checkout\Model\Cart');
    }

    public function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    public function _getCheckoutSession()
    {
        return $this->simiObjectManager->create('Magento\Checkout\Model\Session');
    }

    public function _getOnepage()
    {
        return $this->simiObjectManager->create('Magento\Checkout\Model\Type\Onepage');
    }

    public function setBuilderQuery()
    {
        $data = $this->getData();
        if ($data['resourceid']) {
            if ($data['resourceid'] == 'onepage') {
                return;
            } else {
                if (isset($data['params']['by_entity_id'])) {
                    $this->builderQuery = $this->simiObjectManager->create('Magento\Sales\Model\Order')
                            ->load($data['resourceid']);
                }
                try {
                    $this->builderQuery = $this->simiObjectManager->create('Magento\Sales\Model\Order')
                            ->loadByIncrementId($data['resourceid']);
                } catch (\Exception $e) {

                }
                if (!$this->builderQuery || !$this->builderQuery->getId()) {
                    $this->builderQuery = $this->simiObjectManager->create('Magento\Sales\Model\Order')
                            ->load($data['resourceid']);
                }
                if (!$this->builderQuery || !$this->builderQuery->getId()) {
                    throw new \Simi\Simiconnector\Helper\SimiException(__('Cannot find the Order'), 6);
                }
            }
        } else {
            $this->builderQuery = $this->simiObjectManager->create('Magento\Sales\Model\Order')->getCollection()
                    ->addFieldToFilter(
                        'customer_id',
                        $this->simiObjectManager->create('Magento\Customer\Model\Session')->getCustomer()->getId()
                    )
                    ->setOrder('entity_id', 'DESC');
        }
    }

    /*
     * Update Checkout Order (onepage) Information
     */

    public function update()
    {
        $data = $this->getData();
        if ($data['resourceid'] == 'onepage') {
            $this->_updateOrder();
            return $this->show();
        } else {
            $order = $this->builderQuery;
            $param = $data['contents'];
            if ($param->status == 'cancel') {
                $order->cancel();
                $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
                $order->save();
            } else {
                $order->setState($param->status, true);
                $order->save();
            }
            return $this->show();
        }
    }

    private function _updateOrder()
    {
        $data       = $this->getData();
        $parameters = (array) $data['contents'];

        if (isset($parameters['b_address'])) {
            $this->_initCheckout();
            $this->simiObjectManager->get('Simi\Simiconnector\Helper\Address')
                    ->saveBillingAddress($parameters['b_address']);
            if (!isset($parameters['s_address']) && (!$this->_getQuote()->getShippingAddress()->getFirstName())) {
                $parameters['s_address'] = $parameters['b_address'];
            }
        }

        if (isset($parameters['s_address'])) {
            $this->_initCheckout();
            $this->simiObjectManager->get('Simi\Simiconnector\Helper\Address')
                    ->saveShippingAddress($parameters['s_address']);
            if (!isset($parameters['b_address']) && (!$this->_getQuote()->getBillingAddress()->getFirstName())) {
                $this->simiObjectManager->get('Simi\Simiconnector\Helper\Address')
                    ->saveBillingAddress($parameters['s_address']);
            }
        }

        if (isset($parameters['coupon_code'])) {
            $this->RETURN_MESSAGE = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Coupon')
                    ->setCoupon($parameters['coupon_code']);
        }

        if (isset($parameters['s_method'])) {
            $this->simiObjectManager->get('Simi\Simiconnector\Helper\Checkout\Shipping')
                    ->saveShippingMethod($parameters['s_method']);
        }

        if (isset($parameters['p_method'])) {
            $this->simiObjectManager->get('Simi\Simiconnector\Helper\Checkout\Payment')
                    ->savePaymentMethod($parameters['p_method']);
        }

        $this->_getOnepage()->getQuote()->collectTotals()->save();
    }

    private function _initCheckout()
    {
        if (!$this->QUOTE_INITED) {
            $this->_getCheckoutSession()->setCartWasUpdated(false);
            $this->_getOnepage()->initCheckout();
            $this->QUOTE_INITED = true;
        }
    }

    /*
     * Place Order
     */

    public function store()
    {
        $this->_updateOrder();

        $this->place_order = true;
        $this->eventManager
                ->dispatch(
                    'simi_simiconnector_model_api_orders_onepage_store_before',
                    ['object' => $this, 'data' => $this->getData()]
                );

        if (!$this->place_order) {
            $result = ['order' => $this->order_placed_info];
            return $result;
        }

        $quote = $this->_getQuote();
        if (!$quote->validateMinimumAmount()) {
            throw new \Simi\Simiconnector\Helper\SimiException($this
                    ->getStoreConfig('sales/minimum_order/error_message'), 4);
        }
        /*
         * Checkout as New Customer Data Adding
         */
        if (!$this->simiObjectManager->create('Magento\Customer\Model\Session')->isLoggedIn() &&
                $this->_getQuote()->getPasswordHash()) {
            $billingAddress   = $quote->getBillingAddress();
            $customer         = $this->simiObjectManager->create('Magento\Customer\Model\Data\Customer')
                    ->setFirstname($billingAddress->getFirstname())
                    ->setLastname($billingAddress->getLastname())
                    ->setEmail($billingAddress->getEmail());
            $addressDataArray = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Address')
                    ->getAddressDetail($billingAddress, $customer);
            foreach ($addressDataArray as $index => $dataAddressItem) {
                $customer->setData($index, $dataAddressItem);
            }
            $this->_getQuote()->setCustomer($customer);
        }
        /*
         * Place Order
         */
        $this->simiObjectManager->create('Magento\Quote\Api\CartManagementInterface')
                ->placeOrder($this->_getQuote()->getId());
        $order = ['invoice_number' => $this->_getCheckoutSession()->getLastRealOrderId(),
            'payment_method' => $this->_getOnepage()->getQuote()->getPayment()->getMethodInstance()->getCode()
        ];
        try {
            $orderModel        = $this->simiObjectManager->create('Magento\Sales\Model\Order')
                ->loadByIncrementId($this->_getCheckoutSession()->getLastRealOrderId());
            if($orderModel->getCanSendNewEmailFlag()) {
                $orderSender = $this->simiObjectManager->get('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
                $orderSender->send($orderModel);
            }
        } catch (\Exception $exc) {

        }

        /*
         * App notification
         */
        if ($this->getStoreConfig('simi_notifications/noti_purchase/noti_purchase_enable')) {
            $categoryId   = $this->getStoreConfig('simi_notifications/noti_purchase/noti_purchase_category_id');
            $notification = [];
            if ($categoryId) {
                $category              = $this->simiObjectManager
                        ->create('\Magento\Catalog\Model\Category')->load($categoryId);
                $categoryName          = $category->getName();
                $categoryChildrenCount = $category->getChildrenCount();
                if ($categoryChildrenCount > 0) {
                    $categoryChildrenCount = 1;
                } else {
                    $categoryChildrenCount = 0;
                }
                $notification['categoryID']   = $this
                        ->getStoreConfig('simi_notifications/noti_purchase/noti_purchase_category_id');
                $notification['categoryName'] = $categoryName;
                $notification['has_children'] = $categoryChildrenCount;
            }
            $notification['show_popup']    = '1';
            $notification['title']         = $this
                    ->getStoreConfig('simi_notifications/noti_purchase/noti_purchase_title');
            $notification['url']           = $this
                    ->getStoreConfig('simi_notifications/noti_purchase/noti_purchase_url');
            $notification['message']       = $this
                    ->getStoreConfig('simi_notifications/noti_purchase/noti_purchase_message');
            $notification['notice_sanbox'] = 0;
            $notification['type']          = $this
                    ->getStoreConfig('simi_notifications/noti_purchase/noti_purchase_type');
            $notification['productID']     = $this
                    ->getStoreConfig('simi_notifications/noti_purchase/noti_purchase_product_id');
            $notification['created_time']  = $this->simiObjectManager
                    ->create('\Magento\Framework\Stdlib\DateTime\DateTimeFactory')->create()->gmtDate();
            $notification['notice_type']   = 3;
            $order['notification']         = $notification;
        }

        $session = $this->_getOnepage()->getCheckout();
        $session->resetCheckout();

        $this->order_placed_info = $order;
        $this->eventManager->dispatch(
            'simi_simiconnector_model_api_orders_onepage_store_after',
            ['object' => $this, 'data' => $this->detail_onepage]
        );
        $this->cleanCheckoutSession();
        $result = ['order' => $this->order_placed_info];
        return $result;
    }

    public function cleanCheckoutSession()
    {
        /*
         * Be VERY carefully uncommenting the lines below, will cause errors saving image custom options
         *
        try {
            $quote = $this->_getQuote();
            $quote->setIsActive(false);
            $quote->delete();
        } catch (\Exception $e) {
            $this->_getCheckoutSession()->clearQuote()->clearStorage();
        }
        */
        $checkoutSession = $this->_getCheckoutSession();
        $checkoutSession->clearQuote();
        $checkoutSession->clearStorage();
        $checkoutSession->clearHelperData();
        $checkoutSession->resetCheckout();
        $checkoutSession->restoreQuote();
    }

    /*
     * Return Order Detail (History and Onepage)
     */

    public function show()
    {
        $data = $this->getData();
        if ($data['resourceid'] == 'onepage') {
            $customer      = $this->simiObjectManager->create('Magento\Customer\Model\Session')->getCustomer();
            $quote         = $this->_getQuote();
            $list_payment  = [];
            $paymentHelper = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Checkout\Payment');
            foreach ($paymentHelper->getMethods() as $method) {
                $list_payment[] = $paymentHelper->getDetailsPayment($method);
            }
            $order                     = [];

            /*
            $savedOnce = false;
            
            if (!$quote->getBillingAddress()->getFirstName() && $customer->getData('default_billing')) {
                $defaultBilling = $this->simiObjectManager->create('\Magento\Framework\DataObject');
                $defaultBilling->entity_id = $customer->getData('default_billing');
                $this->simiObjectManager->get('Simi\Simiconnector\Helper\Address')
                        ->saveBillingAddress($defaultBilling);
                $savedOnce = true;
            }

            if (!$quote->getShippingAddress()->getFirstName() && $customer->getData('default_shipping')) {
                $defaultShipping = $this->simiObjectManager->create('\Magento\Framework\DataObject');
                $defaultShipping->entity_id = $customer->getData('default_shipping');
                $this->simiObjectManager->get('Simi\Simiconnector\Helper\Address')
                        ->saveShippingAddress($defaultShipping);
                $savedOnce = true;
            }
            if ($savedOnce)
                $this->_getOnepage()->getQuote()->collectTotals()->save();
            */
                
            $order['billing_address']  = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Address')
                    ->getAddressDetail($quote->getBillingAddress(), $customer);
            $order['shipping_address'] = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Address')
                    ->getAddressDetail($quote->getShippingAddress(), $customer);
            $order['shipping']         = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Checkout\Shipping')
                    ->getMethods();
            $order['payment']          = $list_payment;
            $order['total']            = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Total')->getTotal();
            $detail_onepage            = ['order' => $order];
            if ($this->RETURN_MESSAGE) {
                $detail_onepage['message'] = [$this->RETURN_MESSAGE];
            }
            $this->detail_onepage = $detail_onepage;
            $this->eventManager->dispatch(
                'simi_simiconnector_model_api_orders_onepage_show_after',
                ['object' => $this, 'data' => $this->detail_onepage]
            );
            return $this->detail_onepage;
        } else {
            $isAuth = false;
            if ($this->simiObjectManager->create('Magento\Customer\Model\Session')->isLoggedIn()) {
                $customerId = $this->simiObjectManager->create('Magento\Customer\Model\Session')->getCustomer()->getId();
                if ($customerId == $this->builderQuery->getData('customer_id'))
                    $isAuth = true;
            }
            if (!$isAuth)
                throw new \Simi\Simiconnector\Helper\SimiException(__('Not Authorized'), 6);

            $result = parent::show();
            if ($data['params']['reorder'] == 1) {
                if ($this->builderQuery && $this->builderQuery->getId()) {
                    // $order = $this->simiObjectManager->create('Magento\Sales\Model\Order')->load($data['resourceid']);
                    $cart  = $this->_getCart();
                    $items = $this->builderQuery->getItemsCollection();
                    foreach ($items as $item) {
                        $cart->addOrderItem($item);
                    }
                    $cart->save();
                    $result['message'] = __('Reorder Succeeded');
                } else {
                    $result['message'] = __('Can not re-order this order');
                }
            }
            $order           = $result['order'];
            $customer        = $this->simiObjectManager->create('Magento\Customer\Model\Session')->getCustomer();
            $this->_updateOrderInformation($order, $customer);
            $result['order'] = $order;
            return $result;
        }
    }

    /*
     * Order History
     */

    public function index()
    {
        $result   = parent::index();
        $customer = $this->simiObjectManager->create('Magento\Customer\Model\Session')->getCustomer();
        foreach ($result['orders'] as $index => $order) {
            $this->_updateOrderInformation($order, $customer);
            $result['orders'][$index] = $order;
        }
        return $result;
    }

    private function _updateOrderInformation(&$order, $customer)
    {
        if (!$customer || ($customer->getData('email') !== $order['customer_email']))
            return;

        $orderModel               = $this->simiObjectManager
                ->create('Magento\Sales\Model\Order')->load($order['entity_id']);
        $order['payment_method']  = $orderModel->getPayment()->getMethodInstance()->getTitle();
        $order['shipping_method'] = $orderModel->getShippingDescription();
        $order['billing_address'] = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Address')
                ->getAddressDetail($orderModel->getBillingAddress(), $customer);
        if (!$orderModel->getShippingAddress()) {
            $order['shipping_address'] = $order['billing_address'];
        } else {
            $order['shipping_address'] = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Address')
                    ->getAddressDetail($orderModel->getShippingAddress(), $customer);
        }
        $order['billing_address'] = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Address')
                ->getAddressDetail($orderModel->getBillingAddress(), $customer);
        $order['order_items']     = $this->_getProductFromOrderHistoryDetail($orderModel);
        if (!$this->time_zone) {
            $this->time_zone = $this->simiObjectManager->create('\Magento\Framework\Stdlib\DateTime\TimezoneInterface');
        }
        $order['created_at']      = $this->time_zone->date($order['created_at'])->format('Y-m-d H:i:s');
        $order['updated_at']      = $this->time_zone->date($order['updated_at'])->format('Y-m-d H:i:s');

        $order['total']           = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Total')
                ->showTotalOrder($orderModel);
    }

    public function _getProductFromOrderHistoryDetail($order)
    {
        $productInfo    = [];
        $itemCollection = $order->getAllVisibleItems();
        foreach ($itemCollection as $item) {
            $options = [];
            if ($item->getProductOptions()) {
                $options = $this->_getOptions($item->getProductType(), $item->getProductOptions());
            }
            $images = array();
            $parent_sku = null;
            if ($product = $item->getProduct()) {
                $images = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Products')
                ->getImageProduct($product);
                if ($item->getProductType() == 'configurable'){
                    $parent_sku = $product->getSku();
                }
            }
            $productInfo[] = array_merge(
                ['option' => $options],
                $item->toArray(),
                ['image' => $images],
                ['parent_sku' => $parent_sku]
            );
        }

        return $productInfo;
    }

    public function _getOptions($type, $options)
    {
        $list = [];
        if ($type == 'bundle') {
            foreach ($options['bundle_options'] as $option) {
                foreach ($option['value'] as $value) {
                    $list[] = [
                        'option_title' => $option['label'],
                        'option_value' => $value['title'],
                        'option_price' => $value['price'],
                    ];
                }
            }
        } else {
            $options     = [];
            $optionsList = [];
            if (isset($options['additional_options'])) {
                $optionsList = $options['additional_options'];
            } elseif (isset($options['attributes_info'])) {
                $optionsList = $options['attributes_info'];
            } elseif (isset($options['options'])) {
                $optionsList = $options['options'];
            }
            foreach ($optionsList as $option) {
                $list[] = [
                    'option_title' => $option['label'],
                    'option_value' => $option['value'],
                    'option_price' => isset($option['price']) == true ? $option['price'] : 0,
                ];
            }
        }
        return $list;
    }
}

<?php
/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;


class Orders extends Apiabstract
{
    protected $_DEFAULT_ORDER = 'entity_id';
    protected $_RETURN_MESSAGE;
    protected $_QUOTE_INITED = FALSE;
    public $detail_onepage;


    protected function _getCart() {
        return $this->_objectManager->get('Magento\Checkout\Model\Cart');
    }

    protected function _getQuote() {
        return $this->_getCart()->getQuote();
    }

    protected function _getCheckoutSession() {
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }

    public function _getOnepage() {
        return $this->_objectManager->get('Magento\Checkout\Model\Type\Onepage');
    }

    public function setBuilderQuery() {
        $data = $this->getData();
        if ($data['resourceid']) {
            if ($data['resourceid'] == 'onepage') {
                
            } else {
                $this->builderQuery = $this->_objectManager->get('Magento\Sales\Model\Order')->load($data['resourceid']);
                $order = $this->builderQuery;
                if (!$this->builderQuery->getId()) {
                    $this->builderQuery = $this->_objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($data['resourceid']);
                }
                if (!$this->builderQuery->getId()) {
                    throw new \Exception(__('Cannot find the Order'), 6);
                }
            }
        } else {
            $this->builderQuery = $this->_objectManager->get('Magento\Sales\Model\Order')->getCollection()
                    ->addFieldToFilter('customer_id', $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomer()->getId())
                    ->setOrder('entity_id', 'DESC');
        }
    }

    /*
     * Update Checkout Order (onepage) Information
     */

    public function update() {
        $data = $this->getData();
        if ($data['resourceid'] == 'onepage') {
            $this->_updateOrder();
            return $this->show();
        } else {
            $order = $this->builderQuery;
            $param = $data['contents'];
            if ($param->status == 'cancel') {
                $order->cancel();
                $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
                $order->save();
            } else {
                $order->setState($param->status, true);
                $order->save();
            }
            return $this->show();
        }
    }

    private function _updateOrder() {
        $data = $this->getData();
        $parameters = (array) $data['contents'];

        if (isset($parameters['b_address'])) {
            $this->_initCheckout();
            Mage::helper('simiconnector/address')->saveBillingAddress($parameters['b_address']);
            if (!isset($parameters['s_address']))
                $parameters['s_address'] = $parameters['b_address'];
        }
        if (isset($parameters['s_address'])) {
            $this->_initCheckout();
            Mage::helper('simiconnector/address')->saveShippingAddress($parameters['s_address']);
        }

        if (isset($parameters['coupon_code'])) {
            $this->_RETURN_MESSAGE = Mage::helper('simiconnector/coupon')->setCoupon($parameters['coupon_code']);
        }
        if (isset($parameters['s_method'])) {
            Mage::helper('simiconnector/checkout_shipping')->saveShippingMethod($parameters['s_method']);
        }
        if (isset($parameters['p_method'])) {
            Mage::helper('simiconnector/checkout_payment')->savePaymentMethod($parameters['p_method']);
        }
        $this->_getOnepage()->getQuote()->collectTotals()->save();
    }

    private function _initCheckout() {
        if (!$this->_QUOTE_INITED) {
            $this->_getCheckoutSession()->setCartWasUpdated(false);
            $this->_getOnepage()->initCheckout();
            $this->_QUOTE_INITED = TRUE;
        }
    }

    /*
     * Place Order
     */

    public function store() {
        $this->_updateOrder();
        $quote = $this->_getQuote();
        if (!$quote->validateMinimumAmount()) {
            throw new \Exception(Mage::getStoreConfig('sales/minimum_order/error_message'), 4);
        }
        $this->_getOnepage()->saveOrder();
        $this->_getOnepage()->getQuote()->save();
        $order = array('invoice_number' => $this->_getCheckoutSession()->getLastRealOrderId(),
            'payment_method' => $this->_getOnepage()->getQuote()->getPayment()->getMethodInstance()->getCode()
        );

        /*
         * save To App report
         */
        try {
            $orderId = Mage::getModel('sales/order')->loadByIncrementId($this->_getCheckoutSession()->getLastRealOrderId())->getId();
            $newTransaction = Mage::getModel('simiconnector/appreport');
            $newTransaction->setOrderId($orderId);
            $newTransaction->save();
        } catch (\Exception $exc) {
            
        }

        /*
         * App notification
         */
        if (Mage::getStoreConfig('simiconnector/notification/noti_purchase_enable')) {
            $categoryId = Mage::getStoreConfig('simiconnector/notification/noti_purchase_category_id');
            $category = Mage::getModel('catalog/category')->load($categoryId);
            $categoryName = $category->getName();
            $categoryChildrenCount = $category->getChildrenCount();
            if ($categoryChildrenCount > 0)
                $categoryChildrenCount = 1;
            else
                $categoryChildrenCount = 0;

            $notification['show_popup'] = '1';
            $notification['title'] = Mage::getStoreConfig('simiconnector/notification/noti_purchase_title');
            $notification['url'] = Mage::getStoreConfig('simiconnector/notification/noti_purchase_url');
            $notification['message'] = Mage::getStoreConfig('simiconnector/notification/noti_purchase_message');
            $notification['notice_sanbox'] = 0;
            $notification['type'] = Mage::getStoreConfig('simiconnector/notification/noti_purchase_type');
            $notification['productID'] = Mage::getStoreConfig('simiconnector/notification/noti_purchase_product_id');
            $notification['categoryID'] = Mage::getStoreConfig('simiconnector/notification/noti_purchase_category_id');
            $notification['categoryName'] = $categoryName;
            $notification['has_children'] = $categoryChildrenCount;
            $notification['created_time'] = now();
            $notification['notice_type'] = 3;           
            $order['notification'] = $notification;
        }

        $result = array('order' => $order);
        return $result;
    }

    /*
     * Return Order Detail (History and Onepage)
     */

    public function show() {
        $data = $this->getData();
        if ($data['resourceid'] == 'onepage') {
            $list_payment = array();
            $paymentHelper = Mage::helper('simiconnector/checkout_payment');
            foreach (Mage::helper('simiconnector/checkout_payment')->getMethods() as $method) {
                $list_payment[] = $paymentHelper->getDetailsPayment($method);
            }
            $order = array();
            $quote = $this->_getQuote();
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $order['billing_address'] = Mage::helper('simiconnector/address')->getAddressDetail($quote->getBillingAddress(), $customer);
            $order['shipping_address'] = Mage::helper('simiconnector/address')->getAddressDetail($quote->getShippingAddress(), $customer);
            $order['shipping'] = Mage::helper('simiconnector/checkout_shipping')->getMethods();
            $order['payment'] = $list_payment;
            $order['total'] = Mage::helper('simiconnector/total')->getTotal();			
			$detail_onepage = array('order' => $order);
			if ($this->_RETURN_MESSAGE) {
				$detail_onepage['message'] = array($this->_RETURN_MESSAGE);
			}
            $this->detail_onepage = $detail_onepage;
            Mage::dispatchEvent('Simi_Simiconnector_Model_Api_Orders_Onepage_Show_After', array('object' => $this, 'data' => $this->detail_onepage));
            return $this->detail_onepage;
        } else {
            $result = parent::show();
            if($data['params']['reorder'] == 1){
                $order = $this->_objectManager->get('Magento\Sales\Model\Order')->load($data['resourceid']);
                $cart = $this->_getCart();
                $items = $order->getItemsCollection();
                foreach ($items as $item) {
                    $cart->addOrderItem($item);
                }
                $cart->save();
                $result['message'] = __('Reorder Succeeded');
            }
            $order = $result['order'];
            $customer = $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomer();
            $this->_updateOrderInformation($order, $customer);
            $result['order'] = $order;
            return $result;
        }
    }

    /*
     * Order History
     */

    public function index() {
        $result = parent::index();
        $customer = $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomer();
        foreach ($result['orders'] as $index => $order) {
            $this->_updateOrderInformation($order, $customer);
            $result['orders'][$index] = $order;
        }
        return $result;
    }

    private function _updateOrderInformation(&$order, $customer) {
        $orderModel = $this->_objectManager->get('Magento\Sales\Model\Order')->load($order['entity_id']);
        $order['payment_method'] = $orderModel->getPayment()->getMethodInstance()->getTitle();
        $order['shipping_method'] = $orderModel->getShippingDescription();
        $order['shipping_address'] = $this->_objectManager->get('Simi\Simiconnector\Helper\Address')->getAddressDetail($orderModel->getShippingAddress(), $customer);
        $order['billing_address'] = $this->_objectManager->get('Simi\Simiconnector\Helper\Address')->getAddressDetail($orderModel->getBillingAddress(), $customer);
        $order['order_items'] = $this->_getProductFromOrderHistoryDetail($orderModel);
        $order['total'] = $this->_objectManager->get('Simi\Simiconnector\Helper\Total')->showTotalOrder($orderModel);
    }

	/*
    private function _getProductFromOrderList($itemCollection) {
        $productInfo = array();
        foreach ($itemCollection as $item) {
            $productInfo[] = $item->toArray();
        }
        return $productInfo;
    }
	*/
    
    public function _getProductFromOrderHistoryDetail($order) {
        $productInfo = array();
        $itemCollection = $order->getAllVisibleItems();
        foreach ($itemCollection as $item) {
            $options = array();
            if ($item->getProductOptions()) {
                $options = $this->_getOptions($item->getProductType(), $item->getProductOptions());
            }
            $productInfo[] = array_merge( array('option' => $options),
            $item->toArray(),
            array('image' => $this->_objectManager->get('Simi\Simiconnector\Helper\Products')->getImageProduct($item->getProduct()))
            );
        }

        return $productInfo;
    }
    
    public function _getOptions($type, $options) {
        $list = array();
        if ($type == 'bundle') {
            foreach ($options['bundle_options'] as $option) {
                foreach ($option['value'] as $value) {
                    $list[] = array(
                        'option_title' => $option['label'],
                        'option_value' => $value['title'],
                        'option_price' => $value['price'],
                    );
                }
            }
        } else {
            $options = array();
            $optionsList = array();
            if (isset($options['additional_options'])) {
                $optionsList = $options['additional_options'];
            } elseif (isset($options['attributes_info'])) {
                $optionsList = $options['attributes_info'];
            } elseif (isset($options['options'])) {
                $optionsList = $options['options'];
            }
            foreach ($optionsList as $option) {
                $list[] = array(
                    'option_title' => $option['label'],
                    'option_value' => $option['value'],
                    'option_price' => isset($option['price']) == true ? $option['price'] : 0,
                );
            }
        }
        return $list;
    }

}

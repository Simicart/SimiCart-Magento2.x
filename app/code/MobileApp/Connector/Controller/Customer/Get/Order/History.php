<?php

namespace MobileApp\Connector\Controller\Customer\Get\Order;

class History extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Sales\Api\OrderRepositoryInterface';
        $this->_serviceMethodName = 'getList';

        $params = $this->_getParams();

        $session = $this->_objectManager->get('Magento\Customer\Model\Session');
        $customerId = $session->getCustomer()->getId();

        $criteria = [];
        $criteria['sort_orders'] = ['field' => 'entity_id', 'direction' => 'DESC'];
        $criteria['filter_groups'][] = [
            'filters' => [
                [
                    'field' => 'customer_id',
                    'value' => $customerId
                ]
            ]
        ];
        $criteria['page_size'] = isset($params['limit'])?$params['limit']:self::DEFAULT_PAGE_SIZE;
        $criteria['current_page'] = round(isset($params['offset'])?$params['offset']:0/$criteria['page_size'])+1;
        $this->_params = ['searchCriteria' => $criteria];

        return parent::execute();
    }

    /*
     * Format data
     *
     * @param $data array
     * @return array
     */
    protected function _formatData($data){
        $orders = [];
        foreach($data['items'] as $order){
            $items = [];
            foreach($order['items'] as $item){
                if(!isset($item['parent_item']))
                    $items[] = [
                        'product_name' => $item['name']
                    ];
            }
            $orders[] = [
                'order_id' => $order['increment_id'],
                'order_status' => $order['status'],
                'order_date'=> $order['created_at'],
                'recipient'=> $order['customer_firstname'].' '.$order['customer_lastname'],
                'order_items'=> $items,

            ];
        }
        return ['data' => $orders, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}

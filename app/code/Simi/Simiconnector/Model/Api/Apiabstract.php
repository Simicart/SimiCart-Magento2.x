<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

abstract class Apiabstract {

    public $FILTER_RESULT = true;

    const DEFAULT_DIR = 'asc';
    const DEFAULT_LIMIT = 15;
    const DIR = 'dir';
    const ORDER = 'order';
    const PAGE = 'page';
    const LIMIT = 'limit';
    const OFFSET = 'offset';
    const FILTER = 'filter';
    const ALL_IDS = 'all_ids';
    const LIMIT_COUNT = 200;
    const MEDIA_PATH = 'Simiconnector';

    protected $_DEFAULT_ORDER = 'entity_id';
    protected $_objectManager;
    protected $_storeManager;
    protected $_scopeConfig;
    protected $_resource;
    public $storeRepository;
    public $storeCookieManager;

    /**
     * Singular key.
     *
     * @var string
     */
    protected $_helper;

    /**
     * Singular key.
     *
     * @var string
     */
    protected $singularKey;

    /**
     * Plural key.
     *
     * @var string
     */
    protected $pluralKey;
    /**
     *
     */

    /**
     * @var collection Magento
     */
    protected $builderQuery = null;
    protected $_data;
    protected $_eventManager;

    abstract public function setBuilderQuery();

    public function __construct() {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_scopeConfig = $this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->_storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $this->storeRepository = $this->_objectManager->get('\Magento\Store\Api\StoreRepositoryInterface');
        $this->storeCookieManager = $this->_objectManager->get('\Magento\Store\Api\StoreCookieManagerInterface');
        $this->_resource = $this->_objectManager->get('\Magento\Framework\App\ResourceConnection');
        $this->_eventManager = $this->_objectManager->get('\Magento\Framework\Event\ManagerInterface');
        return $this;
    }

    public function setDataValue($data) {
        $this->_data = $data;
    }

    public function setData($data) {
        $this->_data = $data;
    }

    public function getData() {
        return $this->_data;
    }

    /**
     * Get singular key
     * @return string
     */
    public function getSingularKey() {
        return $this->singularKey;
    }

    /**
     * Set singular query
     * @return $this
     */
    public function setSingularKey($singularKey) {
        $this->singularKey = substr($singularKey, 0, -1);
        return $this;
    }

    /**
     * Get singular key
     * @return string
     */
    public function getPluralKey() {
        return $this->pluralKey;
    }

    /**
     * Set singular query
     * @return $this
     */
    public function setPluralKey($pluralKey) {
        $this->pluralKey = $pluralKey;
        return $this;
    }

    //start
    public function store() {
        return $this->getDetail(array());
    }

    public function index() {
        $collection = $this->builderQuery;
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

        if ($offset > $total)
            throw new \Exception(__('Invalid method.'), 4);

        $fields = array();
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }

        $check_limit = 0;
        $check_offset = 0;

        foreach ($collection as $entity) {
            if (++$check_offset <= $offset) {
                continue;
            }
            if (++$check_limit > $limit)
                break;

            $info[] = $entity->toArray($fields);
            $all_ids[] = $entity->getId();
        }
        return $this->getList($info, $all_ids, $total, $limit, $offset);
    }

    public function show() {
        $entity = $this->builderQuery;
        $data = $this->getData();
        $parameters = $data['params'];
        $fields = array();
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }
        $info = $entity->toArray($fields);
        return $this->getDetail($info);
    }

    public function update() {
        return $this->getDetail(array());
    }

    public function destroy() {
        return $this->getDetail(array());
    }

    //end
    public function getBuilderQuery() {
        return $this->builderQuery;
    }

    public function callApi($data) {
        $this->renewCustomerSesssion($data);
        $this->setDataValue($data);
        $this->setBuilderQuery(null);
        $this->setPluralKey($data['resource']);
        $this->setSingularKey($data['resource']);
        if ($data['is_method'] == 1) {
            if (isset($data['resourceid']) && $data['resourceid'] != '') {
                return $this->show($data['resourceid']);
            } else {
                return $this->index();
            }
        } elseif ($data['is_method'] == 2) {
            return $this->store();
        } elseif ($data['is_method'] == 3) {
            return $this->update($data['resourceid']);
        } elseif ($data['is_method'] == 4) {
            return $this->destroy($data['resourceid']);
        }
    }

    public function getList($info, $all_ids, $total, $page_size, $from) {
        return array(
            'all_ids' => $all_ids,
            $this->getPluralKey() => $this->motifyFields($info),
            'total' => $total,
            'page_size' => $page_size,
            'from' => $from,
        );
    }

    public function getDetail($info) {
        return array($this->getSingularKey() => $this->motifyFields($info));
    }

    protected function filter() {
        if (!$this->FILTER_RESULT)
            return;
        $data = $this->_data;
        $parameters = $data['params'];
        $query = $this->builderQuery;
        $this->_whereFilter($query, $parameters);
        $this->_order($parameters);

        return $query;
    }

    protected function _order($parameters) {
        $query = $this->builderQuery;
        $order = isset($parameters[self::ORDER]) ? $parameters[self::ORDER] : $this->_DEFAULT_ORDER;
        $order = str_replace('|', '.', $order);
        $dir = isset($parameters[self::DIR]) ? $parameters[self::DIR] : self::DEFAULT_DIR;
        $query->setOrder($order, $dir);
    }

    protected function _whereFilter(&$query, $parameters) {
        if (isset($parameters[self::FILTER])) {
            foreach ($parameters[self::FILTER] as $key => $value) {
                if ($key == 'or') {
                    $filters = array();
                    foreach ($value as $k => $v) {
                        $filters[] = $this->_addCondition($k, $v, true);
                    }
                    if (count($filters))
                        $query->addAttributeToFilter($filters);
                } else {
                    $filter = $this->_addCondition($key, $value);
                    $query->addAttributeToFilter($key, $filter);
                }
            }
        }
    }

    protected function _addCondition($key, $value, $isOr = false) {
        $key = str_replace('|', '.', $key);
        if (is_array($value)) {
            foreach ($value as $operator => $v) {
                if ($operator == 'in' || $operator == 'nin') {
                    return $isOr ? array('attribute' => $key, $operator => explode(',', $v)) : array($operator => explode(',', $v));
                } else {
                    return $isOr ? array('attribute' => $key, $operator => $v) : array($operator => $v);
                }
            }
        } else {
            if (strlen($value) > 0) {
                return $isOr ? array('attribute' => $key, 'eq' => $value) : array('eq' => $value);
            }
        }
    }

    /*
     * Get Store Configuration Value
     */

    public function getStoreConfig($path) {
        return $this->_scopeConfig->getValue($path);
    }

    /**
     * @return string
     */
    public function getMediaUrl($media_path) {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . $media_path;
    }

    //Max update to get fields
    protected function motifyFields($content) {
        $data = $this->getData();
        $parameters = $data['params'];
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
            $motify = array();
            foreach ($content as $key => $item) {
                if (in_array($key, $fields)) {
                    $motify[$key] = $item;
                }
            }
            return $motify;
        } else {
            return $content;
        }
    }

    protected function renewCustomerSesssion($data) {
        $this->_objectManager->get('Simi\Simiconnector\Helper\Customer')->renewCustomerSesssion($data);
    }
}

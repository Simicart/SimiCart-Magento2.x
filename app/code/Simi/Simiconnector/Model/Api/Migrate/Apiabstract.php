<?php

namespace Simi\Simiconnector\Model\Api\Migrate;

abstract class Apiabstract extends \Simi\Simiconnector\Model\Api\Apiabstract
{

    public $FILTER_RESULT = false;
    const DEFAULT_LIMIT = 999999999;

    public function index()
    {
        $collection = $this->builderQuery;
        $this->filter();
        $data       = $this->getData();
        $parameters = $data['params'];
        $page       = 1;
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

        foreach ($collection as $entity) {
            if (++$check_offset <= $offset) {
                continue;
            }
            if (++$check_limit > $limit) {
                break;
            }

            $info[]    = $entity->toArray($fields);
        }
        return $this->getList($info, null, $total, $limit, $offset);
    }

    public function renewCustomerSesssion($data)
    {
        return false;
    }
}

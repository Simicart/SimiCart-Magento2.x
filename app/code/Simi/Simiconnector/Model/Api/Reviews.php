<?php
/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Reviews extends Apiabstract
{
    
    protected $_helper;
    protected $_allow_filter_core = false;

    public function setBuilderQuery()
    {
        // TODO: Implement setBuilderQuery() method.
        $this->_helper = $this->_objectManager->get('\Simi\Simiconnector\Helper\Review');
        $data = $this->getData();
        $parameters = $data['params'];
        if ($data['resourceid']) {
            $this->builderQuery = $this->_helper->getReview($data['resourceid']);
        } else {
            if (isset($parameters[self::FILTER])) {
                $filter = $parameters[self::FILTER];
                $this->builderQuery =  $this->_helper->getReviews($filter['product_id']);
            }
        }
    }

    /**
     * @return collection
     * override
     */
    protected function filter()
    {
        $data = $this->_data;
        $parameters = $data['params'];
        if ($this->_allow_filter_core) {
            $query = $this->builderQuery;
            $this->_whereFilter($query, $parameters);
        }
        if (isset($parameters['dir']) && isset($parameters['order'])) {
            $this->_order($parameters);
        }

        return null;
    }

    /**
     * @return override
     */
    public function store()
    {
        $data = $this->getData();
        $content = $data['contents_array'];
        $review = $this->_helper->saveReview($content);
        $entity = $review['review'];
        $parameters = $data['params'];
        $fields = [];
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }
        $info = $entity->toArray($fields);
        $detail = $this->getDetail($info);
        $detail['message'] = $review['message'];
        return $detail;
    }

    /**
     * @param $info
     * @param $all_ids
     * @param $total
     * @param $page_size
     * @param $from
     * @return array
     * override
     */
    public function getListReview($info, $all_ids, $total, $page_size, $from, $count)
    {
        return [
            'all_ids' => $all_ids,
            $this->getPluralKey() => $info,
            'total' => $total,
            'page_size' => $page_size,
            'from' => $from,
            'count' => $count,
        ];
    }

    /**
     * @return array
     * @throws Exception
     * override
     */
    public function index()
    {
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
        $all_ids = $collection->getAllIds();
        $info = [];
        $total = $collection->getSize();

        if ($offset > $total) {
            throw new \Exception(__('Invalid method.'), 4);
        }

        $fields = [];
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }
        $star = [];
        $count = null;
        $star[0] = 0;
        $star[1] = 0;
        $star[2] = 0;
        $star[3] = 0;
        $star[4] = 0;
        $star[5] = 0;

        $check_limit = 0;
        $check_offset = 0;
        foreach ($collection as $entity) {
            if (++$check_offset <= $offset) {
                continue;
            }
            if (++$check_limit > $limit) {
                break;
            }
            $star[5]++;
            $y = 0;
            foreach ($entity->getRatingVotes() as $vote) {
                $y += ($vote->getPercent() / 20);
            }
            $x = (int) ($y / count($entity->getRatingVotes()));
            $info_detail = $entity->toArray($fields);
            $info_detail['rate_points'] = $x;
            $info[] = $info_detail;

            $z = $y % 3;
            $x = $z < 5 ? $x : $x + 1;
            if ($x == 1) {
                $star[0]++;
            } elseif ($x == 2) {
                $star[1]++;
            } elseif ($x == 3) {
                $star[2]++;
            } elseif ($x == 4) {
                $star[3]++;
            } elseif ($x == 5) {
                $star[4]++;
            } elseif ($x == 0) {
                $star[5]--;
            }
        }
        $count = [
            '1_star' => $star[0],
            '2_star' => $star[1],
            '3_star' => $star[2],
            '4_star' => $star[3],
            '5_star' => $star[4],
        ];
        return $this->getListReview($info, $all_ids, $total, $limit, $offset, $count);
    }

    /**
     * @return array
     * override
     */
    public function show()
    {
        $entity = $this->builderQuery;
        $data = $this->getData();
        $parameters = $data['params'];
        $fields = [];
        if (isset($parameters['fields']) && $parameters['fields']) {
            $fields = explode(',', $parameters['fields']);
        }
        $info = $entity->toArray($fields);
        return $this->getDetail($info);
    }
}

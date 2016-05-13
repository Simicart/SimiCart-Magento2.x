<?php

namespace MobileApp\Connector\Controller\Catalog\Get\Product;

class Review extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute() {
        $data = $this->getRequest()->getParam('data');
        $params = $data?json_decode($data, true):[];

        $productId = $this->_objectManager->create('\Magento\Catalog\Model\Product')
            ->getIdBySku($params['product_id']);

        $reviews = $this->_objectManager->create('\Magento\Review\Model\Review')
            ->getResourceCollection()
            ->addStoreFilter($this->storeManager->getStore()->getId())
            ->addEntityFilter('product', $productId)
            ->setDateOrder()
            ->addRateVotes();

        $offset = isset($data['offset'])?$data['offset']:0;
        $limit = isset($data['limit'])?$data['limit']:self::DEFAULT_PAGE_SIZE;

        $list = [];
        $star = [];
        $count = null;
        $star[0] = 0;
        $star[1] = 0;
        $star[2] = 0;
        $star[3] = 0;
        $star[4] = 0;
        $star[5] = 0;

        if ($offset <= count($reviews) && count($reviews) > 0) {
            $check_limit = 0;
            $check_offset = 0;
            foreach ($reviews->getItems() as $review) {

                if (++$check_offset <= $offset) {
                    continue;
                }
                if (++$check_limit > $limit)
                    break;
                $star[5]++;
                $y = 0;
                foreach ($review->getRatingVotes() as $vote) {
                    $y += ($vote->getPercent() / 20);
                }
                $x = (int) ($y / count($review->getRatingVotes()));
                if (isset($params['star']) && $params['star']) {
                    if ($x == $params['star']) {
                        $list[] = array(
                            'review_id' => $review->getId(),
                            'customer_name' => $review->getNickname(),
                            'review_title' => $review->getTitle(),
                            'review_body' => $review->getDetail(),
                            'review_time' => $review->getCreatedAt(),
                            'rate_point' => $x,
                        );
                    }
                } else {
                    $list[] = array(
                        'review_id' => $review->getId(),
                        'customer_name' => $review->getNickname(),
                        'review_title' => $review->getTitle(),
                        'review_body' => $review->getDetail(),
                        'review_time' => $review->getCreatedAt(),
                        'rate_point' => $x,
                    );
                }
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

            $count = array(
                '1_star' => $star[0],
                '2_star' => $star[1],
                '3_star' => $star[2],
                '4_star' => $star[3],
                '5_star' => $star[4],
            );
            $array[] = $list;
            $array[] = $count;
        }

        $information['data'] = $list;
        $information['count'] = $count;

        $outputData = ['data' => $information, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];

        /** @param \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData($outputData);
    }
}

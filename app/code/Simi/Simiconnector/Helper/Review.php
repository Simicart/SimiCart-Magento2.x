<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

class Review extends \Simi\Simiconnector\Helper\Data
{

    public function getTotalRate($rates)
    {
        $total = $rates[0] * 1 + $rates[1] * 2 + $rates[2] * 3 + $rates[3] * 4 + $rates[4] * 5;
        return $total;
    }

    public function getAvgRate($rates, $total)
    {
        if ($rates[5] != 0) {
            $avg = $total / $rates[5];
        } else {
            $avg = 0;
        }
        return $avg;
    }

    public function getRatingStar($productId)
    {
        $reviews = $this->simiObjectManager->get('Magento\Review\Model\Review')
                ->getResourceCollection()
                ->addStoreFilter($this->storeManager->getStore()->getId())
                ->addEntityFilter('product', $productId)
                ->addStatusFilter(\Magento\Review\Model\Review::STATUS_APPROVED)
                ->setDateOrder()
                ->addRateVotes();
        /**
         * Getting numbers ratings/reviews
         */
        $star    = [];
        $star[0] = 0;
        $star[1] = 0;
        $star[2] = 0;
        $star[3] = 0;
        $star[4] = 0;
        $star[5] = 0;
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($reviews) > 0) {
            foreach ($reviews->getItems() as $review) {
                $star[5] ++;
                $y = 0;
                foreach ($review->getRatingVotes() as $vote) {
                    $x = ($vote->getPercent() / 20);
                    if ($x == 1) {
                        $star[0] ++;
                    } elseif ($x == 2) {
                        $star[1] ++;
                    } elseif ($x == 3) {
                        $star[2] ++;
                    } elseif ($x == 4) {
                        $star[3] ++;
                    } elseif ($x == 5) {
                        $star[4] ++;
                    } elseif ($x == 0) {
                        $star[5] --;
                    }
                }
            }
        }
        return $star;
    }

    public function getReviews($productId)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $reviews = $this->simiObjectManager->get('Magento\Review\Model\Review')
                ->getResourceCollection()
                ->addStoreFilter($storeId)
                ->addEntityFilter('product', $productId)
                ->addStatusFilter(\Magento\Review\Model\Review::STATUS_APPROVED)
                ->setDateOrder()
                ->addRateVotes();

        return $reviews;
    }

    public function getReview($review_id)
    {
        return $this->simiObjectManager->get('Magento\Review\Model\Review')->load($review_id);
    }

    public function getReviewToAdd()
    {
        $block    = $this->simiObjectManager->get('Magento\Review\Block\Form');
        $info  = [];
        $rates = [];
        if ($block->getRatings() && $block->getRatings()->getSize()) {
            foreach ($block->getRatings() as $_rating) {
                $_options = [];
                foreach ($_rating->getOptions() as $_option) {
                    $_options[] = [
                        'key'   => $_rating->getId(),
                        'value' => $_option->getId(),
                    ];
                }
                $rates[] = [
                    'rate_code'    => $block->escapeHtml($_rating->getRatingCode()),
                    'rate_options' => $_options,
                ];
            }
        }
        $info[] = ['rates'       => $rates, 'form_review' => [
            'key_1'    => 'nickname',
            'key_2'    => 'title',
            'key_3'    => 'detail',
            'form_key' => [
                [
                    'key'   => 'nickname',
                    'value' => 'Nickname'
                ],
                [
                    'key'   => 'title',
                    'value' => 'Title'
                ],
                [
                    'key'   => 'detail',
                    'value' => 'Detail'
                ],
            ]],
        ];
        return $info;
    }

    public function _initProduct($product_id)
    {
        return $this->simiObjectManager->create('Magento\Catalog\Model\Product')->load($product_id);
    }

    public function saveReview($data)
    {
        $allowGuest = $this->simiObjectManager->get('Magento\Review\Helper\Data')->getIsGuestAllowToWrite();
        if (
            !$allowGuest &&
            !$this->simiObjectManager->get('Magento\Customer\Model\Session')->isLoggedIn()
        ) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Guest can not write'), 4);
        }
        if (($product = $this->_initProduct($data['product_id'])) && !empty($data)) {
            $rating = $data['ratings'];
            $review = $this->simiObjectManager->get('Magento\Review\Model\Review')->setData($data);
            
            $validate = $review->validate();
            if ($validate === true) {
                try {
                    $review->setEntityId($review->getEntityIdByCode(\Magento\Review\Model\Review::ENTITY_PRODUCT_CODE))
                            ->setEntityPkValue($product->getId())
                            ->setStatusId(\Magento\Review\Model\Review::STATUS_PENDING)
                            ->setCustomerId($this->simiObjectManager
                                    ->get('Magento\Customer\Model\Session')->getCustomerId())
                            ->setStoreId($this->storeManager->getStore()->getId())
                            ->setStores([$this->storeManager->getStore()->getId()])
                            ->save();
                    foreach ($rating as $ratingId => $optionId) {
                        $this->simiObjectManager->get('Magento\Review\Model\Rating')
                                ->setRatingId($ratingId)
                                ->setReviewId($review->getId())
                                ->setCustomerId($this->simiObjectManager
                                        ->get('Magento\Customer\Model\Session')->getCustomerId())
                                ->addOptionVote($optionId, $product->getId());
                    }

                    $review->aggregate();
                    return [
                        'review'  => $review,
                        'message' => __('Your review has been accepted for moderation.')];
                } catch (\Exception $e) {
                    throw new \Simi\Simiconnector\Helper\SimiException(__('Unable to post the review'), 4);
                }
            } else {
                throw new \Simi\Simiconnector\Helper\SimiException(__('Unable to post the review'), 4);
            }
        } else {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Invalid method.'), 4);
        }
    }

    public function getProductReviews($productId, $getForm = true) {
        $ratings      = $this->getRatingStar($productId);
        $total_rating = $this->getTotalRate($ratings);
        $avg          = $this->getAvgRate($ratings, $total_rating);
        return [
            'rate'             => $avg,
            'number'           => $ratings[5],
            '5_star_number'    => $ratings[4],
            '4_star_number'    => $ratings[3],
            '3_star_number'    => $ratings[2],
            '2_star_number'    => $ratings[1],
            '1_star_number'    => $ratings[0],
            'form_add_reviews' => $getForm?$this->getReviewToAdd():null,
        ];
    }
}

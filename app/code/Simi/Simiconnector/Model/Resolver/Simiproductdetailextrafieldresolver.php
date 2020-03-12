<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Simi\Simiconnector\Model\Resolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * @inheritdoc
 */
class Simiproductdetailextrafieldresolver implements ResolverInterface
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;
    public $extraFields;

    /**
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        MetadataPool $metadataPool,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager
    ) {
        $this->metadataPool = $metadataPool;
        $this->simiObjectManager = $simiObjectManager;
    }

    /**
     * Fetch and format configurable variants.
     *
     * {@inheritdoc}
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ){
        try {
            $productCollection = $this->simiObjectManager->get('Magento\Catalog\Model\Product')->getCollection()
                ->addAttributeToSelect('*');
            if ($args && isset($args['filter'])) {
                foreach ($args['filter'] as $key => $filter) {
                    $productCollection->addAttributeToFilter($key, $filter);
                }
            }
            $productModel = $productCollection->getFirstItem();
            if ($productId = $productModel->getId()) {
                $productModel    = $this->simiObjectManager->create('Magento\Catalog\Model\Product')->load($productId);
                $registry = $this->simiObjectManager->get('\Magento\Framework\Registry');
                if (!$registry->registry('product') && $productModel->getId()) {
                    $registry->register('product', $productModel);
                    $registry->register('current_product', $productModel);
                }
                $options = $this->simiObjectManager
                    ->get('\Simi\Simiconnector\Helper\Options')->getOptions($productModel);

                $app_reviews  = $this->simiObjectManager
                    ->get('\Simi\Simiconnector\Helper\Review')
                    ->getProductReviews($productModel->getId());

                $layout      = $this->simiObjectManager->get('Magento\Framework\View\LayoutInterface');
                $block_att   = $layout->createBlock('Magento\Catalog\Block\Product\View\Attributes');
                $_additional = $block_att->getAdditionalData();

                $tierPrice   = $this->simiObjectManager
                    ->get('\Simi\Simiconnector\Helper\Price')->getProductTierPricesLabel($productModel);

                $this->extraFields = array(
                    'attribute_values' => $productModel->toArray(),
                    'app_options' => $options,
                    'app_reviews' => $app_reviews,
                    'additional'  => $_additional,
                    'app_tier_prices' => $tierPrice,
                );
                $this->eventManager = $this->simiObjectManager->get('\Magento\Framework\Event\ManagerInterface');
                $this->eventManager->dispatch(
                    'simi_simiconnector_graphql_product_detail_extra_field_after',
                    ['object' => $this, 'data' => $this->extraFields]
                );
                return json_encode($this->extraFields);
            }
        } catch (\Exception $e) {
            return '';
        }
        return '';
    }
}

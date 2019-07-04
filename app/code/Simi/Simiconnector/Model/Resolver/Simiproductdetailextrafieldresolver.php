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
                //$productModel    = $this->simiObjectManager->create('Magento\Catalog\Model\Product')->load($productId);
                $options = $this->simiObjectManager
                    ->get('\Simi\Simiconnector\Helper\Options')->getOptions($productModel);
                $extraFields = array(
                    'app_options' => $options
                );
                return json_encode($extraFields);
            }
        } catch (\Exception $e) {
            return '';
        }
        return '';
    }
}

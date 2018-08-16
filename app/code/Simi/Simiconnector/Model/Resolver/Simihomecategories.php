<?php

/**
 * Created by PhpStorm.
 * User: codynguyen
 * Date: 8/16/18
 * Time: 8:47 AM
 */


declare(strict_types=1);

namespace Simi\Simiconnector\Model\Resolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;

class Simihomecategories implements ResolverInterface
{
    private $homeCategory;

    private $valueFactory;

    public function __construct(
        \Simi\Simiconnector\Model\Resolver\DataProvider\Homecategory $homeCategory,
        ValueFactory $valueFactory
    ) {
        $this->homeCategory = $homeCategory;
        $this->valueFactory = $valueFactory;
    }
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null) : Value
    {
        $homecategories = $this->homeCategory->getList();
        $data = [
            'total' => $homecategories->getSize(),
            'items' => $homecategories->getData()
        ];
        
        $result = function () use ($data) {
            return $data;
        };
        return $this->valueFactory->create($result);
    }
}

<?php

/**
 * Created by PhpStorm.
 * User: codynguyen
 * Date: 8/16/18
 * Time: 8:47 AM
 */

namespace Simi\Simiconnector\Model\Resolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;

class Simihomebanners implements ResolverInterface
{
    private $homeBanner;

    private $valueFactory;

    public function __construct(
        \Simi\Simiconnector\Model\Resolver\DataProvider\Homebanner $homeBanner,
        ValueFactory $valueFactory
    ) {
        $this->homeBanner = $homeBanner;
        $this->valueFactory = $valueFactory;
    }
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null) : Value
    {
        $data = $this->homeBanner->getListData();
        $result = function () use ($data) {
            return $data;
        };
        return $this->valueFactory->create($result);
    }
}

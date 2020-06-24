<?php

namespace Simi\Simiconnector\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;

class SimiConfig extends ConfigValue
{
    public $simiObjectManager;
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->simiObjectManager = $simiObjectManager;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }
    public function beforeSave()
    {
        if (class_exists('Magento\Framework\Serialize\Serializer\Json')) {
            $serializer = $this->simiObjectManager->get('Magento\Framework\Serialize\SerializerInterface');
            $value = $this->getValue();
            unset($value['__empty']);
            $encodedValue = $serializer->serialize($value);
            $this->setValue($encodedValue);
        } else {
            $this->setValue('');
        }
    }
    protected function _afterLoad()
    {
        if (class_exists('Magento\Framework\Serialize\Serializer\Json')) {
            $serializer = $this->simiObjectManager->get('Magento\Framework\Serialize\SerializerInterface');
            $value = $this->getValue();
            if ($value) {
                $decodedValue = $serializer->unserialize($value);
                $this->setValue($decodedValue);
            }
        } else {
            $this->setValue(array());
        }
    }
}

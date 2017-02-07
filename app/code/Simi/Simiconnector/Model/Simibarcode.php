<?php

namespace Simi\Simiconnector\Model;

/**
 * Connector Model
 *
 * @method \Simi\Simiconnector\Model\Resource\Page _getResource()
 * @method \Simi\Simiconnector\Model\Resource\Page getResource()
 */
class Simibarcode extends \Magento\Framework\Model\AbstractModel
{

    /**
     * @var \Simi\Simiconnector\Helper\Website
     * */
    public $websiteHelper;
    public $simiObjectManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ResourceModel\Key $resource
     * @param ResourceModel\Key\Collection $resourceCollection
     * @param \Simi\Simiconnector\Helper\Website $websiteHelper
     * @param AppFactory $app
     * @param PluginFactory $plugin
     * @param DesignFactory $design
     * @param ResourceModel\App\CollectionFactory $appCollection
     * @param ResourceModel\Key\CollectionFactory $keyCollection
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Simi\Simiconnector\Model\ResourceModel\Simibarcode $resource,
        \Simi\Simiconnector\Model\ResourceModel\Simibarcode\Collection $resourceCollection,
        \Simi\Simiconnector\Helper\Website $websiteHelper
    ) {
        $this->websiteHelper = $websiteHelper;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection
        );
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\ResourceModel\Simibarcode');
    }

    /**
     * @return array Status
     */
    public function toOptionStatusHash()
    {
        $status = [
            '1' => __('Enable'),
            '2' => __('Disabled'),
        ];
        return $status;
    }

    /**
     * @return array Status
     */
    public function toOptionBarcodeTypeHash()
    {
        $status = [
            'code128'  => __('code128'),
            'code128a' => __('code128a'),
            'code39'   => __('code39'),
            'code25'   => __('code25'),
            'codabar'  => __('codabar')
        ];
        return $status;
    }
}

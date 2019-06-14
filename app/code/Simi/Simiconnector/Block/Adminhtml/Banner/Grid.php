<?php

namespace Simi\Simiconnector\Block\Adminhtml\Banner;

/**
 * Adminhtml Simiconnector grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Simi\Simiconnector\Model\Banner
     */
    public $bannerFactory;

    /**
     * @var \Simi\Simiconnector\Model\ResourceModel\Banner\CollectionFactory
     */
    public $collectionFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    public $moduleManager;

    /**
     * @var order model
     */
    public $resource;

    /**
     * @var \Simi\Simiconnector\Helper\Website
     * */
    public $websiteHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Simi\Simiconnector\Model\Simiconnector $simiconnectorPage
     * @param \Simi\Simiconnector\Model\ResourceModel\Simiconnector\CollectionFactory $collectionFactory
     * @param \Magento\Core\Model\PageLayout\Config\Builder $pageLayoutBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Simi\Simiconnector\Model\BannerFactory $bannerFactory,
        \Simi\Simiconnector\Model\ResourceModel\Banner\CollectionFactory $collectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Simi\Simiconnector\Helper\Website $websiteHelper,
        array $data = []
    ) {
   
        $this->collectionFactory = $collectionFactory;
        $this->moduleManager      = $moduleManager;
        $this->resource          = $resourceConnection;
        $this->bannerFactory      = $bannerFactory;
        $this->websiteHelper      = $websiteHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('bannerGrid');
        $this->setDefaultSort('banner_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    public function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    public function _prepareColumns()
    {
        $this->addColumn('simi_banner_id', [
            'header' => __('ID'),
            'index'  => 'banner_id',
        ]);

        $this->addColumn('banner_title', [
            'header' => __('Title'),
            'index'  => 'banner_title',
        ]);

        $this->addColumn('type', [
            'type'    => 'options',
            'header'  => __('Direct viewers to'),
            'index'   => 'type',
            'options' => $this->bannerFactory->create()->toOptionTypeHash(),
        ]);

        $this->addColumn('sort_order', [
            'header' => __('Sort Order'),
            'index'  => 'sort_order',
        ]);

        $this->addColumn('status', [
            'type'    => 'options',
            'header'  => __('Status'),
            'index'   => 'status',
            'options' => $this->bannerFactory->create()->toOptionStatusHash(),
        ]);

        $this->addColumn(
            'action',
            [
            'header'           => __('View'),
            'type'             => 'action',
            'getter'           => 'getId',
            'actions'          => [
                [
                    'caption' => __('Edit'),
                    'url'     => [
                        'base'   => '*/*/edit',
                        'params' => ['store' => $this->getRequest()->getParam('store')]
                    ],
                    'field'   => 'banner_id'
                ]
            ],
            'sortable'         => false,
            'filter'           => false,
            'header_css_class' => 'col-action',
            'column_css_class' => 'col-action',
                ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Row click url
     *
     * @param \Magento\Framework\Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', [
                    'banner_id' => $row->getId()
        ]);
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }

    /**
     * @return mixed
     */
    public function getWebsiteIdFromUrl()
    {
        return $this->websiteHelper->getWebsiteIdFromUrl();
    }
}

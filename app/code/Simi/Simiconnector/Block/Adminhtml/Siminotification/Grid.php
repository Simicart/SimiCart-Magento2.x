<?php

namespace Simi\Simiconnector\Block\Adminhtml\Siminotification;

/**
 * Adminhtml Connector grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var \Simi\Simiconnector\Model\Siminotification
     */
    public $siminotificationFactory;

    /**
     * @var \Simi\Simiconnector\Model\ResourceModel\Siminotification\CollectionFactory
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
     * @param \Simi\Simiconnector\Model\Simiconnector $connectorPage
     * @param \Simi\Simiconnector\Model\ResourceModel\Simiconnector\CollectionFactory $collectionFactory
     * @param \Magento\Core\Model\PageLayout\Config\Builder $pageLayoutBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Simi\Simiconnector\Model\SiminotificationFactory $siminotificationFactory,
        \Simi\Simiconnector\Model\ResourceModel\Siminotification\CollectionFactory $collectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Simi\Simiconnector\Helper\Website $websiteHelper,
        array $data = []
    ) {
   
        $this->collectionFactory       = $collectionFactory;
        $this->moduleManager            = $moduleManager;
        $this->resource                = $resourceConnection;
        $this->siminotificationFactory = $siminotificationFactory;
        $this->websiteHelper            = $websiteHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('siminotificationGrid');
        $this->setDefaultSort('notice_id');
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
        $this->addColumn('notice_id', [
            'header' => __('ID'),
            'index'  => 'notice_id',
        ]);

        $this->addColumn('notice_title', [
            'header' => __('Title'),
            'index'  => 'notice_title',
        ]);

        $this->addColumn('notice_content', [
            'header' => __('Message'),
            'index'  => 'notice_content',
        ]);

        $this->addColumn('storeview_id', [
            'type'    => 'options',
            'header'  => __('Storeview'),
            'index'   => 'storeview_id',
            'options' => $this->siminotificationFactory->create()->toOptionStoreviewHash(),
        ]);

        $this->addColumn('device_id', [
            'type'    => 'options',
            'header'  => __('Device'),
            'index'   => 'device_id',
            'options' => $this->siminotificationFactory->create()->toOptionDeviceHash(),
        ]);

        $this->addColumn('created_time', [
            'type'   => 'datetime',
            'header' => __('Created Date'),
            'index'  => 'created_time',
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
                    'field'   => 'notice_id'
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
                    'notice_id' => $row->getId()
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

<?php

namespace Simi\Simiconnector\Block\Adminhtml\Device;

/**
 * Adminhtml Connector grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var \Simi\Simiconnector\Model\Device
     */
    public $deviceFactory;

    /**
     * @var \Simi\Simiconnector\Model\ResourceModel\Device\CollectionFactory
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
        \Simi\Simiconnector\Model\DeviceFactory $deviceFactory,
        \Simi\Simiconnector\Model\ResourceModel\Device\CollectionFactory $collectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Simi\Simiconnector\Helper\Website $websiteHelper,
        array $data = []
    ) {
   
        $this->collectionFactory = $collectionFactory;
        $this->moduleManager      = $moduleManager;
        $this->resource          = $resourceConnection;
        $this->deviceFactory     = $deviceFactory;
        $this->websiteHelper      = $websiteHelper;

        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('deviceGrid');
        $this->setDefaultSort('device_id');
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
        $this->addColumn('device_id', [
            'header' => __('ID'),
            'index'  => 'device_id',
        ]);

        $this->addColumn('storeview_id', [
            'type'    => 'options',
            'header'  => __('Storeview'),
            'index'   => 'storeview_id',
            'options' => $this->deviceFactory->create()->toOptionStoreviewHash(),
        ]);

        $this->addColumn('plaform_id', [
            'type'    => 'options',
            'header'  => __('Device Type'),
            'index'   => 'plaform_id',
            'options' => $this->deviceFactory->create()->toOptionDeviceHash(),
        ]);

        $this->addColumn('city', [
            'header' => __('City'),
            'index'  => 'city',
        ]);

        $this->addColumn('state', [
            'header' => __('State/Province'),
            'index'  => 'state',
        ]);

        $this->addColumn('state', [
            'header' => __('State/Province'),
            'index'  => 'state',
        ]);

        $this->addColumn('country', [
            'type'    => 'options',
            'header'  => __('Country'),
            'index'   => 'country',
            'options' => $this->deviceFactory->create()->toOptionCountryHash(),
        ]);

        $this->addColumn('is_demo', [
            'type'    => 'options',
            'header'  => __('Is Demo'),
            'index'   => 'is_demo',
            'options' => $this->deviceFactory->create()->toOptionDemoHash(),
        ]);

        $this->addColumn('created_time', [
            'type'   => 'datetime',
            'header' => __('Created Date'),
            'index'  => 'created_time',
        ]);

        $this->addColumn('app_id', [
            'header' => __('App Id'),
            'index'  => 'app_id',
        ]);

        $this->addColumn('build_version', [
            'header' => __('Build Version'),
            'index'  => 'build_version',
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
                    'field'   => 'device_id'
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
                    'device_id' => $row->getId()
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

    public function _prepareMassaction()
    {
        $this->setMassactionIdField('device_id');
        $this->getMassactionBlock()->addItem(
            'delete',
            [
            'label'   => __('Delete'),
            'url'     => $this->getUrl('*/*/massDelete'),
            'confirm' => __('Are you sure?')
                ]
        );
        return $this;
    }
}

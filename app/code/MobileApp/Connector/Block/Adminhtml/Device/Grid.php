<?php
namespace MobileApp\Connector\Block\Adminhtml\Device;

/**
 * Adminhtml Connector grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \MobileApp\Connector\Model\Device
     */
    protected $_deviceFactory;

    /**
     * @var \MobileApp\Connector\Model\ResourceModel\Device\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var order model
     */
    protected $_resource;

    /**
     * @var \MobileApp\Connector\Helper\Website
     **/
    protected $_websiteHelper;



    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \MobileApp\Connector\Model\Connector $connectorPage
     * @param \MobileApp\Connector\Model\ResourceModel\Connector\CollectionFactory $collectionFactory
     * @param \Magento\Core\Model\PageLayout\Config\Builder $pageLayoutBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \MobileApp\Connector\Model\DeviceFactory $deviceFactory,
        \MobileApp\Connector\Model\ResourceModel\Device\CollectionFactory $collectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \MobileApp\Connector\Helper\Website $websiteHelper,

        array $data = []
    )
    {
        $this->_collectionFactory = $collectionFactory;
        $this->moduleManager = $moduleManager;
        $this->_resource = $resourceConnection;
        $this->_deviceFactory = $deviceFactory;
        $this->_websiteHelper = $websiteHelper;

        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
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
    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->create();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn('device_id', [
            'header' => __('ID'),
            'index' => 'device_id',
        ]);

        $this->addColumn('website_id', [
            'type' => 'options',
            'header' => __('Website'),
            'index' => 'website_id',
            'options' => $this->_deviceFactory->create()->toOptionWebsiteHash(),
        ]);

        $this->addColumn('plaform_id', [
            'type' => 'options',
            'header' => __('Device Type'),
            'index' => 'plaform_id',
            'options' => $this->_deviceFactory->create()->toOptionDeviceHash(),
        ]);

        $this->addColumn('city', [
            'header' => __('City'),
            'index' => 'city',
        ]);

        $this->addColumn('state', [
            'header' => __('State/Province'),
            'index' => 'state',
        ]);

        $this->addColumn('country', [
            'type' => 'options',
            'header' => __('Country'),
            'index' => 'country',
            'options' => $this->_deviceFactory->create()->toOptionCountryHash(),
        ]);

        $this->addColumn('is_demo', [
            'type' => 'options',
            'header' => __('Is Demo'),
            'index' => 'is_demo',
            'options' => $this->_deviceFactory->create()->toOptionDemoHash(),
        ]);

        $this->addColumn('created_time', [
            'type'      => 'datetime',
            'header'    => __('Created Date'),
            'index'     => 'created_time',
        ]);

        $this->addColumn(
            'action',
            [
                'header' => __('View'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => [
                    [
                        'caption' => __('Edit'),
                        'url' => [
                            'base' => '*/*/edit',
                            'params' => ['store' => $this->getRequest()->getParam('store')]
                        ],
                        'field' => 'device_id'
                    ]
                ],
                'sortable' => false,
                'filter' => false,
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

}

<?php

namespace Simi\Simiconnector\Block\Adminhtml\Siminotification\Edit\Tab;

/**
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Devicegrid extends \Magento\Backend\Block\Widget\Grid\Extended
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
    public $simiObjectManager;
    public $storeview_id;

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
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        array $data = []
    ) {
   
        $this->simiObjectManager  = $simiObjectManager;
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
        if (!$this->storeview_id && ($storeviewId = $this->getRequest()->getParam('storeview_id'))) {
            $this->storeview_id = $storeviewId;
        }
        
        $collection         = $this->collectionFactory->create();
        if ($this->storeview_id) {
            $collection->addFieldToFilter('storeview_id', $this->storeview_id);
        }
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
        $this->addColumn(
            'in_devices',
            [
            'type'             => 'checkbox',
            'html_name'        => 'devices_id',
            'required'         => true,
            'values'           => $this->_getSelectedDevices(),
            'align'            => 'center',
            'index'            => 'entity_id',
            'header_css_class' => 'col-select',
            'column_css_class' => 'col-select',
            'renderer'         => '\Simi\Simiconnector\Block\Adminhtml\Siminotification\Edit\Tab\Devicerender',
                ]
        );

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
        return false;
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->_getData(
            'grid_url'
        ) ? $this->_getData(
            'grid_url'
        ) : $this->getUrl(
            'simiconnector/*/devicegrid',
            ['_current' => true, 'notice_id' => $this->getRequest()->getParam('notice_id'),
            'storeview_id' => $this->storeview_id]
        );
    }

    /**
     * @return array
     */
    public function _getSelectedDevices()
    {
        $devices = array_keys($this->getSelectedDevices());
        return $devices;
    }

    public function setStoreview($storeviewid)
    {
        $this->storeview_id = $storeviewid;
        return $this;
    }

    /**
     * @return array
     */
    public function getSelectedDevices()
    {
        $noticeId = $this->getRequest()->getParam('notice_id');
        if (!isset($noticeId)) {
            $noticeId = 0;
        }

        $notification = $this->simiObjectManager->get('Simi\Simiconnector\Model\Siminotification')->load($noticeId);
        $devices      = [];

        if ($notification->getId()) {
            $devices = explode(',', str_replace(' ', '', $notification->getData('devices_pushed')));
        }

        $proIds = [];

        foreach ($devices as $device) {
            $proIds[$device] = ['id' => $device];
        }
        return $proIds;
    }
}

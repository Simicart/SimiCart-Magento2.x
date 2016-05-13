<?php
namespace MobileApp\Connector\Block\Adminhtml\Connector;

/**
 * Adminhtml Connector grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \MobileApp\Connector\Model\ResourceModel\App\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \MobileApp\Connector\Model\App
     */
    protected $_app;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

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
        \MobileApp\Connector\Model\App $app,
        \MobileApp\Connector\Model\ResourceModel\App\CollectionFactory $collectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \MobileApp\Connector\Helper\Website $websiteHelper,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_app = $app;
        $this->moduleManager = $moduleManager;
        $this->_websiteHelper = $websiteHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('appGrid');
        $this->setDefaultSort('app_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
        $this->setFilterVisibility(false);
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareCollection()
    {
        $webId = $this->getWebsiteIdFromUrl();
        $collection = $this->_collectionFactory->create();

        $collection->addFieldToFilter('website_id',$webId);
        $collection->addFieldToFilter('device_id', array('neq' => 2));

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
            'header'    => __('Device'),
            'index'     => 'device_id',
            'width' => '80px',
            'filter' => false,
            'sortable' => false,
            'renderer'  => '\MobileApp\Connector\Block\Adminhtml\Renderer\Connector\Grid\Device',
        ]);
        
        $this->addColumn(
            'action',
            [
                'header' => __('Edit'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => [
                    [
//                        'caption' => __('Edit'),
//                        'url' => [
//                            'base' => '*/*/edit',
//                            'params' => ['store' => $this->getRequest()->getParam('store')]
//                        ],
//                        'field' => 'app_id'
                    ]
                ],
                'sortable' => false,
                'filter' => false,
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action',
                'renderer'  => '\MobileApp\Connector\Block\Adminhtml\Renderer\Connector\Grid\Edit',
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
        $webId = $this->getWebsiteIdFromUrl();
        return $this->getUrl('*/*/edit', [
            'id' => $row->getId(),
            'store' => $this->getRequest()->getParam('store'),
            'device_id' => $row->getDeviceId(),
            'website_id' => $webId,
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
    public function getWebsiteIdFromUrl(){
        return $this->_websiteHelper->getWebsiteIdFromUrl();
    }
}

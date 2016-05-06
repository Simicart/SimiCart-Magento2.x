<?php
namespace MobileApp\Connector\Block\Adminhtml\Banner;

/**
 * Adminhtml Connector grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \MobileApp\Connector\Model\Banner
     */
    protected $_bannerFactory;

    /**
     * @var \MobileApp\Connector\Model\ResourceModel\Banner\CollectionFactory
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
        \MobileApp\Connector\Model\BannerFactory $bannerFactory,
        \MobileApp\Connector\Model\ResourceModel\Banner\CollectionFactory $collectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \MobileApp\Connector\Helper\Website $websiteHelper,
        array $data = []
    )
    {
        $this->_collectionFactory = $collectionFactory;
        $this->moduleManager = $moduleManager;
        $this->_resource = $resourceConnection;
        $this->_bannerFactory = $bannerFactory;
        $this->_websiteHelper = $websiteHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
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
    protected function _prepareCollection()
    {
        $webId = $this->getWebsiteIdFromUrl();
        $collection = $this->_collectionFactory->create();
        $collection->addFieldToFilter('website_id', $webId);

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
        $this->addColumn('banner_id', [
            'header' => __('ID'),
            'index' => 'banner_id',
        ]);

        $this->addColumn('banner_title', [
            'header' => __('Title'),
            'index' => 'banner_title',
        ]);

        $this->addColumn('type', [
            'type' => 'options',
            'header' => __('Direct viewers to'),
            'index' => 'type',
            'options' => $this->_bannerFactory->create()->toOptionTypeHash(),
        ]);

        $this->addColumn('website_id', [
            'type' => 'options',
            'header' => __('Website'),
            'index' => 'website_id',
            'options' => $this->_bannerFactory->create()->toOptionWebsiteHash(),
        ]);

        $this->addColumn('status', [
            'type' => 'options',
            'header' => __('Status'),
            'index' => 'status',
            'options' => $this->_bannerFactory->create()->toOptionStatusHash(),
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
                        'field' => 'banner_id'
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
            'id' => $row->getId()
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
        return $this->_websiteHelper->getWebsiteIdFromUrl();
    }

}

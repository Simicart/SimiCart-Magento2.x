<?php
namespace Simi\Simiconnector\Block\Adminhtml\History;

/**
 * Adminhtml Connector grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Simi\Simiconnector\Model\History
     */
    protected $_historyFactory;

    /**
     * @var \Simi\Simiconnector\Model\ResourceModel\History\CollectionFactory
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
     * @var \Simi\Simiconnector\Helper\Website
     **/
    protected $_websiteHelper;

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
        \Simi\Simiconnector\Model\HistoryFactory $historyFactory,
        \Simi\Simiconnector\Model\ResourceModel\History\CollectionFactory $collectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Simi\Simiconnector\Helper\Website $websiteHelper,
        array $data = []
    ){
        $this->_collectionFactory = $collectionFactory;
        $this->moduleManager = $moduleManager;
        $this->_resource = $resourceConnection;
        $this->_historyFactory = $historyFactory;
        $this->_websiteHelper = $websiteHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('historyGrid');
        $this->setDefaultSort('history_id');
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
        $this->addColumn('history_id', [
            'header' => __('ID'),
            'index' => 'history_id',
        ]);

        $this->addColumn('siminotification_title', [
            'header' => __('Title'),
            'index' => 'siminotification_title',
        ]);

        $this->addColumn('siminotification_content', [
            'header' => __('Message'),
            'index' => 'siminotification_content',
        ]);

        $this->addColumn('website_id', [
            'type' => 'options',
            'header' => __('Website'),
            'index' => 'website_id',
            'options' => $this->_historyFactory->create()->toOptionWebsiteHash(),
        ]);

        $this->addColumn('device_id', [
            'type' => 'options',
            'header' => __('Device'),
            'index' => 'device_id',
            'options' => $this->_historyFactory->create()->toOptionDeviceHash(),
        ]);

        $this->addColumn('siminotification_type', [
            'type' => 'options',
            'header' => __('Type'),
            'index' => 'siminotification_type',
            'options' => $this->_historyFactory->create()->toOptionTypeHash(),
        ]);

        $this->addColumn('created_time', [
            'type'      => 'datetime',
            'header'    => __('Sent Date'),
            'index'     => 'created_time',
        ]);

        $this->addColumn('status', [
            'type' => 'options',
            'header' => __('Status'),
            'index' => 'status',
            'options' => $this->_historyFactory->create()->toOptionStatusHash(),
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
                        'field' => 'history_id'
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
            'history_id' => $row->getId()
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

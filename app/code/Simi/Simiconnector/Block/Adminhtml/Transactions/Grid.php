<?php
namespace Simi\Simiconnector\Block\Adminhtml\Transactions;

/**
 * Adminhtml Connector grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    
    protected $_collectionFactory;

    
    protected $moduleManager;

    /**
     * @var order model
     */
    protected $_resource;

    /**
     * @var order status model
     */
    protected $_orderStatus;

    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Simi\Simiconnector\Model\ResourceModel\Appreport\CollectionFactory $collectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $orderStatusCollection,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->moduleManager = $moduleManager;
        $this->_resource = $resourceConnection;
        $this->_orderStatus = $orderStatusCollection;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('transactionsGrid');
        $this->setDefaultSort('transaction_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
//        $this->setFilterVisibility(false);
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->create();
        $orderGrid_table = $this->_resource->getTableName('sales_order_grid');

        $collection->join(
            array('ordergrid' => $orderGrid_table),
            'main_table.order_id = ordergrid.entity_id',
            array('*')
        );
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
        $this->addColumn('real_order_id', [
            'header'    => __('ID'),
            'index'     => 'increment_id',
        ]);

        $this->addColumn('store_id', [
            'type'      => 'store',
            'header'    => __('Purchase Point'),
            'index'     => 'store_id',
        ]);

        $this->addColumn('created_at', [
            'type'      => 'datetime',
            'header'    => __('Purchase Date'),
            'index'     => 'created_at',
        ]);

        $this->addColumn('billing_name', [
            'header'    => __('Bill-to Name'),
            'index'     => 'billing_name',
        ]);

        $this->addColumn('shipping_name', [
            'header'    => __('Ship-to Name'),
            'index'     => 'shipping_name',
        ]);

        $this->addColumn('base_grand_total', [
            'type'      => 'currency',
            'header'    => __('Grand Total (Base)'),
            'index'     => 'base_grand_total',
        ]);

        $this->addColumn('grand_total', [
            'type'      => 'currency',
            'header'    => __('Grand Total (Purchased)'),
            'index'     => 'grand_total',
        ]);

        $this->addColumn('status', [
            'type'      => 'options',
            'header'    => __('Status'),
            'index'     => 'status',
            'options'   => $this->_orderStatus->create()->toOptionHash(),
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
        return $this->getUrl('sales/order/view', [
            'order_id' => $row->getOrderId()
        ]);
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/transactions/grid', ['_current' => true]);
    }

}

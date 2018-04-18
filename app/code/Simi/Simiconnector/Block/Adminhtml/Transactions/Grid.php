<?php

namespace Simi\Simiconnector\Block\Adminhtml\Transactions;

/**
 * Adminhtml Connector grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    public $collectionFactory;
    public $moduleManager;

    /**
     * @var order model
     */
    public $resource;
    public $simiObjectManager;

    /**
     * @var order status model
     */
    public $orderStatus;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Backend\Helper\Data $backendHelper,
        \Simi\Simiconnector\Model\ResourceModel\Appreport\CollectionFactory $collectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $orderStatusCollection,
        array $data = []
    ) {
        $this->simiObjectManager    = $simiObjectManager;
        $this->collectionFactory = $collectionFactory;
        $this->moduleManager      = $moduleManager;
        $this->resource          = $resourceConnection;
        $this->orderStatus       = $orderStatusCollection;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('transactionsGrid');
        $this->setDefaultSort('transaction_id');
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
        $collection      = $this->simiObjectManager->create('Simi\Simiconnector\Model\Appreport')
                ->getCollection()->getGridCollection($this->simiObjectManager);
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
        $this->addColumn('real_order_id', [
            'header' => __('ID'),
            'index'  => 'increment_id',
        ]);

        $this->addColumn('platform', [
            'type'    => 'options',
            'header' => __('Platform'),
            'index'  => 'platform',
            'options' => [
                '0' => __('Native App'),
                '1' => __('PWA')
            ]
        ]);
        

        $this->addColumn('store_id', [
            'type'   => 'store',
            'header' => __('Purchase Point'),
            'index'  => 'store_id',
        ]);

        $this->addColumn('created_at', [
            'type'   => 'datetime',
            'header' => __('Purchase Date'),
            'index'  => 'created_at',
        ]);

        $this->addColumn('billing_name', [
            'header' => __('Bill-to Name'),
            'index'  => 'billing_name',
        ]);

        $this->addColumn('shipping_name', [
            'header' => __('Ship-to Name'),
            'index'  => 'shipping_name',
        ]);

        $this->addColumn('base_grand_total', [
            'type'   => 'currency',
            'header' => __('Grand Total (Base)'),
            'index'  => 'base_grand_total',
        ]);

        $this->addColumn('grand_total', [
            'type'   => 'currency',
            'header' => __('Grand Total (Purchased)'),
            'index'  => 'grand_total',
        ]);

        $this->addColumn('status', [
            'type'    => 'options',
            'header'  => __('Status'),
            'index'   => 'status',
            'options' => $this->orderStatus->create()->toOptionHash(),
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

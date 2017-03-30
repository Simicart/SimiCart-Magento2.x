<?php

namespace Simi\Simiconnector\Block\Adminhtml\Simibarcode;

/**
 * Adminhtml Connector grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var \Simi\Simiconnector\Model\Simibarcode
     */
    public $barcodeFactory;

    /**
     * @var \Simi\Simiconnector\Model\ResourceModel\Simibarcode\CollectionFactory
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
        \Simi\Simiconnector\Model\SimibarcodeFactory $simibarcodeFactory,
        \Simi\Simiconnector\Model\ResourceModel\Simibarcode\CollectionFactory $collectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Simi\Simiconnector\Helper\Website $websiteHelper,
        array $data = []
    ) {
   
        $this->collectionFactory = $collectionFactory;
        $this->moduleManager      = $moduleManager;
        $this->resource          = $resourceConnection;
        $this->barcodeFactory    = $simibarcodeFactory;
        $this->websiteHelper      = $websiteHelper;

        parent::__construct($context, $backendHelper, $data);
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
        $this->addColumn('barcode_id', [
            'header' => __('ID'),
            'align'  => 'right',
            'width'  => '50px',
            'index'  => 'barcode_id',
        ]);

        $this->addColumn('barcode', [
            'header' => __('Barcode'),
            'align'  => 'left',
            'index'  => 'barcode'
        ]);

        $this->addColumn('qrcode', [
            'header' => __('QRcode'),
            'align'  => 'left',
            'index'  => 'qrcode'
        ]);

        $this->addColumn('product_sku', [
            'header' => __('Product SKU'),
            'align'  => 'left',
            'index'  => 'product_sku'
        ]);

        $this->addColumn('created_date', [
            'header' => __('Created Date'),
            'align'  => 'left',
            'type'   => 'datetime',
            'index'  => 'created_date'
        ]);

        $this->addColumn('barcode_status', [
            'type'    => 'options',
            'header'  => __('Status'),
            'index'   => 'barcode_status',
            'options' => $this->barcodeFactory->create()->toOptionStatusHash(),
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
                    'field'   => 'barcode_id'
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
                    'barcode_id' => $row->getId()
        ]);
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/index', ['_current' => true]);
    }

    public function _prepareMassaction()
    {
        $this->setMassactionIdField('barcode_id');
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

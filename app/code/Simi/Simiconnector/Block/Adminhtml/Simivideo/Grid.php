<?php

namespace Simi\Simiconnector\Block\Adminhtml\Simivideo;

/**
 * Adminhtml Connector grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var \Simi\Simiconnector\Model\Simivideo
     */
    public $videoFactory;

    /**
     * @var \Simi\Simiconnector\Model\ResourceModel\Simivideo\CollectionFactory
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
        \Simi\Simiconnector\Model\SimivideoFactory $simivideoFactory,
        \Simi\Simiconnector\Model\ResourceModel\Simivideo\CollectionFactory $collectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Simi\Simiconnector\Helper\Website $websiteHelper,
        array $data = []
    ) {
   
        $this->collectionFactory = $collectionFactory;
        $this->moduleManager      = $moduleManager;
        $this->resource          = $resourceConnection;
        $this->videoFactory      = $simivideoFactory;
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
        $this->addColumn('video_id', [
            'header' => __('ID'),
            'align'  => 'right',
            'width'  => '50px',
            'index'  => 'video_id',
        ]);

        $this->addColumn('video_title', [
            'header' => __('Title'),
            'align'  => 'left',
            'index'  => 'video_title'
        ]);

        $this->addColumn('video_url', [
            'header' => __('Video Key'),
            'align'  => 'left',
            'index'  => 'video_url'
        ]);

        $this->addColumn('status', [
            'type'    => 'options',
            'header'  => __('Status'),
            'index'   => 'status',
            'options' => $this->videoFactory->create()->toOptionStatusHash(),
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
        return $this->getUrl('*/*/edit', [
                    'video_id' => $row->getId()
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
        $this->setMassactionIdField('video_id');
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

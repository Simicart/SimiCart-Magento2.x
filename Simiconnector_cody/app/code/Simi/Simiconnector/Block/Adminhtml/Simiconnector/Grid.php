<?php
namespace Simi\Simisimiconnector\Block\Adminhtml\Simisimiconnector;

/**
 * Adminhtml Simisimiconnector grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Simi\Simisimiconnector\Model\ResourceModel\Simisimiconnector\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Simi\Simisimiconnector\Model\Simisimiconnector
     */
    protected $_simiconnector;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Simi\Simisimiconnector\Model\Simisimiconnector $simiconnectorPage
     * @param \Simi\Simisimiconnector\Model\ResourceModel\Simisimiconnector\CollectionFactory $collectionFactory
     * @param \Magento\Core\Model\PageLayout\Config\Builder $pageLayoutBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Simi\Simisimiconnector\Model\Simisimiconnector $simiconnector,
        \Simi\Simisimiconnector\Model\ResourceModel\Simisimiconnector\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_simiconnector = $simiconnector;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('simiconnectorGrid');
        $this->setDefaultSort('simiconnector_id');
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
        /* @var $collection \Simi\Simisimiconnector\Model\ResourceModel\Simisimiconnector\Collection */
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
        $this->addColumn('simiconnector_id', [
            'header'    => __('ID'),
            'index'     => 'simiconnector_id',
        ]);
        
        $this->addColumn('title', ['header' => __('Title'), 'index' => 'title']);
        $this->addColumn('author', ['header' => __('Author'), 'index' => 'author']);
        
        $this->addColumn(
            'published_at',
            [
                'header' => __('Published On'),
                'index' => 'published_at',
                'type' => 'date',
                'header_css_class' => 'col-date',
                'column_css_class' => 'col-date'
            ]
        );
        
        $this->addColumn(
            'created_at',
            [
                'header' => __('Created'),
                'index' => 'created_at',
                'type' => 'datetime',
                'header_css_class' => 'col-date',
                'column_css_class' => 'col-date'
            ]
        );
        
        $this->addColumn(
            'action',
            [
                'header' => __('Edit'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => [
                    [
                        'caption' => __('Edit'),
                        'url' => [
                            'base' => '*/*/edit',
                            'params' => ['store' => $this->getRequest()->getParam('store')]
                        ],
                        'field' => 'simiconnector_id'
                    ]
                ],
                'sortable' => false,
                'filter' => false,
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action'
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
        return $this->getUrl('*/*/edit', ['simiconnector_id' => $row->getId()]);
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

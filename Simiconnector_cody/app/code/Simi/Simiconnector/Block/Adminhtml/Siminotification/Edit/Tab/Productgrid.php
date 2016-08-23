<?php
namespace Simi\Simiconnector\Block\Adminhtml\Siminotification\Edit\Tab;

/**
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Productgrid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Registry|null
     */
    protected
    $_coreRegistry = null;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected
    $_productFactory;

    /**
     * @var \Simi\Simiconnector\Model\Banner
     */
    protected
    $_siminotificationFactory = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public
    function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Simi\Simiconnector\Model\BannerFactory $siminotificationFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    )
    {

        $this->_productFactory = $productFactory;
        $this->_siminotificationFactory = $siminotificationFactory;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * init construct
     */
    protected
    function _construct()
    {
        parent::_construct();
        $this->setId('product_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);

    }

    /**
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected
    function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in product flag
        if ($column->getId() == 'in_products') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
            } else {
                if ($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $productIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected
    function _prepareCollection()
    {
        $collection = $this->_productFactory->create()->getCollection()
                                            ->addAttributeToSelect('entity_id')
                                            ->addAttributeToSelect('name')
                                            ->addAttributeToSelect('sku')
                        ;
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected
    function _prepareColumns()
    {

        $this->addColumn(
            'in_products',
            [
                'type' => 'radio',
                'html_name' => 'products_id',
                'required' => true,
                'values' => $this->_getSelectedProducts(),
                'align' => 'center',
                'index' => 'entity_id',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );

        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'index' => 'entity_id',
                'width' => '20px',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );


        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );


        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'index' => 'sku',
                'header_css_class' => 'col-sku',
                'column_css_class' => 'col-sku'
            ]
        );


        return parent::_prepareColumns();
    }

    /**
     * @return mixed|string
     */
    public
    function getGridUrl()
    {
        return $this->_getData(
            'grid_url'
        ) ? $this->_getData(
            'grid_url'
        ) : $this->getUrl(
            'simiconnector/*/productgrid',
            ['_current' => true]
        );
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $row
     * @return string
     */
    public
    function getRowUrl($row)
    {
        return false;
    }

    /**
     * @return array
     */
    protected
    function _getSelectedProducts()
    {
        $products = array_keys($this->getSelectedProducts());
        return $products;
    }

    /**
     * @return array
     */
    public
    function getSelectedProducts()
    {
        $siminotification_id = $this->getRequest()->getParam('id');
        if (!isset($tm_id)) {
            $siminotification_id = 0;
        }

        $siminotification = $this->_siminotificationFactory->create()->load($siminotification_id);
        $products = array();
        if($siminotification->getId()){
            $products = array($siminotification->getProductId());
        }

        $proIds = array();

        foreach ($products as $product) {
            $proIds[$product] = array('id' => $product);
        }

        return $proIds;
    }


}
<?php


namespace Simi\Simiconnector\Helper;

class Productlist extends Data
{
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    
    protected $_resourceFactory;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\File\Size $fileSize,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\Filesystem\Io\File $ioFile,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reports\Model\ResourceModel\Report\Collection\Factory $resourceFactory,
        \Magento\Framework\Image\Factory $imageFactory,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_scopeConfig = $scopeConfig;
        $this->filesystem = $filesystem;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->httpFactory = $httpFactory;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_ioFile = $ioFile;
        $this->_storeManager = $storeManager;
        $this->_imageFactory = $imageFactory;
        $this->_resource = $resource;
        $this->_resourceFactory = $resourceFactory;
        parent::__construct($context);
    }
    
    public function getListTypeId() {
        return array(
            1 => __('Custom Product List'),
            2 => __('Best Seller'),
            3 => __('Most View'),
            4 => __('Newly Updated'),
            5 => __('Recently Added')
        );
    }

    public function getTypeOption() {
        return array(
            array('value' => 1, 'label' => __('Custom Product List')),
            array('value' => 2, 'label' => __('Best Seller')),
            array('value' => 3, 'label' => __('Most View')),
            array('value' => 4, 'label' => __('Newly Updated')),
            array('value' => 5, 'label' => __('Recently Added')),
        );
    }
    
    public function getProductCollection($listModel) {
        $collection = $this->_objectManager->get('Magento\Catalog\Model\Product')->getCollection()
                ->addAttributeToSelect($this->_objectManager->get('Magento\Catalog\Model\Config')
                        ->getProductAttributes())
                ->addMinimalPrice()
                ->addFinalPrice()
                ->addTaxPercents()
                ->addUrlRewrite();
        switch ($listModel->getData('list_type')) {
            //Product List
            case 1:
                $collection->addFieldToFilter('entity_id', array('in' => explode(',', $listModel->getData('list_products'))));
                break;
            //Best seller
            case 2:
                $collection = $this->_objectManager->get('Magento\Reports\Model\ResourceModel\Product\Sold\Collection');
                $resourceCollection = $this->_resourceFactory->create('Magento\Sales\Model\ResourceModel\Report\Bestsellers\Collection');
		
                die;
		$resourceCollection = $resourceF->create('Magento\Sales\Model\ResourceModel\Report\Bestsellers\Collection');
                
                \zend_debug::dump($resourceCollection->getData());die;
                    $collection = $this->_objectManager->get('Magento\Catalog\Model\Product')->getCollection()
                            ->addAttributeToSelect('ordered_qty');
                    //->addOrderedQty();
                \zend_debug::dump($collection->getData());die;
                die;
                        //->addAttributeToSelect($this->_objectManager->get('Magento\Catalog\Model\Config')
                           // ->getProductAttributes())
                       // ->addOrderedQty()->addMinimalPrice()
                         //  ->addTaxPercents()
                      //  ->addStoreFilter()
                      //  ->setOrder('ordered_qty', 'desc');
                break;
            //Most Viewed
            case 3:
                $collection = Mage::getResourceModel('reports/product_collection')
                        ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
                        ->addViewsCount()
                        ->addMinimalPrice()
                        ->addTaxPercents()
                        ->addStoreFilter();
                break;
            //New Updated
            case 4:
                $collection->setOrder('updated_at', 'desc');
                break;
            //Recently Added
            case 5:
                $collection->setOrder('created_at', 'desc');
                break;
            default:
                break;
        }
        return $collection;
    }

    
    /*
     * Matrix Helper Functions
     */
    public function getMatrixRowOptions() {
        //return $this->getListTypeId();
        $rows = array();
        $highestRow = 0;
        foreach ($this->_objectManager->get('Simi\Simiconnector\Model\Simicategory')->getCollection() as $simicat) {
            $currentIndex = $simicat->getData('matrix_row');
            if (!isset($rows[$currentIndex]))
                $rows[$currentIndex] = array();
            if ($currentIndex >= $highestRow)
                $highestRow = $currentIndex + 1;
            $rows[$currentIndex][] = $simicat->getData('simicategory_name');
        }
        foreach ($this->_objectManager->get('Simi\Simiconnector\Model\Productlist')->getCollection() as $productlist) {
            $currentIndex = $productlist->getData('matrix_row');
            if (!isset($rows[$currentIndex]))
                $rows[$currentIndex] = array();
            if ($currentIndex >= $highestRow)
                $highestRow = $currentIndex + 1;
            $rows[$currentIndex][] = $productlist->getData('list_title');
        }
        ksort($rows);
        $returnArray = array($highestRow => _('Create New Row'));
        foreach ($rows as $index => $row)
            $returnArray[$index] =  __('Row No. ') . $index . ' - ' . implode(',', $row);
        return $returnArray;
    }
    
    
    public function getMatrixLayoutMockup($storeviewid, $controller) {
        $rows = array();
        $typeID = $this->_objectManager->get('Simi\Simiconnector\Helper\Data')->getVisibilityTypeId('homecategory');
        $visibilityTable = $this->_resource->getTableName('simiconnector_visibility');
        
        $simicategoryCollection = $this->_objectManager->get('Simi\Simiconnector\Model\Simicategory')->getCollection();
        $simicategoryCollection->getSelect()
                ->join(array('visibility' => $visibilityTable), 'visibility.item_id = main_table.simicategory_id AND visibility.content_type = ' . $typeID . ' AND visibility.store_view_id =' . $storeviewid);
        $this->builderQuery = $simicategoryCollection;
        foreach ($simicategoryCollection as $simicat) {
            if (!isset($rows[$simicat->getData('matrix_row')]))
                $rows[(int) $simicat->getData('matrix_row')] = array();

            $editUrl = $controller->getUrl('*/*/simicategory/edit', array('productlist_id' => $simicat->getId()));
            $title = '<a href="' . $editUrl . '" style="background-color:rgba(255,255,255,0.7); text-decoration:none; text-transform: uppercase; color: black">' . $simicat->getData('simicategory_name') . '</a>';

            $rows[(int) $simicat->getData('matrix_row')][] = array(
                'id' => $simicat->getId(),
                'image' => $simicat->getData('simicategory_filename'),
                'image_tablet' => $simicat->getData('simicategory_filename_tablet'),
                'matrix_width_percent' => $simicat->getData('matrix_width_percent'),
                'matrix_height_percent' => $simicat->getData('matrix_height_percent'),
                'matrix_width_percent_tablet' => $simicat->getData('matrix_width_percent_tablet'),
                'matrix_height_percent_tablet' => $simicat->getData('matrix_height_percent_tablet'),
                'title' => $title,
            );
        }

        $listtypeID = $this->_objectManager->get('Simi\Simiconnector\Helper\Data')->getVisibilityTypeId('productlist');
        $listCollection = $this->_objectManager->get('Simi\Simiconnector\Model\Productlist')->getCollection();
        $listCollection->getSelect()
                ->join(array('visibility' => $visibilityTable), 'visibility.item_id = main_table.productlist_id AND visibility.content_type = ' . $listtypeID . ' AND visibility.store_view_id =' . $storeviewid);

        foreach ($listCollection as $productlist) {
            if (!isset($rows[$productlist->getData('matrix_row')]))
                $rows[(int) $productlist->getData('matrix_row')] = array();

            $editUrl = $controller->getUrl('*/*/edit', array('productlist_id' => $productlist->getId()));
            $title = '<a href="' . $editUrl . '" style="background-color:rgba(255,255,255,0.7); text-decoration:none; text-transform: uppercase; color: black">' . $productlist->getData('list_title') . '  </a>';
            $rows[(int) $productlist->getData('matrix_row')][] = array(
                'id' => $productlist->getId(),
                'image' => $productlist->getData('list_image'),
                'image_tablet' => $productlist->getData('list_image_tablet'),
                'matrix_width_percent' => $productlist->getData('matrix_width_percent'),
                'matrix_height_percent' => $productlist->getData('matrix_height_percent'),
                'matrix_width_percent_tablet' => $productlist->getData('matrix_width_percent_tablet'),
                'matrix_height_percent_tablet' => $productlist->getData('matrix_height_percent_tablet'),
                'title' => $title,
            );
        }
        ksort($rows);

        $html = '</br> <b> Matrix Theme Mockup Preview: </b></br>(Save Item to update your Changes)</br></br>';
        $html.= 'Phone Screen Mockup Preview: </br>';
        $html.= $this->drawMatrixMockupTable(170, 320, false, $rows, $storeviewid);
        $html.= '</br>Tablet Screen Mockup Preview: </br>';
        $html.= $this->drawMatrixMockupTable(178, 512, true, $rows, $storeviewid).'</table>';
        return $html;
    }

    public function drawMatrixMockupTable($bannerHeight, $bannerWidth, $is_tablet, $rows, $storeviewid) {
        if (!$is_tablet) {
            $margin = 8;
            $screenHeight = 568;
            $topmargin = 30;
            $bottommargin = 70;
        } else {
            $margin = 25;
            $screenHeight = 384;
            $topmargin = 10;
            $bottommargin = 50;
        }
        //phone shape
        $html = '<div style="background-color:black; width:' . ($bannerWidth + $margin * 2) . 'px; height:' . ($screenHeight + $topmargin + $bottommargin) . 'px; border-radius: 30px;"><br>';
        //screen
        $html.= '<div style="background-color:white; width:' . $bannerWidth . 'px;margin :' . $margin . 'px; height:' . $screenHeight . 'px ;margin-top: ' . $topmargin . 'px ; overflow-y:scroll; overflow-x:hidden;">';
        //logo (navigation)
        $html .= '<span style="color:white ; font-size: 18px; line-height: 35px; margin: 0 0 24px;"> <div> <div style= "background-color:#FF6347; width:' . $bannerWidth . '; height:' . ($bannerHeight / 6) . 'px ; text-align:center; background-image:url(https://www.simicart.com/skin/frontend/default/simicart2.0/images/menu.jpg); background-repeat:no-repeat;background-size: ' . ($bannerHeight / 6) . 'px ' . ($bannerHeight / 6) . 'px; " ><b>APPLICATION LOGO</b></div></div>';
        //banner
        $html .= '<div style="background-color:#cccccc; height:' . $bannerHeight . 'px; width:' . $bannerWidth . 'px;"><br><br><b>BANNER AREA</b></div>';
        //categories and product lists
        foreach ($rows as $row) {
            $totalWidth = 0;
            $cells = '';
            foreach ($row as $rowItem) {

                if ($is_tablet) {
                    if ($rowItem['image_tablet'] != null)
                        $rowItem['image'] = $rowItem['image_tablet'];
                    if ($rowItem['matrix_width_percent_tablet'] != null)
                        $rowItem['matrix_width_percent'] = $rowItem['matrix_width_percent_tablet'];
                    if ($rowItem['matrix_height_percent_tablet'] != null)
                        $rowItem['matrix_height_percent'] = $rowItem['matrix_height_percent_tablet'];
                }
                $rowItem['image'] = $this->getImageUrl($rowItem['image'], $storeviewid);

                $rowWidth = $rowItem['matrix_width_percent'] * $bannerWidth / 100;
                $rowHeight = $rowItem['matrix_height_percent'] * $bannerWidth / 100;
                $totalWidth += $rowWidth;

                $cells .= '<span style="display:inline-block;  width:' . $rowWidth . 'px; height: ' . $rowHeight . 'px;
                overflow:hidden; background-image:url(' . $rowItem['image'] . '); background-repeat:no-repeat;
                background-size: ' . $rowWidth . 'px ' . $rowHeight . 'px;">' . $rowItem['title'] . '</span>';
            }
            if ($totalWidth > $rowWidth)
                $style = 'overflow-x: scroll; overflow-y: hidden;';
            else
                $style = 'overflow: hidden;';
            $html.= '<div style="' . $style . 'width: ' . $bannerWidth . 'px"> <div style="width:' . $totalWidth . 'px; height:' . $rowHeight . 'px">' . $cells;
            $html.= '</div></div>';
        }
        $html.='</span></div></div>';
        return $html;
    }
    
    
    public function autoFillMatrixRowHeight() {
        $rows = array();
        foreach ($this->_objectManager->get('Simi\Simiconnector\Model\Simicategory')->getCollection() as $simicat) {
            $currentIndex = $simicat->getData('matrix_row');
            if (!isset($rows[$currentIndex]))
                $rows[$currentIndex] = array('phone' => $simicat->getData('matrix_height_percent'), 'tablet' => $simicat->getData('matrix_height_percent_tablet'));
        }
        foreach ($this->_objectManager->get('Simi\Simiconnector\Model\Productlist')->getCollection() as $productlist) {
            $currentIndex = $productlist->getData('matrix_row');
            if (!isset($rows[$currentIndex]))
                $rows[$currentIndex] = array('phone' => $productlist->getData('matrix_height_percent'), 'tablet' => $productlist->getData('matrix_height_percent_tablet'));
        }
        ksort($rows);
        $script = '
            function autoFillHeight(row){
                var returnValue = 100;
                switch(row) {';
        foreach ($rows as $index => $row) {
            $script .= '  case "' . $index . '":
                        $("matrix_height_percent").value = "' . $row['phone'] . '";
                        $("matrix_height_percent_tablet").value = "' . $row['tablet'] . '";
                        break; ';
        }
        $script .= '}}
        ';
        return $script;
    }
    /**
     * @return string
     */
    public function getImageUrl($media_path, $storeviewid)
    { 
        return $this->_objectManager->get('\Magento\Store\Model\Store')->load($storeviewid)->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ).$media_path;
    }
    
}

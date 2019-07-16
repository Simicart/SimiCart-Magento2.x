<?php

namespace Simi\Simiconnector\Helper;

class Productlist extends Data
{

    public function getListTypeId()
    {
        return [
            1 => __('Custom Product List'),
            2 => __('Best Seller'),
            3 => __('Most View'),
            4 => __('Newly Updated'),
            5 => __('Recently Added'),
            6 => __('Category Products'),
        ];
    }

    public function getTypeOption()
    {
        return [
            ['value' => 1, 'label' => __('Custom Product List')],
            ['value' => 2, 'label' => __('Best Seller')],
            ['value' => 3, 'label' => __('Most View')],
            ['value' => 4, 'label' => __('Newly Updated')],
            ['value' => 5, 'label' => __('Recently Added')],
            ['value' => 6, 'label' => __('Category Products')],
        ];
    }

    public function getProductCollection($listModel)
    {
        $collection = $this->simiObjectManager
                ->create('Simi\Simiconnector\Model\ResourceModel\Productlist\ProductlistCollection')
                ->getProductCollection($listModel, $this->simiObjectManager);
        return $collection;
    }

    /*
     * Matrix Helper Functions
     */

    public function getMatrixRowOptions()
    {
        $rows       = [];
        $highestRow = 0;
        foreach ($this->simiObjectManager->get('Simi\Simiconnector\Model\Simicategory')->getCollection() as $simicat) {
            $currentIndex = $simicat->getData('matrix_row');
            if (!isset($rows[$currentIndex])) {
                $rows[$currentIndex] = [];
            }
            if ($currentIndex >= $highestRow) {
                $highestRow = $currentIndex + 1;
            }
            $rows[$currentIndex][] = $simicat->getData('simicategory_name');
        }
        foreach ($this->simiObjectManager
                ->get('Simi\Simiconnector\Model\Productlist')->getCollection() as $productlist) {
            $currentIndex = $productlist->getData('matrix_row');
            if (!isset($rows[$currentIndex])) {
                $rows[$currentIndex] = [];
            }
            if ($currentIndex >= $highestRow) {
                $highestRow = $currentIndex + 1;
            }
            $rows[$currentIndex][] = $productlist->getData('list_title');
        }
        ksort($rows);
        $returnArray = [$highestRow => __('Create New Row')];
        foreach ($rows as $index => $row) {
            $returnArray[$index] = __('Row No. ') . $index . ' - ' . implode(',', $row);
        }
        return $returnArray;
    }

    public function getMatrixLayoutMockup($storeviewid, $controller)
    {
        $rows            = [];
        $typeID          = $this->simiObjectManager
                ->get('Simi\Simiconnector\Helper\Data')->getVisibilityTypeId('homecategory');
        $visibilityTable = $this->resource->getTableName('simiconnector_visibility');

        $simicategoryCollection = $this->simiObjectManager
                ->get('Simi\Simiconnector\Model\Simicategory')
                ->getCollection()->setOrder('sort_order', 'desc')->addFieldToFilter('status', '1')
                ->applyAPICollectionFilter($visibilityTable, $typeID, $storeviewid);
        
        $this->builderQuery     = $simicategoryCollection;
        foreach ($simicategoryCollection as $simicat) {
            if (!isset($rows[$simicat->getData('matrix_row')])) {
                $rows[(int) $simicat->getData('matrix_row')] = [];
            }

            $editUrl = $controller->getUrl('*/simicategory/edit', ['simicategory_id' => $simicat->getId()]);
            $title   = '<a href="' . $editUrl
                    . '" style="background-color:rgba(255,255,255,0.7); text-decoration:none; '
                    . 'text-transform: uppercase; color: black">' . $simicat->getData('simicategory_name') . '</a>';

            $rows[(int) $simicat->getData('matrix_row')][] = [
                'id'                           => $simicat->getId(),
                'image'                        => $simicat->getData('simicategory_filename'),
                'image_tablet'                 => $simicat->getData('simicategory_filename_tablet'),
                'matrix_width_percent'         => $simicat->getData('matrix_width_percent'),
                'matrix_height_percent'        => $simicat->getData('matrix_height_percent'),
                'matrix_width_percent_tablet'  => $simicat->getData('matrix_width_percent_tablet'),
                'matrix_height_percent_tablet' => $simicat->getData('matrix_height_percent_tablet'),
                'title'                        => $title,
                'sort_order'                   => $simicat->getData('sort_order')
            ];
        }

        $listtypeID     = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')
                ->getVisibilityTypeId('productlist');
        $listCollection = $this->simiObjectManager->get('Simi\Simiconnector\Model\Productlist')
                ->getCollection()->setOrder('sort_order', 'desc')->addFieldToFilter('list_status', '1')
                ->applyAPICollectionFilter($visibilityTable, $listtypeID, $storeviewid);

        foreach ($listCollection as $productlist) {
            if (!isset($rows[$productlist->getData('matrix_row')])) {
                $rows[(int) $productlist->getData('matrix_row')] = [];
            }

            $editUrl = $controller->getUrl('*/*/edit', ['productlist_id' => $productlist->getId()]);
            $title = '<a href="' . $editUrl . '" style="background-color:rgba(255,255,255,0.7); '
                    . 'text-decoration:none; text-transform: uppercase; color: black">'
                    . $productlist->getData('list_title') . '  </a>';
            $rows[(int) $productlist->getData('matrix_row')][] = [
                'id'                           => $productlist->getId(),
                'image'                        => $productlist->getData('list_image'),
                'image_tablet'                 => $productlist->getData('list_image_tablet'),
                'matrix_width_percent'         => $productlist->getData('matrix_width_percent'),
                'matrix_height_percent'        => $productlist->getData('matrix_height_percent'),
                'matrix_width_percent_tablet'  => $productlist->getData('matrix_width_percent_tablet'),
                'matrix_height_percent_tablet' => $productlist->getData('matrix_height_percent_tablet'),
                'title'                        => $title,
                'sort_order'                   => $productlist->getData('sort_order')
            ];
        }
        ksort($rows);
        foreach ($rows as $index => $row) {
            usort($row, function ($a, $b) {
                return $a['sort_order'] - $b['sort_order'];
            });
            $rows[$index] = $row;
        }

        $html = '</br> <b> Matrix Theme Mockup Preview: </b></br>(Save Item to update your Changes)</br></br>';
        $html.= 'Phone Screen Mockup Preview: </br>';
        $html.= $this->drawMatrixMockupTable(170, 320, false, $rows, $storeviewid);
        $html.= '</br>Tablet Screen Mockup Preview: </br>';
        $html.= $this->drawMatrixMockupTable(178, 512, true, $rows, $storeviewid) . '</table>';
        return $html;
    }

    public function drawMatrixMockupTable($bannerHeight, $bannerWidth, $is_tablet, $rows, $storeviewid)
    {
        if (!$is_tablet) {
            $margin       = 8;
            $screenHeight = 568;
            $topmargin    = 30;
            $bottommargin = 70;
        } else {
            $margin       = 25;
            $screenHeight = 384;
            $topmargin    = 10;
            $bottommargin = 50;
        }
        //phone shape
        $html = '<div style="background-color:black; width:' . ($bannerWidth + $margin * 2)
                . 'px; height:' . ($screenHeight + $topmargin + $bottommargin) . 'px; border-radius: 30px;"><br>';
        //screen
        $html.= '<div style="background-color:white; width:' . $bannerWidth
                . 'px;margin :' . $margin . 'px; height:' . $screenHeight . 'px ;margin-top: '
                . $topmargin . 'px ; overflow-y:scroll; overflow-x:hidden;">';
        //logo - navigationbar
        $html .= '<span style="color:white ; font-size: 18px; line-height: 35px; margin: 0 0 24px;"> '
                . '<div> <div style= "background-color:#FF6347; width:' . $bannerWidth . '; height:'
                . ($bannerHeight / 6)
                . 'px ; text-align:center; '
                . 'background-image:url(https://www.simicart.com/skin/frontend/default/simicart2.0/images/menu.jpg); '
                . 'background-repeat:no-repeat;background-size: ' . ($bannerHeight / 6) . 'px '
                . ($bannerHeight / 6) . 'px; " ><b>APPLICATION LOGO</b></div></div>';
        //banner
        $html .= '<div style="background-color:#cccccc; height:'
                . $bannerHeight . 'px; width:' . $bannerWidth . 'px;"><br><br><b>BANNER AREA</b></div>';
        //categories and product lists
        foreach ($rows as $row) {
            $totalWidth = 0;
            $cells      = '';
            foreach ($row as $rowItem) {
                if ($is_tablet) {
                    if ($rowItem['image_tablet'] != null) {
                        $rowItem['image'] = $rowItem['image_tablet'];
                    }
                    if ($rowItem['matrix_width_percent_tablet'] != null) {
                        $rowItem['matrix_width_percent'] = $rowItem['matrix_width_percent_tablet'];
                    }
                    if ($rowItem['matrix_height_percent_tablet'] != null) {
                        $rowItem['matrix_height_percent'] = $rowItem['matrix_height_percent_tablet'];
                    }
                }
                $rowItem['image'] = $this->getImageUrl($rowItem['image'], $storeviewid);

                $rowWidth  = $rowItem['matrix_width_percent'] * $bannerWidth / 100;
                $rowHeight = $rowItem['matrix_height_percent'] * $bannerWidth / 100;
                $totalWidth += $rowWidth;

                $cells .= '<span style="display:inline-block;  width:' . $rowWidth . 'px; height: ' . $rowHeight . 'px;
                overflow:hidden; background-image:url(' . $rowItem['image'] . '); background-repeat:no-repeat;
                background-size: ' . $rowWidth . 'px ' . $rowHeight . 'px;">' . $rowItem['title'] . '</span>';
            }
            if ($totalWidth > $rowWidth) {
                $style = 'overflow-x: scroll; overflow-y: hidden;';
            } else {
                $style = 'overflow: hidden;';
            }
            $html.= '<div style="' . $style . 'width: ' . $bannerWidth . 'px"> <div style="width:'
                    . $totalWidth . 'px; height:' . $rowHeight . 'px">' . $cells;
            $html.= '</div></div>';
        }
        $html.='</span></div></div>';
        return $html;
    }

    public function autoFillMatrixRowHeight()
    {
        $rows = [];
        foreach ($this->simiObjectManager->get('Simi\Simiconnector\Model\Simicategory')->getCollection() as $simicat) {
            $currentIndex = $simicat->getData('matrix_row');
            if (!isset($rows[$currentIndex])) {
                $rows[$currentIndex] = ['phone' => $simicat->getData('matrix_height_percent'),
                    'tablet' => $simicat->getData('matrix_height_percent_tablet')];
            }
        }
        foreach ($this->simiObjectManager->get('Simi\Simiconnector\Model\Productlist')
                ->getCollection() as $productlist) {
            $currentIndex = $productlist->getData('matrix_row');
            if (!isset($rows[$currentIndex])) {
                $rows[$currentIndex] = ['phone' => $productlist->getData('matrix_height_percent'),
                    'tablet' => $productlist->getData('matrix_height_percent_tablet')];
            }
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
        return $this->simiObjectManager->get('\Magento\Store\Model\Store')->load($storeviewid)->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . $media_path;
    }
}

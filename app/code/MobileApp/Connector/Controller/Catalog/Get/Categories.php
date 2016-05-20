<?php

namespace MobileApp\Connector\Controller\Catalog\Get;

class Categories extends \MobileApp\Connector\Controller\Connector
{
    const ROOT_CATEGORY = 2;
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Catalog\Api\CategoryManagementInterface';
        $this->_serviceMethodName = 'getTree';

        $params = $this->_getParams();

        $categoryId = isset($params['category_id'])?$params['category_id']:self::ROOT_CATEGORY;
        $this->_params = ['category_id' => $categoryId];

        return parent::execute();
    }

    /*
     * Format data
     *
     * @var $data array
     * @return array
     */
    protected function _formatData($data){
        $categories = [];
        foreach($data['children_data'] as $category){
            if($category['is_active'] == 'true')
                $categories[] = [
                    'category_id' => $category['id'],
                    'category_name' => $category['name'],
                    'category_image' => null,
                    'has_child'     => empty($category['children_data'])?'NO':'YES',
                ];
        }

        return ['data' => $categories, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}

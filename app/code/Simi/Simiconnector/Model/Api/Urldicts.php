<?php

namespace Simi\Simiconnector\Model\Api;

class Urldicts extends Apiabstract
{
    public $params;
    
    public function setBuilderQuery(){
        $data = $this->getData();
        if (isset($data['resourceid']) && $data['resourceid']) {
            $requestPath = $data['params']['url'];
            $requestPath = explode('?', $requestPath);
            $requestPath = $requestPath[0];

            $store = $this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface')->getStore();
            $storeId = $store->getId();
            $finder = $this->simiObjectManager->get('Magento\UrlRewrite\Model\UrlFinderInterface');
            $this->builderQuery = $finder->findOneByData([
                'request_path' => ltrim($requestPath, '/'),
                'store_id' => $storeId,
            ]);
            if (!$this->builderQuery || !$this->builderQuery->getEntityType()) {
                $this->builderQuery = $this->simiObjectManager
                    ->get('Simi\Simiconnector\Model\Cms')
                    ->getCollection()
                    ->addFieldToFilter('cms_url', $requestPath)->getFirstItem();
                if (!$this->builderQuery || !$this->builderQuery->getId())
                    throw new \Simi\Simiconnector\Helper\SimiException(__('No URL Rewrite Found'), 4);
            }
            $this->parseParams();
        }
    }
    
    public function parseParams() {
        $requestPaths = explode('?', $_SERVER['REQUEST_URI']);
        $this->params = array();
        foreach ($requestPaths as $key => $value) {
            if ($key == 0)
                continue;
            $params = array();
            parse_str($value, $params);
            $this->params = array_merge($this->params, $params);
        }
        unset($this->params['url']);
    }
    
    public function show() {
        $result = ['urldict'=>[]];
        $result['urldict']['entity_type'] = $this->builderQuery->getEntityType();
        if ($this->builderQuery->getEntityType() == 'product')
            $result['urldict']['product_id'] = $this->builderQuery->getEntityId();
        else if($this->builderQuery->getEntityType() == 'category')
            $result['urldict']['category_id'] = $this->builderQuery->getEntityId();
        $data = $this->getData();
        if(isset($result['urldict']['product_id']) && $result['urldict']['product_id']) {
            $apiModel = $this->simiObjectManager->get('Simi\Simiconnector\Model\Api\Products');
            $data['resourceid'] = $result['urldict']['product_id'];
            $apiModel->singularKey = 'product';
            $apiModel->setData($data);
            $apiModel->setBuilderQuery();
            $result['urldict']['simi_product_data'] = $apiModel->show();
        } else if(isset($result['urldict']['category_id']) && $result['urldict']['category_id']) {
            if (isset($data['params']['get_child_cat']) && $data['params']['get_child_cat']) {
                $apiModel = $this->simiObjectManager->get('Simi\Simiconnector\Model\Api\Categories');
                $result['urldict']['simi_catetory_name'] = $this
                    ->simiObjectManager->get('Magento\Catalog\Model\Category')
                    ->load($result['urldict']['category_id'])
                    ->getName();
                $data['resourceid'] = $result['urldict']['category_id'];
                $apiModel->pluralKey = 'categories';
                $apiModel->singularKey = 'category';
                $apiModel->setData($data);
                $apiModel->setBuilderQuery();
                $result['urldict']['simi_category_child'] = $apiModel->show();
            }

            $data['params'] = $this->params;
            $productListModel = $this->simiObjectManager
                ->get('Simi\Simiconnector\Model\Api\Products');
            $data['resourceid'] = null;
            $data['params'][self::FILTER] = array('cat_id'=>$result['urldict']['category_id']);
            $data['params']['image_width'] = isset($data['params']['image_width'])?
                $data['params']['image_width']:180;
            $data['params']['image_height'] = isset($data['params']['image_height'])?
                $data['params']['image_height']:180;
            $data['params']['limit'] = 12;
            
            // Apply filter
            $attributes = array();
            foreach ($this->simiObjectManager
                         ->get('\Magento\Catalog\Model\ResourceModel\Eav\Attribute')
                         ->getCollection() as $attribute) {
                $attributes[] = $attribute->getAttributecode();
            }
            $data['params'][self::FILTER]['layer'] = array();
            foreach ($this->params as $key=>$value) {
                if (in_array($key, $attributes) && $key !== 'email' && $key !== 'simi_hash')
                    $data['params'][self::FILTER]['layer'][$key] = $value;
            }
            
            // Apply sort 
            if(isset($data['params']['product_list_order']))
                $data['params']['order'] = $data['params']['product_list_order'];
            if(isset($data['params']['product_list_dir']))
                $data['params']['dir'] = $data['params']['product_list_dir'];
            
            $productListModel->pluralKey = 'products';
            $productListModel->singularKey = 'product';
            $productListModel->setData($data);
            $productListModel->setBuilderQuery();
            $result['urldict']['simi_category_products'] = $productListModel->index();
        } else {
            $result = parent::show();
        }
        return $result;
    }
}
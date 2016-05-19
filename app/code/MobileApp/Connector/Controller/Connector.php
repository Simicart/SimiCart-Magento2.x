<?php

namespace MobileApp\Connector\Controller;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Webapi\ErrorProcessor;
use Magento\Framework\Webapi\Request;
use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magento\Framework\Webapi\Rest\Response as RestResponse;
use Magento\Framework\Webapi\Rest\Response\FieldsFilter;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Webapi\Controller\Rest\ParamsOverrider;
use Magento\Webapi\Controller\Rest\Router;
use Magento\Webapi\Controller\Rest\Router\Route;
use Magento\Webapi\Model\Rest\Swagger\Generator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

class Connector extends \Magento\Framework\App\Action\Action
{

    /** @param string */
    protected $_serviceClassName;

    /** @param string */
    protected $_serviceMethodName;

    /** @param int */
    const DEFAULT_PAGE_SIZE = 10;

    /** @param int */
    const DEFAULt_OFFSET = 0;


    public function __construct(
        RestRequest $request,
        RestResponse $response,
        Router $router,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\State $appState,
        AuthorizationInterface $authorization,
        ServiceInputProcessor $serviceInputProcessor,
        ErrorProcessor $errorProcessor,
        //PathProcessor $pathProcessor,
        \Magento\Framework\App\AreaList $areaList,
        FieldsFilter $fieldsFilter,
        ParamsOverrider $paramsOverrider,
        ServiceOutputProcessor $serviceOutputProcessor,
        Generator $swaggerGenerator,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->_router = $router;
        $this->_request = $request;
        $this->_response = $response;
        $this->_objectManager = $objectManager;
        $this->_appState = $appState;
        $this->_authorization = $authorization;
        $this->serviceInputProcessor = $serviceInputProcessor;
        $this->_errorProcessor = $errorProcessor;
        //$this->_pathProcessor = $pathProcessor;
        $this->areaList = $areaList;
        $this->fieldsFilter = $fieldsFilter;
        $this->paramsOverrider = $paramsOverrider;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->swaggerGenerator = $swaggerGenerator;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resource = $resource;
        $this->dateTime = $dateTime;
        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        $this->_checkKey($request);
        parent::dispatch($request);
    }

    /*
     * Check
     *
     * @param RequestInterface $request
     */
    protected function _check($request){

        $enabled = $this->scopeConfig->getValue('mobileapp/view/enabled');
        if (!$enabled) {
            echo 'Connect was disable!';
            header("HTTP/1.0 503");
            exit();
        }

        $token = $request->getHeader('Token');
        if(!$token)
            $token = $request->getHeader('TOKEN');

        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $key = $this->_objectManager
            ->create('MobileApp\Connector\Model\Key')
            ->getKey($websiteId);
        $secretKey = $key->getSecretKey();

        if($token != $secretKey){
            echo 'Connect error!';
            header("HTTP/1.0 401 Unauthorized");
            exit();
        }
    }

    /**
     *
     * @return json
     */
    public function execute()
    {
        $serviceClassName = $this->_serviceClassName;
        $serviceMethodName = $this->_serviceMethodName;
        $outputData = $this->getOutputData($this->_params, $serviceClassName, $serviceMethodName);
        $outputData = $this->_formatData($outputData);

        /** @param \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData($outputData);

        //$this->_response->prepareResponse($outputData);
    }

    /*
     * Get data of object
     *
     * @param $params array
     * @param $serviceClassName string
     * @param $serviceMethodName string
     * return array
     */
    public function getOutputData($params, $serviceClassName, $serviceMethodName){
        $inputData = $this->paramsOverrider->override($params, []);
        $inputParams = $this->serviceInputProcessor->process($serviceClassName, $serviceMethodName, $inputData);
        $service = $this->_objectManager->get($serviceClassName);

        try{
            /** @param \Magento\Framework\Api\AbstractExtensibleObject $outputData */
            $outputData = call_user_func_array([$service, $serviceMethodName], $inputParams);

            $outputData = $this->serviceOutputProcessor->process(
                $outputData,
                $serviceClassName,
                $serviceMethodName
            );

            if ($this->_request->getParam(FieldsFilter::FILTER_PARAMETER) && is_array($outputData)) {
                $outputData = $this->fieldsFilter->filter($outputData);
            }
        }catch (\Exception $e){
            $outputData = ['message' => $e->getMessage()];
        }

        return $outputData;
    }

    /*
     * Parse params
     *
     * @param $params array
     * @return array
     */
    protected function _parseParams($params){
        return $params;
    }

    /*
     * Format data
     *
     * @param $data array
     * @return array
     */
    protected function _formatData($data){
        return $data;
    }

    /*
     * Get params from request
     *
     */

    protected function _getParams(){
        $data = $this->getRequest()->getParam('data');
        $params = $data?json_decode($data, true):[];
        return $params;
    }

    /*
     * Get image url
     *
     * @var $imageFile string
     * @return string
     */
    protected  function _getImageUrl($imageFile){
        $currentStore = $this->storeManager->getStore();
        return $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
        .'catalog/product'.$imageFile;
    }

    /*
     * Get product image(s) form sku(s)
     *
     * @param $skus array
     * @return array
     */
    protected function _getProductImages($skus){
        $items = [];
        $products = $this->_objectManager->get('Magento\Catalog\Model\Product')
            ->getCollection()
            ->addFieldToFilter('sku', ['in' => $skus])
            ->addAttributeToSelect('thumbnail');

        foreach($products as $product){
            $items[$product->getSku()] = $this->_getImageUrl($product->getThumbnail());
        }

        return $items;
    }

    /*
     * Parse name to first name and last name
     *
     * @param $name string
     * @return array
     */
    protected function _parseCustomerName($name){
        $temp = explode(' ', $name, 2);
        $firstname =  $temp[0];
        $lastName = isset($temp[1])?$temp[1]:$temp[0];
        return ['firstname' => $firstname, 'lastname' => $lastName];
    }

    /*
     * Get products by skus
     *
     * @param $skus array
     * @return array
     */
    protected function _getProducts($skus){
        $serviceClassName = 'Magento\Catalog\Api\ProductRepositoryInterface';
        $serviceMethodName = 'getList';

        $searchCriteria['filterGroups'][] = [
            'filters' => [
                [
                    'field' => 'sku',
                    'value' => implode(',', $skus),
                    'condition_type' => 'in'
                ]
            ]
        ];
        $searchCriteria['filterGroups'][] = [
            'filters' => [
                [
                    'field' =>'status',
                    'value' => 1
                ]
            ]
        ];

        $params = ['searchCriteria' => $searchCriteria];
        $output =  $this->getOutputData($params, $serviceClassName, $serviceMethodName);
        return $output['items'];
    }

    /*
     * Get product list by sku list
     *
     * @param $skuList array
     * @return array
     */
    protected function _getAttributes($attributeIds){
        $serviceClassName = 'Magento\Catalog\Api\ProductAttributeRepositoryInterface';
        $serviceMethodName = 'getList';

        $searchCriteria['filterGroups'][] = [
            'filters' => [
                [
                    'field' => 'main_table.attribute_id',
                    'value' => implode(',', $attributeIds),
                    'condition_type' => 'in'
                ]
            ]
        ];

        $params = ['searchCriteria' => $searchCriteria];
        $output =  $this->getOutputData($params, $serviceClassName, $serviceMethodName);
        return $output['items'];
    }

    /*
     * Get review summary
     *
     * @param $productId string
     * @return array
     */
    protected function _getProductReview($productId){
        $summary = $this->_objectManager->create('\Magento\Review\Model\Review\Summary')
            ->setStoreId($this->storeManager->getStore()->getId())
            ->load($productId);

        return [
            'product_review_number' => $summary->getReviewsCount(),
            'product_rate' => round($summary->getRatingSummary()/20, 2)
        ];
    }

    protected function _getCartItems(){
        $configurationHelper = $this->_objectManager->get('Magento\Catalog\Helper\Product\Configuration');
        $bundleHelper = $this->_objectManager->get('Magento\Bundle\Helper\Catalog\Product\Configuration');
        $cart = $this->_objectManager->get('Magento\Checkout\Model\Cart');
        $items = $cart->getQuote()->getAllVisibleItems();

        $dataItems = [];
        $productSkus = [];
        foreach($items as $item){
            $productSkus[] = $item->getSku();

            $dataItems[$item->getSku()] = [
                'cart_item_id' => $item->getId(),
                'product_id' => $item->getSku(),
                'stock_status' => true,
                'product_name' => $item->getName(),
                'product_price' => $item->getCalculationPrice(),
                'product_image' => '',
                'product_qty' => $item->getQty(),
                'product_type' => $item->getProductType(),
            ];

            if($item->getProductType() == 'bundle'){
                $options = $this->_getOptions($configurationHelper, $bundleHelper, $item);
            }else{
                $options = $configurationHelper->getOptions($item);
            }

            foreach($options as $option){
                $dataItems[$item->getSku()]['options'][] = [
                    'option_title' => $option['label'],
                    'option_value' => $option['value'],
                    'option_price' => isset($option['price'])?$option['price']:0,
                ];
            }
        }

        $productImages = $this->_getProductImages($productSkus);

        foreach($productImages as $sku => $productImage){
            $dataItems[$sku]['product_image'] = $productImage;
        }
        return $dataItems;
    }

    /**
     * Get bundled selections (slections-products collection)
     *
     * Returns array of options objects.
     * Each option object will contain array of selections objects
     *
     * @param ItemInterface $item
     * @return array
     */
    protected function _getCartBundleOptions(\Magento\Bundle\Helper\Catalog\Product\Configuration $bundleHelper,
                                     \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item)
    {
        $options = [];
        $product = $item->getProduct();

        /** @var \Magento\Bundle\Model\Product\Type $typeInstance */
        $typeInstance = $product->getTypeInstance();

        // get bundle options
        $optionsQuoteItemOption = $item->getOptionByCode('bundle_option_ids');
        $bundleOptionsIds = $optionsQuoteItemOption ? unserialize($optionsQuoteItemOption->getValue()) : [];
        if ($bundleOptionsIds) {
            /** @var \Magento\Bundle\Model\ResourceModel\Option\Collection $optionsCollection */
            $optionsCollection = $typeInstance->getOptionsByIds($bundleOptionsIds, $product);

            // get and add bundle selections collection
            $selectionsQuoteItemOption = $item->getOptionByCode('bundle_selection_ids');

            $bundleSelectionIds = unserialize($selectionsQuoteItemOption->getValue());

            if (!empty($bundleSelectionIds)) {
                $selectionsCollection = $typeInstance->getSelectionsByIds($bundleSelectionIds, $product);

                $bundleOptions = $optionsCollection->appendSelections($selectionsCollection, true);
                foreach ($bundleOptions as $bundleOption) {
                    if ($bundleOption->getSelections()) {
                        $option = ['label' => $bundleOption->getTitle(), 'value' => []];

                        $bundleSelections = $bundleOption->getSelections();

                        foreach ($bundleSelections as $bundleSelection) {
                            $qty = $bundleHelper->getSelectionQty($product, $bundleSelection->getSelectionId()) * 1;
                            if ($qty) {
                                $option['value'] = $qty . ' x ' .$bundleSelection->getName();
                                $option['price'] = $bundleHelper->getSelectionFinalPrice($item, $bundleSelection);
                                $options[] = $option;
                            }
                        }
                    }
                }
            }
        }

        return $options;
    }

    /**
     * Retrieves product options list
     *
     * @param ItemInterface $item
     * @return array
     */
    protected function _getOptions(\Magento\Catalog\Helper\Product\Configuration $configurationHelper,
                               \Magento\Bundle\Helper\Catalog\Product\Configuration $bundleHelper,
                               \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item)
    {
        return array_merge(
            $this->_getCartBundleOptions($bundleHelper, $item),
            $configurationHelper->getCustomOptions($item)
        );
    }
}

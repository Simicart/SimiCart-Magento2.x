<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * Path to store config where count of connector posts per page is stored
     *
     * @var string
     */
    const XML_PATH_ITEMS_PER_PAGE = 'simiconnector/view/items_per_page';

    /**
     * Media path to extension images
     *
     * @var string
     */
    const MEDIA_PATH = 'Simiconnector';

    /**
     * Maximum size for image in bytes
     * Default value is 1M
     *
     * @var int
     */
    const MAX_FILE_SIZE = 8388608;

    /**
     * Manimum image height in pixels
     *
     * @var int
     */
    const MIN_HEIGHT = 10;

    /**
     * Maximum image height in pixels
     *
     * @var int
     */
    const MAX_HEIGHT = 6000;

    /**
     * Manimum image width in pixels
     *
     * @var int
     */
    const MIN_WIDTH = 10;

    /**
     * Maximum image width in pixels
     *
     * @var int
     */
    const MAX_WIDTH = 10000;

    /**
     * Array of image size limitation
     *
     * @var array
     */
    public $imageSize = [
        'minheight' => self::MIN_HEIGHT,
        'minwidth' => self::MIN_WIDTH,
        'maxheight' => self::MAX_HEIGHT,
        'maxwidth' => self::MAX_WIDTH,
    ];

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    public $mediaDirectory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    public $filesystem;

    /**
     * @var \Magento\Framework\HTTP\Adapter\FileTransferFactory
     */
    public $httpFactory;

    /**
     * File Uploader factory
     *
     * @var \Magento\Core\Model\File\UploaderFactory
     */
    public $fileUploaderFactory;

    /**
     * File Uploader factory
     *
     * @var \Magento\Framework\Io\File
     */
    public $ioFile;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /*
     * Scope Config
     *
     *
     */
    public $scopeConfig;

    /*
     * Object Mangager
     *
     *
     */
    public $simiObjectManager;
    public $resource;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList;
     */
    public $directionList;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        DirectoryList $directoryList
    ) {
    
        $this->simiObjectManager = $simiObjectManager;
        $this->scopeConfig = $this->simiObjectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->filesystem = $this->simiObjectManager->get('\Magento\Framework\Filesystem');
        $this->mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->httpFactory = $this->simiObjectManager->create('\Magento\Framework\HTTP\Adapter\FileTransferFactory');
        $this->fileUploaderFactory = $this->simiObjectManager
            ->get('\Magento\MediaStorage\Model\File\UploaderFactory');
        $this->ioFile = $this->simiObjectManager->get('\Magento\Framework\Filesystem\Io\File');
        $this->storeManager = $this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $this->_imageFactory = $this->simiObjectManager->get('\Magento\Framework\Image\Factory');
        $this->resource = $this->simiObjectManager->get('\Magento\Framework\App\ResourceConnection');
        $this->resourceFactory = $this->simiObjectManager
            ->get('\Magento\Reports\Model\ResourceModel\Report\Collection\Factory');
        $this->directionList = $directoryList;
        parent::__construct($context);
    }

    /*
     * Get Store Config Value
     */

    public function getStoreConfig($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Remove Simiconnector item image by image filename
     *
     * @param string $imageFile
     * @return bool
     */
    public function removeImage($imageFile)
    {
        $io = $this->ioFile;
        $io->open(['path' => $this->getBaseDir()]);
        if ($io->fileExists($imageFile)) {
            return $io->rm($imageFile);
        }
        return false;
    }

    /**
     * Return URL for resized Simiconnector Item Image
     *
     * @param Simi\Simiconnector\Model\Simiconnector $item
     * @param integer $width
     * @param integer $height
     * @return bool|string
     */
    public function resize(\Simi\Simiconnector\Model\Simiconnector $item, $width, $height = null)
    {
        if (!$item->getImage()) {
            return false;
        }

        if ($width < self::MIN_WIDTH || $width > self::MAX_WIDTH) {
            return false;
        }
        $width = (int)$width;

        if (!($height === null)) {
            if ($height < self::MIN_HEIGHT || $height > self::MAX_HEIGHT) {
                return false;
            }
            $height = (int)$height;
        }

        $imageFile = $item->getImage();
        $cacheDir = $this->getBaseDir() . '/' . 'cache' . '/' . $width;
        $cacheUrl = $this->getBaseUrl() . '/' . 'cache' . '/' . $width . '/';

        $io = $this->ioFile;
        $io->checkAndCreateFolder($cacheDir);
        $io->open(['path' => $cacheDir]);
        if ($io->fileExists($imageFile)) {
            return $cacheUrl . $imageFile;
        }

        try {
            $image = $this->_imageFactory->create($this->getBaseDir() . '/' . $imageFile);
            $image->resize($width, $height);
            $image->save($cacheDir . '/' . $imageFile);
            return $cacheUrl . $imageFile;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Upload image and return uploaded image file name or false
     *
     * @throws Mage_Core_Exception
     * @param string $scope the request key for file
     * @return bool|string
     */
    public function uploadImage($scope)
    {
        $adapter = $this->httpFactory->create();
        /*
         *
         * Comment to avoid using direct new class initializing function
         * Uncomment it if customer want to use image validation back (Size of image and file
         * size)
         * 
         * 
         * 
        $adapter->addValidator(new \Zend_Validate_File_ImageSize($this->imageSize));
        $adapter->addValidator(
            new \Zend_Validate_File_FilesSize(['max' => self::MAX_FILE_SIZE])
        );
         * 
         */
        if ($adapter->isUploaded($scope)) {
            // validate image
            if (!$adapter->isValid($scope)) {
                throw new \Simi\Simiconnector\Helper\SimiException(__('Uploaded image is not valid.'));
            }
            $uploader = $this->fileUploaderFactory->create(['fileId' => $scope]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);
            $uploader->setAllowCreateFolders(true);
            $ext = $uploader->getFileExtension();
            if ($uploader->save($this->getBaseDir(), $scope . time() . '.' . $ext)) {
                return 'Simiconnector/' . $uploader->getUploadedFileName();
            }
        }
        return false;
    }

    /**
     * Return the base media directory for Simiconnector Item images
     *
     * @return string
     */
    public function getBaseDir()
    {
        $path = $this->filesystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath(self::MEDIA_PATH);
        return $path;
    }

    /**
     * Return the Base URL for Simiconnector Item images
     *
     * @return string
     */
    public function getBaseUrl($addMediaPath = true)
    {
        if ($addMediaPath == true) {
            return $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ) . '/' . self::MEDIA_PATH;
        } else {
            return $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );
        }
    }

    /**
     * Return the number of items per page
     * @return int
     */
    public function getConnectorPerPage()
    {
        return abs((int)$this->scopeConfig
            ->getValue(self::XML_PATH_ITEMS_PER_PAGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
    }

    /*
     * Visibility Id for Different Types
     */

    public function getVisibilityTypeId($contentTypeName)
    {
        switch ($contentTypeName) {
            case 'cms':
                $typeId = 1;
                break;
            case 'banner':
                $typeId = 2;
                break;
            case 'homecategory':
                $typeId = 3;
                break;
            case 'productlist':
                $typeId = 4;
                break;
            case 'storelocator':
                $typeId = 5;
                break;
            default:
                $typeId = 0;
                break;
        }
        return $typeId;
    }

    public function countCollection($collection)
    {
        return $collection->getSize();
    }

    public function countArray($array)
    {
        return count($array);
    }

    public function deleteModel($model)
    {
        $model->delete();
    }

    public function saveModel($model)
    {
        $model->save();
    }

    public function flushStaticCache()
    {
        $path = $this->directionList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'Simiconnector'
            . DIRECTORY_SEPARATOR . 'cache';
        if (is_dir($path)) {
            $this->_removeFolder($path);
        }
    }

    private function _removeFolder($folder)
    {
        if (is_dir($folder)) {
            $dir_handle = opendir($folder);
        }
        if (!$dir_handle) {
            return false;
        }
        while ($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($folder . "/" . $file)) {
                    unlink($folder . "/" . $file);
                } else {
                    $this->_removeFolder($folder . '/' . $file);
                }
            }
        }
        closedir($dir_handle);
        rmdir($folder);
        return true;
    }

    public function getRealIp() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        $ips = explode(' ', str_replace(',', ' ', $ipaddress));
        return $ips[0];
    }

    /**
     * Check magento version if it is one of input
     *
     * @param array[string] $versions the request key for file
     * @return bool
     */
    public function isVersion($versions) {
        try {
            $productMetadata = $this->simiObjectManager->get('Magento\Framework\App\ProductMetadataInterface');
            if ($productMetadata && $magentoVersion = $productMetadata->getVersion()) {
                foreach ($versions as $version) {
                    if (strpos($magentoVersion, $version) === 0)
                        return true;
                }
            }
        }
        catch (\Exception $e) {
        }
        return false;
    }

    /*
     * Set quote model to sessions
     */
    public function setQuoteToSession($quoteModel) {
        $appState = $this->simiObjectManager->get('\Magento\Framework\App\State');
            if($appState->getAreaCode() == 'adminhtml') return;//not allowed admin area
        $quoteId = $quoteModel->getId();
        try {
            $cart = $this->simiObjectManager->get('Magento\Checkout\Model\Cart');
            if ($cart->getQuote() && $cart->getQuote()->getId() == $quoteId)
                return;
            $quoteModel->setStore($this->simiObjectManager
                ->create('\Magento\Store\Model\StoreManagerInterface')->getStore());
            $this->simiObjectManager->get('\Magento\Quote\Api\CartRepositoryInterface')->save($quoteModel);
            $quoteModel = $this->simiObjectManager->get('\Magento\Quote\Api\CartRepositoryInterface')->get($quoteModel->getId());
            $this->simiObjectManager->get('\Magento\Customer\Model\Session')->setQuoteId($quoteId);
            $cart->setQuote($quoteModel);
            try { //not a very important function
                $this->simiObjectManager->get('\Magento\Checkout\Model\Session')->clearQuote();
            } catch (\Exception $e) {

            }
            $this->simiObjectManager->get('\Magento\Checkout\Model\Session')->setQuoteId($quoteId);
            $this->simiObjectManager->get('\Magento\Checkout\Model\Session')->replaceQuote($quoteModel);
        } catch (\Exception $e) {

        }
    }
}

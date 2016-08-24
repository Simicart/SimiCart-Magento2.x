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
    const XML_PATH_ITEMS_PER_PAGE     = 'simiconnector/view/items_per_page';
    
    /**
     * Media path to extension images
     *
     * @var string
     */
    const MEDIA_PATH    = 'Simiconnector';

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
    protected $_imageSize   = array(
        'minheight'     => self::MIN_HEIGHT,
        'minwidth'      => self::MIN_WIDTH,
        'maxheight'     => self::MAX_HEIGHT,
        'maxwidth'      => self::MAX_WIDTH,
    );
    
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\HTTP\Adapter\FileTransferFactory
     */
    protected $httpFactory;
    
    /**
     * File Uploader factory
     *
     * @var \Magento\Core\Model\File\UploaderFactory
     */
    protected $_fileUploaderFactory;
    
    /**
     * File Uploader factory
     *
     * @var \Magento\Framework\Io\File
     */
    protected $_ioFile;
    
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    
    /*
     * Scope Config
     * 
     * 
     */
    protected $_scopeConfig;
    
    /*
     * Object Mangager
     * 
     * 
     */
    protected $_objectManager;
    
    protected $_resource;


    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\File\Size $fileSize,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\Filesystem\Io\File $ioFile,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Image\Factory $imageFactory,
            
        \Magento\Reports\Model\ResourceModel\Report\Collection\Factory $resourceFactory,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_scopeConfig = $this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
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
    
    /*
     * Get Store Config Value
     */
    public function getStoreConfig($path) {
        return $this->_scopeConfig->getValue($path);
    }
    
    /**
     * Remove Simiconnector item image by image filename
     *
     * @param string $imageFile
     * @return bool
     */
    public function removeImage($imageFile)
    {
        $io = $this->_ioFile;
        $io->open(array('path' => $this->getBaseDir()));
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

        if (!is_null($height)) {
            if ($height < self::MIN_HEIGHT || $height > self::MAX_HEIGHT) {
                return false;
            }
            $height = (int)$height;
        }

        $imageFile = $item->getImage();
        $cacheDir  = $this->getBaseDir() . '/' . 'cache' . '/' . $width;
        $cacheUrl  = $this->getBaseUrl() . '/' . 'cache' . '/' . $width . '/';

        $io = $this->_ioFile;
        $io->checkAndCreateFolder($cacheDir);
        $io->open(array('path' => $cacheDir));
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
        $adapter->addValidator(new \Zend_Validate_File_ImageSize($this->_imageSize));
        $adapter->addValidator(
            new \Zend_Validate_File_FilesSize(['max' => self::MAX_FILE_SIZE])
        );
        if ($adapter->isUploaded($scope)) {
            // validate image
            if (!$adapter->isValid($scope)) {
                throw new \Exception(__('Uploaded image is not valid.'));
            }
            $uploader = $this->_fileUploaderFactory->create(['fileId' => $scope]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);
            $uploader->setAllowCreateFolders(true);
            if ($uploader->save($this->getBaseDir())) {
                return 'Simiconnector/'.$uploader->getUploadedFileName();
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
    public function getBaseUrl()
    { 
        return $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ) . '/' . self::MEDIA_PATH;
    }
    
    /**
     * Return the number of items per page
     * @return int
     */
    public function getConnectorPerPage()
    {
        return abs((int)$this->_scopeConfig->getValue(self::XML_PATH_ITEMS_PER_PAGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
    }
    
    /*
     * Visibility Id for Different Types
     */
    public function getVisibilityTypeId($contentTypeName) {
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
            default :
                $typeId = 0;
                break;
        }
        return $typeId;
    }
}

<?php

/**
 * Connector data helper
 */
namespace MobileApp\Connector\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Path to store config where count of connector posts per page is stored
     *
     * @var string
     */
    const XML_PATH_ITEMS_PER_PAGE = 'connector/view/items_per_page';

    /**
     * Media path to extension images
     *
     * @var string
     */
    const MEDIA_PATH = 'Connector';

    /**
     * Maximum size for image in bytes
     * Default value is 1M
     *
     * @var int
     */
    const MAX_FILE_SIZE = 1048576;

    /**
     * Manimum image height in pixels
     *
     * @var int
     */
    const MIN_HEIGHT = 50;

    /**
     * Maximum image height in pixels
     *
     * @var int
     */
    const MAX_HEIGHT = 800;

    /**
     * Manimum image width in pixels
     *
     * @var int
     */
    const MIN_WIDTH = 50;

    /**
     * Maximum image width in pixels
     *
     * @var int
     */
    const MAX_WIDTH = 1024;

    /**
     * iOs name
     *
     * @var string
     */
    const IOS_NAME = 'iOs';

    /**
     * Android name
     *
     * @var string
     */
    const ANDROID_NAME = 'Android';

    /**
     * iPad name
     *
     * @var string
     */
    const IPAD_NAME = 'iPad';


    /**
     * Array of image size limitation
     *
     * @var array
     */
    protected $_imageSize = array(
        'minheight' => self::MIN_HEIGHT,
        'minwidth' => self::MIN_WIDTH,
        'maxheight' => self::MAX_HEIGHT,
        'maxwidth' => self::MAX_WIDTH,
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

    /**
     * @var \MobileApp\Connector\Model\Connector
     */
    protected $_websiteCollectionFactory;

    /**
     * @var \MobileApp\Connector\Model\Connector
     */
    protected $_appFactory;

    /**
     * @var \MobileApp\Connector\Model\Connector
     */
    protected $_designFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\File\Size $fileSize,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\Filesystem\Io\File $ioFile,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Image\Factory $imageFactory,
        \Magento\Store\Model\ResourceModel\Website\CollectionFactory $websiteCollectionFactory,
        \MobileApp\Connector\Model\AppFactory $appFactory,
        \MobileApp\Connector\Model\DesignFactory $designFactory
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->filesystem = $filesystem;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->httpFactory = $httpFactory;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_ioFile = $ioFile;
        $this->_storeManager = $storeManager;
        $this->_imageFactory = $imageFactory;
        $this->_websiteCollectionFactory = $websiteCollectionFactory;
        $this->_appFactory = $appFactory;
        $this->_designFactory = $designFactory;
        parent::__construct($context);
    }

    /**
     * Remove Connector item image by image filename
     *
     * @param string $imageFile
     * @return bool
     */
    public
    function removeImage($imageFile)
    {
        $io = $this->_ioFile;
        $io->open(array('path' => $this->getBaseDir()));
        if ($io->fileExists($imageFile)) {
            return $io->rm($imageFile);
        }
        return false;
    }

    /**
     * Return URL for resized Connector Item Image
     *
     * @param MobileApp\Connector\Model\Connector $item
     * @param integer $width
     * @param integer $height
     * @return bool|string
     */
    public
    function resize(\MobileApp\Connector\Model\Connector $item, $width, $height = null)
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
        $cacheDir = $this->getBaseDir() . '/' . 'cache' . '/' . $width;
        $cacheUrl = $this->getBaseUrl() . '/' . 'cache' . '/' . $width . '/';

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
    public
    function uploadImage($scope)
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
                return 'Connector/' . $uploader->getUploadedFileName();
            }
        }
        return false;
    }

    /**
     * Return the base media directory for Connector Item images
     *
     * @return string
     */
    public
    function getBaseDir()
    {
        $path = $this->filesystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath(self::MEDIA_PATH);
        return $path;
    }

    /**
     * Return the Base URL for Connector Item images
     *
     * @return string
     */
    public
    function getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . '/' . self::MEDIA_PATH;
    }

    /**
     * Return the number of items per page
     * @return int
     */
    public
    function getConnectorPerPage()
    {
        return abs((int)$this->_scopeConfig->getValue(self::XML_PATH_ITEMS_PER_PAGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
    }

    /**
     * @return array
     */
    public
    function getDataDesign()
    {
        return array(
            array(
                'theme_color' => '#FFA238',
                'theme_logo' => '',
                'device_id' => 1,
                'app_name' => 'N/A'
            ),
            array(
                'theme_color' => '#FFA238',
                'theme_logo' => '',
                'device_id' => 2,
                'app_name' => 'N/A'
            ),
            array(
                'theme_color' => '#FFA238',
                'theme_logo' => '',
                'device_id' => 3,
                'app_name' => 'N/A'
            ),
        );
    }

    /**
     * @return array
     */
    public
    function getDevice()
    {
        return array(
            1 => self::IOS_NAME,
            2 => self::IPAD_NAME,
            3 => self::ANDROID_NAME,
        );
    }

    /**
     * @param $name
     * @return int
     */
    public
    function getDeviceIdByName($name)
    {
        $id = 1;
        switch ($name) {
            case self::IOS_NAME:
                $id = 1;
                break;
            case self::IPAD_NAME:
                $id = 2;
                break;
            case self::ANDROID_NAME:
                $id = 3;
                break;
        }
        return $id;
    }

    /**
     * @param $id
     * @return string
     */
    public
    function getNameDeviceById($id)
    {
        $name = '';
        switch ($id) {
            case 1:
                $name = self::IOS_NAME;
                break;
            case 2:
                $name = self::IPAD_NAME;
                break;
            case 3:
                $name = self::ANDROID_NAME;
                break;
            default :
                $name = self::IOS_NAME;
                break;
        }
        return $name;
    }

    /**
     * @return mixed
     */
    public
    function getWebsites()
    {
        $collection = $this->_websiteCollectionFactory->create();
        return $collection;
    }

    /**
     * @return mixed
     */
    public
    function initAppModel()
    {
        $model = $this->_appFactory->create();
        return $model;
    }

    /**
     * @return mixed
     */
    public
    function initDesignModel()
    {
        $model = $this->_designFactory->create();
        return $model;
    }


    /**
     * save data to design table
     */
    public
    function importDesgin()
    {
        $websites = $this->getWebsites();
        $check = false;
        $data = $this->getDataDesign();
        foreach ($websites as $website) {
            $check = true;
            foreach ($data as $item) {
                $model = $this->initDesignModel();
                $model->setData($item);
                $model->setWebsiteId($website->getId());
                $model->save();
            }
        }
        if (!$check) {
            foreach ($data as $item) {
                $model = $this->initDesignModel();
                $model->setData($item);
                $model->save();
            }
        }
    }

    /**
     * save data to app table
     */
    public
    function importApp()
    {
        $check = false;
        $websites = $this->getWebsites();
        $data = $this->getDataDesign();

        foreach ($websites as $website) {
            $check = true;
            foreach ($data as $item) {
                $model = $this->initAppModel();
                $model->setData($item);
                $model->setWebsiteId($website->getId());
                $model->save();
            }
        }

        if (!$check) {
            foreach ($data as $item) {
                $model = $this->initAppModel();
                $model->setData($item);
                $model->save();
            }
        }
    }


}

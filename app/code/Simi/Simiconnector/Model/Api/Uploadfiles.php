<?php

namespace Simi\Simiconnector\Model\Api;

class Uploadfiles extends Apiabstract
{
    public function setBuilderQuery()
    {
        $data = $this->getData();
    }

    public function store()
    {
        $objectManager = $this->simiObjectManager;
        $fileSystem = $objectManager->create('\Magento\Framework\Filesystem');
        $mediaPath  =   $fileSystem
            ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
            ->getAbsolutePath();
        $oriPath = 'Simiconnector/tmp/';
        $media  =  $mediaPath.$oriPath;

        if ($_FILES['file']['type'] == 'text/php' ||
            strpos($_FILES['file']['type'], 'application') !== false)
            throw new \Simi\Simiconnector\Helper\SimiException(__('No supported type'), 4);

        $file_name = rand().md5(time()).$_FILES['file']['name'];
        $file_tmp =$_FILES['file']['tmp_name'];
        $file_type = $_FILES['file']['type'];
        if (move_uploaded_file($file_tmp,$media.$file_name))
        {
            return array('uploadfile'=>
                array(
                    'title'=>$_FILES['file']['name'],
                    'type'=>$file_type,
                    'full_path'=>$media.$file_name,
                    'quote_path'=>$oriPath.$file_name,
                    'order_path'=>$oriPath.$file_name,
                    'secret_key'=>substr(md5(file_get_contents($media.$file_name)), 0, 20)
                )
            );
        }
        else
        {
            throw new \Simi\Simiconnector\Helper\SimiException(__('File was not uploaded'), 4);
        }
    }
}

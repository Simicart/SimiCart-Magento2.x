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
        $uploader = $objectManager->create('Magento\MediaStorage\Model\File\Uploader',['fileId' => 'file']);
        $file = $uploader->validateFile();
        if ($file['type'] == 'text/php' ||
            strpos($file['type'], 'application') !== false)
            throw new \Simi\Simiconnector\Helper\SimiException(__('No supported type'), 4);

        $encodeMethod = 'md5';
        $file_name = rand().$encodeMethod(time()).$file['name'];
        $file_tmp =$file['tmp_name'];
        $file_type = $file['type'];
        if (move_uploaded_file($file_tmp,$media.$file_name))
        {
            return array('uploadfile'=>
                array(
                    'title'=>$file['name'],
                    'type'=>$file_type,
                    'full_path'=>$media.$file_name,
                    'quote_path'=>$oriPath.$file_name,
                    'order_path'=>$oriPath.$file_name,
                    'secret_key'=>substr($encodeMethod(file_get_contents($media.$file_name)), 0, 20)
                )
            );
        }
        else
        {
            throw new \Simi\Simiconnector\Helper\SimiException(__('File was not uploaded'), 4);
        }
    }
}

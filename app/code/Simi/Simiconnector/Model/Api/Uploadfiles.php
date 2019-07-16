<?php

namespace Simi\Simiconnector\Model\Api;

class Uploadfiles extends Apiabstract
{
    public function setBuilderQuery()
    {
        $data = $this->getData();
    }

    public function getReturnedData($file, $file_type, $media, $oriPath, $file_name, $encodeMethod) {
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

    public function store()
    {
        $data = $this->getData();
        $objectManager = $this->simiObjectManager;
        $fileSystem = $objectManager->create('\Magento\Framework\Filesystem');
        $mediaPath  =   $fileSystem
            ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
            ->getAbsolutePath();
        $oriPath = 'Simiconnector/tmp/';
        $media  =  $mediaPath.$oriPath;
        if (!file_exists($media)) {
            mkdir($media, 0775, true);
        }
        if (isset($data['contents_array']['fileData'])) {
            //get file from raw data
            $file = $data['contents_array']['fileData'];
        } else {
            //get file from form data
            $uploader = $objectManager->create('Magento\MediaStorage\Model\File\Uploader',['fileId' => 'file']);
            $file = $uploader->validateFile();
        }

        if ($file['type'] == 'text/php' ||
            strpos($file['type'], 'application') !== false ||
            strpos($file['name'], '.php') !== false)
            throw new \Simi\Simiconnector\Helper\SimiException(__('No supported type'), 4);

        $encodeMethod = 'md5';
        $file_name = rand().$encodeMethod(time()).$file['name'];
        $file_tmp = isset($file['tmp_name'])?$file['tmp_name']:null;
        $file_type = $file['type'];

        if (isset($file['base64'])) {
            $base64 = str_replace(' ', '+', $file['base64']);
            $content = base64_decode($base64);
            $saved_file = fopen($media.$file_name, "wb");
            fwrite($saved_file, $content);
            fclose($saved_file);
            return $this->getReturnedData($file, $file_type, $media, $oriPath, $file_name, $encodeMethod);
        } else if ($file_tmp && move_uploaded_file($file_tmp,$media.$file_name)) {
            return $this->getReturnedData($file, $file_type, $media, $oriPath, $file_name, $encodeMethod);
        } else {
            throw new \Simi\Simiconnector\Helper\SimiException(__('File was not uploaded'), 4);
        }
    }
}

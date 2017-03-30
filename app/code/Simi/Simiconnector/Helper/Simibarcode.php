<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

class Simibarcode extends \Simi\Simiconnector\Helper\Data
{

    /**
     * Barcode Config
     *
     * return string
     */
    public function getBarcodeConfig($code)
    {
        return $this->getStoreConfig('simiconnector/barcode/' . $code);
    }

    /**
     * Validate code
     *
     * return string
     */
    public function getValidateBarcode()
    {
        $validate = 'required-entry';
        return $validate;
    }

    /**
     * Generate code
     *
     * return string
     */
    public function generateCode($string)
    {
        $barcode = preg_replace_callback('#\[([AN]{1,2})\.([0-9]+)\]#', [$this, 'convertExpression'], $string);
        $checkBarcodeExist = $this->simiObjectManager
                ->create('Simi\Simiconnector\Model\Simibarcode')->load($barcode, 'barcode');
        if ($checkBarcodeExist->getId()) {
            $barcode = $this->generateCode($string);
        }

        return $barcode;
    }

    /**
     * Random code
     *
     * return string
     */
    public function convertExpression($param)
    {
        $alphabet = (strpos($param[1], 'A')) === false ? '' : 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $alphabet .= (strpos($param[1], 'N')) === false ? '' : '0123456789';
        return $this->getRandomString($param[2], $alphabet);
    }

    /**
     * get All column
     *
     * return Array
     */
    public function getAllColumOfTable()
    {
        return ['barcode_id','barcode',
            'qrcode','barcode_status','product_entity_id','product_name','product_sku','created_date'];
    }

    /**
     * get value for barcode
     *
     * param String $table, String $column, int $productId, array $data
     * return Array
     */
    public function getValueForBarcode($table, $column, $productId)
    {
        if ($table == 'product') {
            $model = $this->simiObjectManager->create('Magento\Catalog\Model\Product')->load($productId);
            return $model->getData($column);
        }
    }

    /**
     * import Product
     *
     * param array()
     */
    public function importProduct($data)
    {
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($data)) {
            $this->simiObjectManager
                    ->create('Magento\Backend\Model\Session')->setData('null_barcode_product_import', 0);
        } else {
            $this->simiObjectManager
                    ->create('Magento\Backend\Model\Session')->setData('null_barcode_product_import', 1);
            $this->simiObjectManager
                    ->create('Magento\Backend\Model\Session')->setData('barcode_product_import', null);
        }
    }

    public function getRandomString($length, $chars = null)
    {
        return $this->simiObjectManager->create('Magento\Framework\Math\Random')->getRandomString($length, $chars);
    }
}

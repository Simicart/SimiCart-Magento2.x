<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Simibarcode;

use Magento\Backend\App\Action;

class Save extends \Magento\Backend\App\Action
{

    /**
     * Save action
     *
     * @return void
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $simiObjectManager = $this->_objectManager;
            $model = $simiObjectManager->create('Simi\Simiconnector\Model\Simibarcode');
            $id    = $this->getRequest()->getParam('barcode_id');
            if ($id) {
                $model->load($id);
            }

            try {
                if ($model->getId()) {
                    $model->addData($data);
                    
                    try {
                        $model->save();
                        $this->messageManager->addSuccess(__('The Data has been saved.'));
                        $simiObjectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                        if ($this->getRequest()->getParam('back')) {
                            $this->_redirect('*/*/edit', ['barcode_id' => $model->getId(), '_current' => true]);
                            return;
                        }
                        $this->_redirect('*/*/');
                        return;
                    } catch (\Magento\Framework\Model\Exception $e) {
                        $this->messageManager->addError($e->getMessage());
                    } catch (\RuntimeException $e) {
                        $this->messageManager->addError($e->getMessage());
                    } catch (\Exception $e) {
                        $this->messageManager->addException($e, __('Something went wrong while saving the data.'));
                    }

                    $this->_getSession()->setFormData($data);
                    $this->_redirect('*/*/edit', ['barcode_id' => $this->getRequest()->getParam('barcode_id')]);
                    return;
                }
                $this->createNewCode($simiObjectManager, $data);
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('*/*/edit', ['banner_id' => $model->getId()]);
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    private function createNewCode($simiObjectManager, $data)
    {
        $sqlNews      = [];
        $sqlOlds      = '';
        $countSqlOlds = 0;

        $tablename = 'simiconnector/simibarcode';

        $results = $simiObjectManager->get('Simi\Simiconnector\Helper\Simibarcode')->getAllColumOfTable();

        $columns = [];
        $string  = '';
        $type    = '';

        foreach ($results as $result) {
            $fields = explode('_', $result);
            if ($fields[0] == 'barcode' || $fields[0] == 'qrcode') {
                continue;
            }
            foreach ($fields as $id => $field) {
                if ($id == 0) {
                    $type = $field;
                }
                if ($id == 1) {
                    $string = $field;
                }
                if ($id > 1) {
                    $string = $string . '_' . $field;
                }
            }
            $columns[] = [$type => $string];
            $string    = '';
            $type      = '';
        }

        $this->generateSqlQuery($sqlNews, $data, $simiObjectManager, $columns);
        if (!empty($sqlNews)) {
            $resource        = $simiObjectManager->create('Magento\Framework\App\ResourceConnection');
            $resourceModel   = $simiObjectManager
                    ->create('Simi\Simiconnector\Model\ResourceModel\Simibarcode');
            $writeConnection = $resourceModel->getConnection();
            $tablename       = $resource->getTableName($resourceModel::TABLE_NAME);

            $writeConnection->insertMultiple($tablename, $sqlNews);
        }

        $simiObjectManager->create('Magento\Backend\Model\Session')->setData('barcode_product_import', null);

        if ($this->getRequest()->getParam('back')) {
            $this->messageManager->addSuccess(__('Barcode was successfully saved.'));
            $this->_redirect('*/*/new');
            return;
        }
        $this->messageManager->addSuccess(__('Barcode was successfully saved.'));

        $this->_redirect('*/*');
    }
    
    private function generateSqlQuery(&$sqlNews, $data, $simiObjectManager, $columns)
    {
        if (isset($data['product_ids'])) {
            $products         = [];
            $productsExplodes = explode(',', str_replace(' ', '', $data['product_ids']));

            if ($simiObjectManager
                    ->get('Simi\Simiconnector\Helper\Data')->countArray($productsExplodes)) {
                $productIds = '';
                $count      = 0;
                $j          = 0;
                $barcode    = [];
                $qrcode     = [];
                foreach ($productsExplodes as $pId) {
                    $codeArr            = [];
                    //auto generate barcode
                    $codeArr['barcode'] = $this->checkDupplicate($barcode);
                    $barcode[]          = $codeArr['barcode'];
                    //auto generate QRcode
                    $codeArr['qrcode']  = $this->checkDupplicateQrcode($qrcode);
                    $qrcode[]           = $codeArr['qrcode'];

                    $sqlNews[$j] = [
                        'barcode'        => $codeArr['barcode'],
                        'qrcode'         => $codeArr['qrcode'],
                        'barcode_status' => 1,
                    ];
                    foreach ($columns as $id => $column) {
                        $this->addOtherData($sqlNews, $simiObjectManager, $column, $j, $codeArr, $pId);
                    }
                    $sqlNews[$j]['created_date'] = $simiObjectManager
                            ->get('\Magento\Framework\Stdlib\DateTime\DateTimeFactory')
                            ->create()->gmtDate();
                    $j++;
                }
            }
        }
    }
    private function addOtherData(&$sqlNews, $simiObjectManager, $column, $j, $codeArr, $pId)
    {
        $i          = 0;
        $columnName = '';

        foreach ($column as $_id => $key) {
            if ($i == 0) {
                $columnName = $_id . '_' . $key;
            }
            if ($i > 0) {
                $columnName = $columnName . '_' . $key;
            }

            $i++;
        }

        if ($_id != 'custom') {
            $return = $simiObjectManager
                    ->get('Simi\Simiconnector\Helper\Simibarcode')
                    ->getValueForBarcode($_id, $key, $pId);
            if (is_array($return)) {
                foreach ($return as $_columns) {
                    foreach ($_columns as $_column => $value) {
                        if (!isset($sqlNews[$_id . '_' . $_column])) {
                            $sqlNews[$j][$_id . '_' . $_column] = $value;
                        } else {
                            $sqlNews[$j][$_id . '_' . $_column] .= ',' . $value;
                        }
                    }
                }
            } else {
                $sqlNews[$j][$columnName] = $return;
            }
        } else {
            if (isset($codeArr[$columnName])) {
                $sqlNews[$j][$columnName] = $codeArr[$columnName];
            }
        }
    }
    /**
     * check barcode dupplicate
     */
    private function checkDupplicate($barcode)
    {
        $simiObjectManager = $this->_objectManager;
        $code = $simiObjectManager->get('Simi\Simiconnector\Helper\Simibarcode')
                ->generateCode($simiObjectManager->get('Simi\Simiconnector\Helper\Simibarcode')
                        ->getBarcodeConfig('pattern'));
        if (in_array($code, $barcode)) {
            $code = $this->checkDupplicate($barcode);
        }
        return $code;
    }

    /**
     * check QRcode dupplicate
     */
    private function checkDupplicateQrcode($qrcode)
    {
        $simiObjectManager = $this->_objectManager;
        $code = $simiObjectManager->get('Simi\Simiconnector\Helper\Simibarcode')
                ->generateCode($simiObjectManager->get('Simi\Simiconnector\Helper\Simibarcode')
                        ->getBarcodeConfig('qrcode_pattern'));
        if (in_array($code, $qrcode)) {
            $code = $this->checkDupplicate($qrcode);
        }
        return $code;
    }
}

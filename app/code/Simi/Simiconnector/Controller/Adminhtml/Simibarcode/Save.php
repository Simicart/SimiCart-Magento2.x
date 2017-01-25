<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Simibarcode;

use Magento\Backend\App\Action;

class Save extends \Magento\Backend\App\Action {

    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @param Action\Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(Action\Context $context, PostDataProcessor $dataProcessor) {
        $this->dataProcessor = $dataProcessor;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed() {
        return true;
        //return $this->_authorization->isAllowed('Simi_Simiconnector::simibarcode_save');
    }

    /**
     * Save action
     *
     * @return void
     */
    public function execute() {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $data = $this->dataProcessor->filter($data);

            $model = $this->_objectManager->create('Simi\Simiconnector\Model\Simibarcode');
            $id = $this->getRequest()->getParam('barcode_id');
            if ($id) {
                $model->load($id);
            }

            try {
                if ($model->getId()) {
                    $model->addData($data);
                    if (!$this->dataProcessor->validate($data)) {
                        $this->_redirect('*/*/edit', ['barcode_id' => $model->getId(), '_current' => true]);
                        return;
                    }
                    try {
                        $model->save();
                        $this->messageManager->addSuccess(__('The Data has been saved.'));
                        $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
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

                $sqlNews = array();
                $sqlOlds = '';
                $countSqlOlds = 0;

                $tablename = 'simiconnector/simibarcode';
                
                $results = $this->_objectManager->get('Simi\Simiconnector\Helper\Simibarcode')->getAllColumOfTable();

                $columns = array();
                $string = '';
                $type = '';
                
                foreach ($results as $result) {
                    $fields = explode('_', $result);
                    if ($fields[0] == 'barcode' || $fields[0] == 'qrcode')
                        continue;
                    foreach ($fields as $id => $field) {
                        if ($id == 0)
                            $type = $field;
                        if ($id == 1) {
                            $string = $field;
                        }
                        if ($id > 1)
                            $string = $string . '_' . $field;
                    }
                    $columns[] = array($type => $string);
                    $string = '';
                    $type = '';
                }

                if (isset($data['product_ids'])) {
                    $products = array();
                    $productsExplodes = explode(',', str_replace(' ', '', $data['product_ids']));
                    
                    if (count($productsExplodes)) {
                        $productIds = '';
                        $count = 0;
                        $j = 0;
                        $barcode = array();
                        $qrcode = array();
                        foreach ($productsExplodes as $pId) {
                            $codeArr = array();
                            //parse_str(base64_decode($enCoded), $codeArr);
                            //auto generate barcode
                            $codeArr['barcode'] = $this->checkDupplicate($barcode);
                            $barcode[] = $codeArr['barcode'];
                            //auto generate QRcode
                            $codeArr['qrcode'] = $this->checkDupplicateQrcode($qrcode);
                            $qrcode[] = $codeArr['qrcode'];
                            
                            $sqlNews[$j] = array(
                                'barcode' => $codeArr['barcode'],
                                'qrcode' => $codeArr['qrcode'],
                                'barcode_status' => 1,
                                    // 'barcode_status' => $codeArr['barcode_status'],
                            );
                            foreach ($columns as $id => $column) {
                                $i = 0;
                                $columnName = '';

                                foreach ($column as $_id => $key) {
                                    if ($i == 0)
                                        $columnName = $_id . '_' . $key;
                                    if ($i > 0)
                                        $columnName = $columnName . '_' . $key;

                                    $i++;
                                }

                                if ($_id != 'custom') {
                                    $return = $this->_objectManager->get('Simi\Simiconnector\Helper\Simibarcode')->getValueForBarcode($_id, $key, $pId, $codeArr);
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
                                    if (isset($codeArr[$columnName]))
                                        $sqlNews[$j][$columnName] = $codeArr[$columnName];
                                }
                            }
                            $sqlNews[$j]['created_date'] = $this->_objectManager->get('\Magento\Framework\Stdlib\DateTime\DateTimeFactory')->create()->gmtDate();
                            $j++;
                        }
                    }
                }
                if (!empty($sqlNews)) {
                    $resource = $this->_objectManager->create('Magento\Framework\App\ResourceConnection');
                    $resourceModel = $this->_objectManager->create('Simi\Simiconnector\Model\ResourceModel\Simibarcode');
                    $writeConnection = $resourceModel->getConnection();
                    $tablename = $resource->getTableName($resourceModel::TABLE_NAME);
                    
                    $writeConnection->insertMultiple($tablename, $sqlNews);
                }
                
                $this->_objectManager->create('Magento\Backend\Model\Session')->setData('barcode_product_import', null);

                if ($this->getRequest()->getParam('back')) {
                    $this->messageManager->addSuccess(__('Barcode was successfully saved.'));
                    $this->_redirect('*/*/new');
                    return;
                }
                $this->messageManager->addSuccess(__('Barcode was successfully saved.'));

                $this->_redirect('*/*');
                return;
            } catch (Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData($post);
                $this->_redirect('*/*/edit', array('banner_id' => $model->getId()));
                return;
            }
        }
        $this->_redirect('*/*/');
    }
    
    /**
     * check barcode dupplicate
     */
    public function checkDupplicate($barcode) {
        $code = $this->_objectManager->get('Simi\Simiconnector\Helper\Simibarcode')->generateCode($this->_objectManager->get('Simi\Simiconnector\Helper\Simibarcode')->getBarcodeConfig('pattern'));
        if (in_array($code, $barcode)) {
            $code = $this->checkDupplicate($barcode);
        }
        return $code;
    }

    /**
     * check QRcode dupplicate
     */
    public function checkDupplicateQrcode($qrcode) {
        $code = $this->_objectManager->get('Simi\Simiconnector\Helper\Simibarcode')->generateCode($this->_objectManager->get('Simi\Simiconnector\Helper\Simibarcode')->getBarcodeConfig('qrcode_pattern'));
        if (in_array($code, $qrcode)) {
            $code = $this->checkDupplicate($qrcode);
        }
        return $code;
    }

}

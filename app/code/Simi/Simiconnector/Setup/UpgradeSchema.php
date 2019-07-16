<?php

/**
 * Copyright © 2018 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        //handle all possible upgrade versions

        if(!$context->getVersion()) {
            //no previous version found, installation, InstallSchema was just executed
            //be careful, since everything below is true for installation !
        }

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            //code to upgrade to 1.0.1
        }

        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $tableName = $setup->getTable('simiconnector_transactions');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $setup->getConnection();
                $connection->addColumn(
                    $tableName,
                    'platform',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,'nullable' => false,
                        'default' => '0',
                        'COMMENT' => 'Order made from']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            $tableName = $setup->getTable('simiconnector_cms');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $setup->getConnection();
                $connection->addColumn(
                    $tableName,
                    'cms_script',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'default' => '',
                        'COMMENT' => 'Cms Script']
                );
                $connection->addColumn(
                    $tableName,
                    'cms_url',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'length' => 255,
                        'default' => '',
                        'COMMENT' => 'Cms Url']
                );
                $connection->addColumn(
                    $tableName,
                    'cms_meta_title',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'length' => 255,
                        'default' => '',
                        'COMMENT' => 'Cms Meta Title']
                );
                $connection->addColumn(
                    $tableName,
                    'cms_meta_desc',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'default' => '',
                        'COMMENT' => 'Cms Meta Description']
                );
            }
        }


        if (version_compare($context->getVersion(), '1.0.10') < 0) {
            $mappingTableName = $setup->getTable('simipwa_social_customer_mapping');
            if (!$setup->getConnection()->isTableExists($mappingTableName)) {
                $table_token = $setup->getConnection()->newTable(
                    $mappingTableName
                )->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Token Id'
                )->addColumn(
                    'customer_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false],
                    'Customer Id'
                )->addColumn(
                    'social_user_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'default' => ''],
                    'Social User Id'
                )->addColumn(
                    'provider_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'default' => ''],
                    'Provider Id'
                );
                $setup->getConnection()->createTable($table_token);
            }

            $tokenTableName = $setup->getTable('simiconnector_customer_token');
            if (!$setup->getConnection()->isTableExists($tokenTableName)) {
                $table_token = $setup->getConnection()->newTable(
                    $tokenTableName
                )->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Token Id'
                )->addColumn(
                    'customer_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false],
                    'Customer Id'
                )->addColumn(
                    'token',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'default' => ''],
                    'Token value'
                )->addColumn(
                    'created_time',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    255,
                    ['nullable' => true, 'default' => null],
                    'Created Time'
                );
                $setup->getConnection()->createTable($table_token);
            }
        }

        if (version_compare($context->getVersion(), '1.0.12') < 0) {
            $tableName = $setup->getTable('simiconnector_product_list');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $setup->getConnection();
                $connection->addColumn(
                    $tableName,
                    'category_id',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'nullable' => true,
                        'default' => 0,
                        'COMMENT' => 'Category Id']
                );
            }
        }


        $setup->endSetup();
    }
}
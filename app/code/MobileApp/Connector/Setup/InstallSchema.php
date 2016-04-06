<?php


namespace MobileApp\Connector\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $installer, ModuleContextInterface $context)
    {
        $installer = $installer;
        $installer->startSetup();

        /**
         * Creating table mobileapp_connector
         */
//        $table = $installer->getConnection()->newTable(
//            $installer->getTable('mobileapp_connector')
//        )->addColumn(
//            'connector_id',
//            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
//            null,
//            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
//            'Entity Id'
//        )->addColumn(
//            'title',
//            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
//            255,
//            ['nullable' => true],
//            'News Title'
//        )->addColumn(
//            'author',
//            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
//            255,
//            ['nullable' => true, 'default' => null],
//            'Author'
//        )->addColumn(
//            'content',
//            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
//            '2M',
//            ['nullable' => true, 'default' => null],
//            'Content'
//        )->addColumn(
//            'image',
//            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
//            null,
//            ['nullable' => true, 'default' => null],
//            'Connector image media path'
//        )->addColumn(
//            'created_at',
//            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
//            null,
//            ['nullable' => false],
//            'Created At'
//        )->addColumn(
//            'published_at',
//            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
//            null,
//            ['nullable' => true, 'default' => null],
//            'World publish date'
//        )->addIndex(
//            $installer->getIdxName(
//                'mobileapp_connector',
//                ['published_at'],
//                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
//            ),
//            ['published_at'],
//            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX]
//        )->setComment(
//            'Connector item'
//        );
//        $installer->getConnection()->createTable($table);

        /**
         * Creating table connector key
         */
        $table_key_name = $installer->getTable('connector_key');
        if ($installer->getConnection()->isTableExists($table_key_name) == true) {
            $installer->getConnection()->dropTable($installer->getConnection()->getTableName('connector_key'));
        }
        $table_key = $installer->getConnection()->newTable(
            $table_key_name
        )->addColumn(
            'key_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Key Id'
        )->addColumn(
            'key_secret',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Key Secret'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            10,
            ['nullable' => false],
            'Website Id'
        );
        $installer->getConnection()->createTable($table_key);
        // end create table connector key

        /**
         * Creating table connector notice
         */
        $table_notice_name = $installer->getTable('connector_notice');
        if ($installer->getConnection()->isTableExists($table_notice_name) == true) {
            $installer->getConnection()->dropTable($installer->getConnection()->getTableName('connector_notice'));
        }
        $table_notice = $installer->getConnection()->newTable(
            $table_notice_name
        )->addColumn(
            'notice_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Notice Id'
        )->addColumn(
            'notice_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Notice Title'
        )->addColumn(
            'notice_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Notice Url'
        )->addColumn(
            'notice_content',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true, 'default' => null],
            'Notice Content'
        )->addColumn(
            'notice_sanbox',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => true],
            'Notice Sanbox'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Website Id'
        )->addColumn(
            'device_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Device Id'
        )->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            5,
            ['nullable' => false],
            'Type'
        )->addColumn(
            'category_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Category Id'
        )->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Product Id'
        )->addColumn(
            'image_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Image Url'
        )
            ->addColumn(
                'latitude',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                30,
                ['nullable' => false],
                'Latitude'
            )->addColumn(
                'longitude',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                30,
                ['nullable' => false],
                'Longitude'
            )->addColumn(
                'address',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Address'
            )->addColumn(
                'city',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'City'
            )->addColumn(
                'country',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Country'
            )->addColumn(
                'zipcode',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                25,
                ['nullable' => false],
                'Zipcode'
            )->addColumn(
                'state',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                25,
                ['nullable' => false],
                'Zipcode'
            )->addColumn(
                'show_popup',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                255,
                ['nullable' => true, 'default' => null],
                'Show Popup'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false],
                'Created At'
            );
        $installer->getConnection()->createTable($table_notice);
        // end create table connector notice

        /**
         * Creating table connector app
         */

        $table_app_name = $installer->getTable('connector_app');
        if ($installer->getConnection()->isTableExists($table_app_name) == true) {
            $installer->getConnection()->dropTable($installer->getConnection()->getTableName('connector_app'));
        }
        $table_app = $installer->getConnection()->newTable(
            $table_app_name
        )->addColumn(
            'app_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'App Id'
        )->addColumn(
            'app_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'App Name'
        )->addColumn(
            'device_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Device Id'
        )->addColumn(
            'expired_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false],
            'Expired Time'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Status'
        )->addColumn(
            'categories',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Categories'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Website Id'
        );
        $installer->getConnection()->createTable($table_app);
        // end create table connector app

        /**
         * Creating table connector plugin
         */
        $table_plugin_name = $installer->getTable('connector_plugin');
        if ($installer->getConnection()->isTableExists($table_plugin_name) == true) {
            $installer->getConnection()->dropTable($installer->getConnection()->getTableName('connector_plugin'));
        }
        $table_plugin = $installer->getConnection()->newTable(
            $table_plugin_name
        )->addColumn(
            'plugin_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Plugin Id'
        )->addColumn(
            'plugin_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Plugin Name'
        )->addColumn(
            'plugin_version',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Plugin Version'
        )->addColumn(
            'plugin_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Plugin Code'
        )->addColumn(
            'expired_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false],
            'Expired Time'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Status'
        )->addColumn(
            'plugin_sku',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Plugin Sku'
        )->addColumn(
            'device_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Device'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Website Id'
        );
        $installer->getConnection()->createTable($table_plugin);
        // end create table connector plugin

        /**
         * Creating table connector design
         */
        $table_design_name = $installer->getTable('connector_design');
        if ($installer->getConnection()->isTableExists($table_design_name) == true) {
            $installer->getConnection()->dropTable($installer->getConnection()->getTableName('connector_design'));
        }
        $table_design = $installer->getConnection()->newTable(
            $table_design_name
        )->addColumn(
            'design_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Design Id'
        )->addColumn(
            'theme_color',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Theme Color'
        )->addColumn(
            'device_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Device Id'
        )->addColumn(
            'theme_logo',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Theme Logo'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Website Id'
        );
        $installer->getConnection()->createTable($table_design);
        // end create table connector design

        /**
         * Creating table connector device
         */
        $table_device_name = $installer->getTable('connector_device');
        if ($installer->getConnection()->isTableExists($table_device_name) == true) {
            $installer->getConnection()->dropTable($installer->getConnection()->getTableName('connector_device'));
        }
        $table_device = $installer->getConnection()->newTable(
            $table_device_name
        )->addColumn(
            'device_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Device Id'
        )->addColumn(
            'device_token',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Device Token'
        )->addColumn(
            'plaform_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Platform'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Website Id'
        )->addColumn(
            'device_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Device'
        )->addColumn(
            'latitude',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            30,
            ['nullable' => false],
            'Latitude'
        )->addColumn(
            'longitude',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            30,
            ['nullable' => false],
            'Longitude'
        )->addColumn(
            'address',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Address'
        )->addColumn(
            'city',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'City'
        )->addColumn(
            'country',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Country'
        )->addColumn(
            'zipcode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            25,
            ['nullable' => false],
            'Zipcode'
        )->addColumn(
            'state',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            25,
            ['nullable' => false],
            'Zipcode'
        )->addColumn(
            'created_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false],
            'Created Time'
        );
        $installer->getConnection()->createTable($table_device);
        // end create table connector device

        /**
         * Creating table connector design
         */
        $table_banner_name = $installer->getTable('connector_banner');
        if ($installer->getConnection()->isTableExists($table_banner_name) == true) {
            $installer->getConnection()->dropTable($installer->getConnection()->getTableName('connector_banner'));
        }
        $table_banner = $installer->getConnection()->newTable(
            $table_banner_name
        )->addColumn(
            'banner_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Banner Id'
        )->addColumn(
            'banner_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Banner Name'
        )->addColumn(
            'banner_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Banner Url'
        )->addColumn(
            'banner_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Banner Title'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Status'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Website Id'
        )->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'default' => 3],
            'Type'
        )->addColumn(
            'category_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Category'
        )->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Product'
        );
        $installer->getConnection()->createTable($table_banner);
        // end create table connector design

        /**
         * Creating table connector cms
         */
        $table_cms_name = $installer->getTable('connector_cms');
        if ($installer->getConnection()->isTableExists($table_cms_name) == true) {
            $installer->getConnection()->dropTable($installer->getConnection()->getTableName('connector_cms'));
        }
        $table_cms = $installer->getConnection()->newTable(
            $table_cms_name
        )->addColumn(
            'cms_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'CMS'
        )->addColumn(
            'cms_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'CMS Title'
        )->addColumn(
            'cms_image',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'CMS Image'
        )->addColumn(
            'cms_content',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'CMS Content'
        )->addColumn(
            'cms_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false],
            'CMS Status'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Website Id'
        );
        $installer->getConnection()->createTable($table_cms);
        // end create table connector cms

        $installer->endSetup();
    }
}
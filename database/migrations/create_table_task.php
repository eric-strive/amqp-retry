<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Migrations\Migration;

class CreateTableTask extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table = config('amqp_retry.task_table');
        $sql = <<<EFO
CREATE TABLE $table (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `key` varchar(64) COLLATE utf8mb4_bin NOT NULL DEFAULT '' COMMENT '消息的唯一key',
  `product_system` varchar(32) COLLATE utf8mb4_bin NOT NULL DEFAULT '' COMMENT '消息生产的系统',
  `exchange` varchar(48) COLLATE utf8mb4_bin NOT NULL DEFAULT '' COMMENT '交换器名称',
  `routing_key` varchar(32) COLLATE utf8mb4_bin DEFAULT NULL COMMENT '路由键',
  `request_data` text COLLATE utf8mb4_bin COMMENT '请求数据',
  `response_data` text COLLATE utf8mb4_bin COMMENT '响应数据',
  `status` enum('running','error','terminated','success') COLLATE utf8mb4_bin NOT NULL DEFAULT 'running' COMMENT '状态',
  `retry_times` tinyint(2) NOT NULL DEFAULT '0' COMMENT '重试次数',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key`) USING BTREE COMMENT '唯一key',
  KEY `idx_created_at` (`created_at`) USING BTREE COMMENT '创建时间',
  KEY `idx_exchange` (`exchange`,`routing_key`) USING BTREE COMMENT '交换器'
) ENGINE=InnoDB COMMENT='rabbitmq消费任务';
EFO;
        \Hyperf\DbConnection\Db::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_task');
    }
}

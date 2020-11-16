<?php

declare(strict_types=1);

/**
 * Amqp automatic retry configuration
 */
return [
    'retry_times'          => env('AMQP_RETRY_TIMES_LIMIT', 5),//自动重试次数
    'retry_times_interval' => (int)env('RETRY_TIMES_INTERVAL', 100),//每次重试间隔时间基数 间隔时间=retry_times*retry_times_interval
    'task_table'           => (int)env('RETRY_TASK_TABLE', 'task'),//amqp表名称
];

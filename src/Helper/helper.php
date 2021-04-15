<?php
declare(strict_types=1);

use Hyperf\Amqp\Exception\MessageException;
use Hyperf\Utils\ApplicationContext;

/**
 * amqp生产
 * $data:生产的数据
 * $key：该消息唯一标识
 */
if (!function_exists('amqpProducer')) {
    function amqpProducer($producerClass, $data, $key)
    {
        if (!class_exists($producerClass)) {
            throw new MessageException('对象不存在');
        }
        $obj      = new $producerClass($data, $key);
        $producer = ApplicationContext::getContainer()->get(\Hyperf\Amqp\Producer::class);

        return $producer->produce($obj);
    }
}

if (!function_exists('getUUID')) {
    function getUUID($prefix = '')
    {
        return $prefix . '_' . uniqid(date('YmdHis'), false);
    }
}

<?php
declare(strict_types=1);

namespace Eric\AmqpRetry\Amqp;

use Hyperf\Amqp\Builder\ExchangeBuilder;
use Hyperf\Amqp\Message\ProducerMessage;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * User: wangwei
 * Date: 2020/11/5
 * Time: 9:59 上午
 */
abstract class BaseProducer extends ProducerMessage
{
    protected $type = 'x-delayed-message';

    protected $delayType = "fanout";

    protected $argments = [];

    public function __construct($data, string $key, int $delay = 0, $poolName = null)
    {
        // 设置不同 pool
        $this->poolName = $poolName ?? 'default';

        if(empty($key)){
            throw new MessageException('队列Key不能为空');
        }
        $this->payload                           = [
            'key'            => $key,
            'product_system' => config('app_name'),
            'data'           => $data,
        ];
        $this->properties['application_headers'] = new AMQPTable(['x-delay' => $delay * 1000]);
        $this->properties['delivery_mode']       = AMQPMessage::DELIVERY_MODE_PERSISTENT;
    }

    public static function producerKey($keyPrefix)
    {
        return getUUID($keyPrefix);
    }

    public function getExchangeBuilder(): ExchangeBuilder
    {
        $this->argments = array_merge($this->argments, ['x-delayed-type' => $this->delayType]);

        return (new ExchangeBuilder())->setExchange($this->getExchange())
            ->setType($this->getType())
            ->setArguments(new AMQPTable($this->argments));
    }
}
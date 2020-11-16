<?php

declare(strict_types=1);

namespace Eric\AmqpRetry\Amqp\Producer;

use Eric\AmqpRetry\Amqp\BaseProducer;
use Hyperf\Amqp\Producer;
use Hyperf\Utils\ApplicationContext;

class DelayProducer extends BaseProducer
{
    public function __construct($exchangeName, $routingKey, $delay, $key, $poolName = null)
    {
        $this->exchange   = $exchangeName;
        $this->routingKey = $routingKey;
        parent::__construct([], $key, $delay, $poolName);
    }

    public function produce()
    {
        $producer = ApplicationContext::getContainer()->get(Producer::class);

        return $producer->produce($this);
    }
}

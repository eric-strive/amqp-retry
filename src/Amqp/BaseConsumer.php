<?php
declare(strict_types=1);

namespace Eric\AmqpRetry\Amqp;

use Eric\AmqpRetry\Utils\AmqpLock;
use Eric\AmqpRetry\Model\AmqpTask;
use Eric\AmqpRetry\Constants\AmqpRetry;
use Exception;
use Hyperf\Amqp\Builder\ExchangeBuilder;
use Hyperf\Amqp\Exception\MessageException;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Context;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Eric\AmqpRetry\Amqp\Producer\DelayProducer;

/**
 * Date: 2020/11/2
 * Time: 9:01 下午
 */
abstract class BaseConsumer extends ConsumerMessage
{
    public    $qos  = ['prefetch_count' => 1000, 'prefetch_size' => null];
    protected $type = 'x-delayed-message';

    protected $delayType = "fanout";

    protected $argments = [];

    /**
     * author wangwei
     *
     * @param $amqpData
     */
    private function beforeConsume($amqpData): void
    {
        try {
            $key  = $amqpData['key'];
            $task = AmqpTask::getInfoByKey($key);
            if (!$task) {
                $task = AmqpTask::Create([
                    'key'            => $key,
                    'exchange'       => $this->getExchange(),
                    'routing_key'    => $this->getRoutingKey(),
                    'product_system' => $amqpData['product_system'],
                    'request_data'   => json_encode($amqpData['data']),
                    'retry_times'    => 0,
                    'status'         => AmqpRetry::TASK_STATUS_RUNNING,
                ]);
            }
            ++$task->retry_times;
            Context::set(AmqpRetry::CONTEXT_TASK_DB_OBJ_KEY, $task);
        } catch (\Throwable $e) {
            logger()->error($e->getMessage());
        }
    }

    public function consumeMessage($amqpData, AMQPMessage $message): string
    {
        $redisLock = new AmqpLock();
        $key       = $amqpData['key'];
        $redisLock->precautionConcurrency($key, function () use ($amqpData) {
            $this->beforeConsume($amqpData);
            try {
                $task = Context::get(AmqpRetry::CONTEXT_TASK_DB_OBJ_KEY);
                if (empty($task)) {//防止task表操作失败
                    return Result::ACK;
                }
                if ($task->status === AmqpRetry::TASK_STATUS_SUCCESS) {
                    return Result::ACK;
                }
                $requestData = json_decode($task->request_data, true);
                $data   = $this->consume($requestData);
                $result = [
                    'code'    => AmqpRetry::AMQP_NO_ERROR_CODE,
                    'message' => 'consume success',
                    'data'    => $data,
                ];
            } catch (Exception $e) {
                $result = [
                    'code'    => AmqpRetry::AMQP_ERROR_CODE,
                    'message' => $e->getMessage(),
                ];
            }
            $this->afterConsume($result);
        });

        return Result::ACK;
    }

    /**
     * author wangwei
     *
     * @param $result
     */
    private function afterConsume($result)
    {
        try {
            /**
             * @var $taskModel \Eric\AmqpRetry\Model\AmqpTask
             */
            $task = Context::get(AmqpRetry::CONTEXT_TASK_DB_OBJ_KEY);
            if (empty($task)) {
                return false;
            }
            $task->status = $result['code'] === AmqpRetry::AMQP_NO_ERROR_CODE ? AmqpRetry::TASK_STATUS_SUCCESS : AmqpRetry::TASK_STATUS_ERROR;
            if ($task->retry_times >= config('amqp_retry.retry_times')) {
                $task->status = AmqpRetry::TASK_STATUS_TERMINATED;
            }
            $task->response_data = json_encode($result, JSON_UNESCAPED_UNICODE);
            $exchangeName        = $this->getExchange();
            $routingKeyName      = $this->getRoutingKey();
            if ($task->status === AmqpRetry::TASK_STATUS_ERROR) {
                $delayQueueObj = new DelayProducer($exchangeName, $routingKeyName,
                    $task->retry_times * config('amqp_retry.retry_times_interval'), $task->key);
                $delayResult   = $delayQueueObj->produce();
                if (!$delayResult) {
                    throw new MessageException('队列延时出错');
                }
            }
            $taskSave = $task->save();
            if ($taskSave === false) {
                throw new MessageException('队列延时出错');
            }
        } catch (Exception $e) {
            logger()->error($e->getMessage());
        }
    }

    public function getExchangeBuilder(): ExchangeBuilder
    {
        $this->argments = array_merge($this->argments, ['x-delayed-type' => $this->delayType]);

        return (new ExchangeBuilder())->setExchange($this->getExchange())
            ->setType($this->getType())
            ->setArguments(new AMQPTable($this->argments));
    }
}
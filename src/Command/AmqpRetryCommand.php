<?php
declare(strict_types=1);

namespace Eric\AmqpRetry\Command;

use Eric\AmqpRetry\Amqp\Producer\DelayProducer;
use Eric\AmqpRetry\Model\AmqpTask;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Utils\Parallel;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @Command
 */
class AmqpRetryCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('amqp:retry');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('amqp retry Command');
    }

    protected function getArguments()
    {
        return [
            ['exchange', InputArgument::OPTIONAL, '重试的交换器名称'],
            ['routing_key', InputArgument::OPTIONAL, '重试的路由键名称'],
        ];
    }

    public function handle()
    {
        $exchange   = $this->input->getArgument('exchange');
        $routingKey = $this->input->getArgument('routing_key');
        $parallel   = new Parallel();
        AmqpTask::getTasks($exchange, $routingKey)->orderBy('id')->chunk(100, function ($tasks) use ($parallel) {
            /**
             * @var $task \Eric\AmqpRetry\Model\AmqpTask
             */
            foreach ($tasks as $task) {
                $parallel->add(function () use ($task) {
                    $delayQueueObj = new DelayProducer($task->exchange, $task->routing_key, 0, $task->key);
                    $delayResult   = $delayQueueObj->produce();
                    if (!$delayResult) {
                        $this->error(sprintf('【%s】队列处理出错', $task->key));
                    }
                    $this->line(sprintf('【%s】队列处理成功', $task->key), 'info');
                }, $task->key);
            }
        });
        $parallel->wait();
        $this->line(sprintf('处理完成'), 'info');
    }
}

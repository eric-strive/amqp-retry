<?php
declare (strict_types=1);

namespace Eric\AmqpRetry\Model;

use Eric\AmqpRetry\Constants\AmqpRetry;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int            $id
 * @property string         $key
 * @property string         $exchange
 * @property string         $routing_key
 * @property string         $product_system
 * @property string         $request_data
 * @property string         $response_data
 * @property string         $status
 * @property int            $retry_times
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static Create(array $array)
 */
class AmqpTask extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('amqp_retry.task_table'));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['key', 'routing_key', 'exchange', 'product_system', 'request_data', 'retry_times', 'status'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id'          => 'integer',
        'retry_times' => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public static function getInfoByKey($key)
    {
        return self::query()->where('key', $key)->first();
    }

    public static function getTasks($exchange = null, $routingKey = null): \Hyperf\Database\Model\Builder
    {
        $query = self::query()->whereIn('status', [AmqpRetry::TASK_STATUS_ERROR, AmqpRetry::TASK_STATUS_TERMINATED]);
        if (!empty($exchange)) {
            $query->where('exchange', $exchange);
        }
        if (!empty($routingKey)) {
            $query->where('routing_key', $routingKey);
        }

        return $query;
    }
}
<?php

namespace Eric\AmqpRetry\Constants;

/**
 * User: wangwei
 * Date: 2020/11/4
 * Time: 5:08 下午
 */
class AmqpRetry
{
    public const CONTEXT_TASK_DB_OBJ_KEY = 'context_task_db_obj_key';//task对象Context

    //task状态
    public const TASK_STATUS_RUNNING    = 'running';
    public const TASK_STATUS_ERROR      = 'error';
    public const TASK_STATUS_TERMINATED = 'terminated';
    public const TASK_STATUS_SUCCESS    = 'success';

    public const AMQP_ERROR_CODE = 1;
    public const AMQP_NO_ERROR_CODE = 0;
}
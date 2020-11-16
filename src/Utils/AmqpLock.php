<?php

namespace Eric\AmqpRetry\Utils;

use App\Constants\ErrorCode;
use Exception;

/**
 * User: eric
 * Date: 2020/11/6
 * Time: 11:04 上午
 */
class AmqpLock
{
    private const REDIS_LOCK_KEY_DEFAULT_TEMP    = 'api:redis:lock:%s'; //redis database-3
    private const REDIS_LOCK_DEFAULT_EXPIRE_TIME = 600;                //default expire time 10 minute

    /**
     * Common lock
     *
     * @param string $key
     * @param int    $expire second
     *
     * @return bool
     */
    public function addLock($key, $expire = self::REDIS_LOCK_DEFAULT_EXPIRE_TIME)
    {
        if (empty($key) || (int)$expire <= 0) {
            return false;
        }

        $token    = self::generateToken();
        $cacheKey = $this->getCacheKey($key);
        $result   = redis()->set($cacheKey, $token, ['nx', 'ex' => $expire]);

        return $result ? $token : $result;
    }
    public function precautionConcurrency($key,
        $func = [],
        $params = [],
        $expire = self::REDIS_LOCK_DEFAULT_EXPIRE_TIME,
        $isReleaseLock = true): array
    {
        while (!$token = $this->addLock($key, $expire)) {
            sleep(1);
        }
        if (!empty($func)) {
            try {
                $result = call_user_func_array($func, $params);
            } catch (Exception $e) {
                return ['code' => ErrorCode::ERROR_CODE, 'message' => $e];
            } finally {
                if ($isReleaseLock) {
                    $this->releaseLock($key, $token);
                }
            }
        }

        return [
            'code'    => ErrorCode::NO_ERROR_CODE,
            'message' => '获取成功',
            'data'    => ['token' => $token, 'result' => $result ?? ''],
        ];
    }
    /**
     * @param $key
     * @param $token
     *
     * @return bool
     */
    public function releaseLock($key, $token): bool
    {
        if (empty($key) || empty($token)) {
            return false;
        }

        $cacheKey = $this->getCacheKey($key);

        //验证缓存值是否发生改变
        if ($token === redis()->get($cacheKey)) {
            redis()->del($cacheKey);

            return true;
        }

        return false;
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function getCacheKey($key): string
    {
        return sprintf(self::REDIS_LOCK_KEY_DEFAULT_TEMP, $key);
    }

    /**
     * 生成token,删除时候验证
     *
     * @return string
     */
    public static function generateToken(): string
    {
        [$t1, $t2] = explode(' ', microtime());
        $random = mt_rand(1000000, 9999999);

        return sprintf('%.0f', ((float)$t1 + (float)$t2)) . $random;
    }
}
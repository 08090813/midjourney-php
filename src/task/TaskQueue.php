<?php
namespace YcOpen\Midjourney\task;

class TaskQueue
{
    const POSITION_FIRST = 0;
    const POSITION_LAST = -1;
    private $redis = null;
    private $redisConfig = [];

    public function __construct(array $config)
    {
        $this->redisConfig = $config;
        if (!$this->redis) {
            $this->RedisConnection();
        }
    }

    # 链接Redis
    private function RedisConnection()
    {
        $config = $this->redisConfig['redis'];
        $this->redis = new \Redis;
        $this->redis->connect($config['host'], $config['port'], $config['db']);
    }

    /**
     * 获取队列头部元素，并删除
     * @param string $zset
     */
    public function zlPop($zset)
    {
        return $this->zsetPopCheck($zset, self::POSITION_FIRST);
    }

    /**
     * 获取队列头部元素，并删除
     * @param string $zset
     */
    public function zPop($zset)
    {
        return $this->zsetPop($zset, self::POSITION_FIRST);
    }

    /**
     * 获取队列尾部元素，并删除
     * @param string $zset
     */
    public function zRevPop($zset)
    {
        return $this->zsetPop($zset, self::POSITION_LAST);
    }

    /**
     * redis incr
     * @param string $key
     */
    public function incr($key)
    {
        try {
            return $this->redis->incr($key);
        }
        catch (\Exception $e) {
            $this->RedisConnection();
            return $this->redis->incr($key);
        }
    }

    /**
     *  redis del
     * @param string $key
     */
    public function del($key)
    {
        try {
            return $this->redis->del($key);
        }
        catch (\Exception $e) {
            $this->RedisConnection();
            return $this->redis->del($key);
        }
    }

    /**
     *   redis zAdd
     * @param string $key
     * @param int $source
     * @param string $value
     */
    public function zadd($key, $source, $value)
    {
        try {
            $this->redis->zadd($key, $source, $value);
        }
        catch (\Exception $e) {
            $this->RedisConnection();
            $this->redis->zadd($key, $source, $value);
        }
    }

    /**
     *  redis zRange
     * @param int $position
     * @param int $limit
     * @param string $value
     */
    public function zRange($zset, $position, $limit, $WITHSCORES = '')
    {
        try {
            $element = $this->redis->zRange($zset, $position, $limit);
        }
        catch (\Exception $e) {
            $this->RedisConnection();
            $element = $this->redis->zRange($zset, $position, $limit);
        }
        if (! isset($element[0])) {
            return null;
        }
        return $element;
    }

    /**
     * 模拟zset pop 
     * 方法1：使用watch监控key，获取元素 (轮询大大增加了时间消耗)
     * @param string $zset
     * @param int $position
     * @return string|json
     */
    private function zsetPop($zset, $position)
    {
        try {
            $this->redis->ping();
        }
        catch (\Exception $e) {
            $this->RedisConnection();
        }

        $redis = $this->redis;
        //乐观锁监控key是否变化
        $redis->watch($zset);
        $element = $redis->zRange($zset, $position, $position);
        if (! isset($element[0])) {
            return null;
        }
        $redis->multi();
        $redis->zRem($zset, $element[0]);
        if ($redis->exec()) {
            return $element[0];
        }
        //key发生变化，重新获取(轮询大大增加了时间消耗?)
        return $this->zsetPop($zset, $position);
    }

    /**
     * 模拟zset pop 避免元素竞争获取
     * 方法2：使用写入标记key，获取可用元素
     * @param string $zset
     * @param int $position
     * @return string|json
     */
    private function zsetPopCheck($zset, $position)
    {
        //get queue top 1
        //php7 不支持只获取 value 的zRange?
        try {
            $element = $this->redis->zRange($zset, $position, $position);
        }
        catch (\Exception $e) {
            $this->RedisConnection();
            $element = $this->redis->zRange($zset, $position, $position);
        }

        if (empty($element) || ! isset($element[0])) {
            return null;
        }
        //唯一key(可使用更严谨的生成规则，比如:redis的incr)
        $myCheckKey = (microtime(true) * 10000) . rand(1000, 9999);
        $k = $element[0] . '_check';
        $checkKey = $this->redis->get($k);

        if (empty($checkKey) || $myCheckKey == $checkKey) {
            $this->redis->setex($k, 10, $myCheckKey);
            $this->redis->watch($k); //监控锁
            $this->redis->multi();
            $this->redis->zRem($zset, $element[0]);
            if ($this->redis->exec()) {
                return $element[0];
            }
            //return null;
        }
        //重新获取（期待queue top1已消费,获取新的top1,或多个进程抢夺？）
        return $this->zsetPopCheck($zset, $position); //$position = 2
    }
}
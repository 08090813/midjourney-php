<?php
namespace YcOpen\Midjourney\redis;

class Redis
{
    /**
     * @var \Redis
     */
    private $redis = null;
    private $redisConfig = [];

    # 构造函数
    public function __construct(array $redisConfig)
    {
        $this->redis = $this->connect($redisConfig);
        $this->redisConfig = $redisConfig;
    }

    // 链接Redis
    private function connect(array $redisConfig)
    {
        $config = $redisConfig['redis'];
        $redis = new \Redis;
        $redis->connect($config['host'], $config['port'], $config['db']);
        return $redis;
    }

    // 获取redis
    public function getRedis()
    {
        if (!$this->redis) {
            return $this->connect($this->redisConfig);
        }
        return $this->redis;
    }
}
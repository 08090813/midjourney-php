<?php
namespace YcOpen\Midjourney\task;

use YcOpen\Midjourney\log\Log;
use YcOpen\Midjourney\redis\Redis;

class Task
{
    /**
     * @var \Redis
     */
    private $redis = null;
    private $redisConfig = [];

    # 构造函数
    public function __construct(array $redisConfig)
    {
        $this->redis = (new Redis($redisConfig))->getRedis();
        $this->redisConfig = $redisConfig;
    }

    /**
     * 将执行任务写入队列
     * @param string $queue func.php方法名
     * @param array $args	 参数(顺序array)
     * @return boolean
     */
    public function add(string $queue, array $args = [])
    {
        if (!$queue) {
            return false;
        }
        $data = serialize([
            'fun' => $queue,
            'args' => $args
        ]);

        # 投递队列
        $this->redis->lpush($this->redisConfig['queue_key'], $data);
        return true;
    }

    /**
     * 消费队列数据
     */
    public function digestion_queue_data()
    {
        $redisConfig = $this->redisConfig;
        while (true) {
            try {
                $data = $this->redis->lpop($redisConfig['queue_key']);
                if ($data) {
                    $data = unserialize($data);
                }
            } catch (\Throwable $e) {
                console($e->getMessage());
                Log::add($e->getMessage(),$redisConfig['error_logs']);
                return;
            }
            // 没有获取到队列数据，并且脚本不持续
            if (!$data && !$redisConfig['keep']) {
                break;
            }
            // 解析数据
            if (!isset($data['fun']) || empty($data['fun'])) {
                continue;
            }
            // 执行回调方法
            $callback = $this->call_func($data['fun'], $data['args']);
            if ($callback === false) {
                // 消费失败计数
                $again = intval($redisConfig['again']);
                // 执行次数未超额
                $fail = 0;
                if (!isset($data['args']['fail'])) {
                    $data['args']['fail'] = 0;
                }
                if ($again >= 0 && $again >= $fail) {
                    /**
                     * 消费失败 数据回归队列top100位置（头部/尾部/top2）
                     * 避免放回头部,如果重复消费失败 阻塞任务
                     */
                    $this->add($data['fun'],$data['args']);
                    // 设置日志
                    $msg = console("任务ID:{$data['args']['task_id']}---执行失败，当前重试次数{$data['args']['fail']}");
                    Log::add($msg,$redisConfig['error_logs']);
                }
            } else {
                $this->redis->set($callback['task_id'], serialize($callback));
                $msg = console("任务ID:{$callback['task_id']}---执行成功");
                Log::add($msg, $redisConfig['logs']);
            }
        }
    }

    /**
     * 执行方法
     * @param string $queue 方法名
     * @param array $args  方法参数
     * @return mixed|boolean 
     */
    public function call_func(string $queue, array $args = [])
    {
        $ex = [];
        try {
            $ex = explode('.', $queue);
            if (count($ex) > 1) {
                $class = new $ex[0];
                $method = $ex[1];
                return call_user_func([$class, $method], $args);
            }
            return call_user_func($queue, $args);
        } catch (\Exception $e) {
            Log::add($e->getMessage(), $this->redisConfig['error_logs']);
            return false;
        }
    }
}
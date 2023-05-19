<?php
/**
 * Created by 贵州猿创科技&轻创开发网
 * User: Coder - 程序员在囧途
 * DateTime: 2023/5/19 - 16:37
 * Email: 416716328@qq.com
 * desc: 使用PHP实现代理 MidJourney 的discord频道，用最简单的方式调用AI绘图
 */
class Proxy
{
    // 机器人账号池
    protected $bot = [
        ['guild_id' => '1108410963478196294', 'channel_id' => '1108410963478196297', 'user-token' => 'ODQ2NDU5NjU4Mjg5NTQ1MjE3.GuulSo.21tUKaxB1Bdsa7keGr8KXiDA0DdM1h7ev-BqwI', 'bot-token' => '']
    ];

    // 抓包请求参数
    protected $param_url = [
        'imagine' => "https://midjourney-1251511393.cos.ap-shanghai.myqcloud.com/json/imagine.json",
        'upscale' => 'https://midjourney-1251511393.cos.ap-shanghai.myqcloud.com/json/upscale.json',
        'variation' => 'https://midjourney-1251511393.cos.ap-shanghai.myqcloud.com/json/variation.json',
        'reset' => 'https://midjourney-1251511393.cos.ap-shanghai.myqcloud.com/json/reset.json',
        'describe' => 'https://midjourney-1251511393.cos.ap-shanghai.myqcloud.com/json/describe.json'
    ];

    /**
     * 提交绘画指令
     * @param $prompt 提示词
     * @return bool
     */
    public function imagine($prompt = "")
    {
        $param = file_get_contents($this->param_url['imagine']);
        ## 替换参数
        $param = str_replace("\$guild_id", $this->bot[0]['guild_id'], $param);
        $param = str_replace("\$channel_id", $this->bot[0]['channel_id'], $param);
        $task_id = $this->generateIDNumber();
        $finalPrompt = "[" . $task_id . "] " . $prompt;
        $param = str_replace("\$prompt", $finalPrompt, $param);
        $url = "https://discord.com/api/v9/interactions";
        $response = $this->http_request($url, $param, "POST", [
            'Content-Type: application/json', 'Authorization: ' . $this->bot[0]['user-token']
        ]);
        if (!$response) {
            ## 提交成功
            return json_encode(['code' => 200, 'msg=>' => "Prompt已提交", 'data' => ['prompt_id' => $task_id]]);
        }
        ## 提交失败
        return json_encode(['code' => 400, 'msg=>' => "Prompt提交失败"]);
    }

    /**
     * 获取指定图片 - 可配合Redis定时任务执行
     * @param $task_id 根据任务ID可以做到区分 - 自行扩展
     * @param $prompt
     * @return false|string
     */
    public function picture($task_id = "", $prompt = "")
    {
        $url = "https://discord.com/api/channels/" . $this->bot[0]['channel_id'] . "/messages";
        $response = $this->http_request($url, [], "GET", [
            'Content-Type: application/json', 'Authorization: ' . $this->bot[0]['user-token']
        ]);
        $response = json_decode($response, true);
        $message = [];
        foreach ($response as $key => $val) {
            ## 确保是同一条消息
            if (strpos($val['content'], $task_id) !== false && strpos($val['content'], $prompt)) {
                $message = $val;
                break;
            }
        }
        return json_encode(['code' => 200, 'msg=>' => "Midjourney绘图成功", 'data' => ['message' => $message]]);
    }

    /**
     * 操作图片 根据取到的图进行操作（U/V）   - 自行扩展
     * @param $message_id 消息ID
     * @param $name 例如：MJ::JOB::upsample::1::d0d171c7-6f40-4ebd-bc60-3e965570ec78
     * @return false|string
     */
    public function event($message_id, $name = "")
    {
        $param = file_get_contents($this->param_url['upscale']);
        ## 替换参数
        $param = str_replace("\$guild_id", $this->bot[0]['guild_id'], $param);
        $param = str_replace("\$channel_id", $this->bot[0]['channel_id'], $param);
        $param = str_replace("\$message_id", "1109010718537297970", $param);
        $param = str_replace("\$custom_id", $name, $param);
        $url = "https://discord.com/api/v9/interactions";
        $response = $this->http_request($url, $param, "POST", [
            'Content-Type: application/json', 'Authorization: ' . $this->bot[0]['user-token']
        ]);
        if (!$response) {
            ## 提交成功
            return json_encode(['code' => 200, 'msg=>' => "事件已提交", 'data' => []]);
        }
        ## 提交失败
        return json_encode(['code' => 400, 'msg=>' => "事件提交失败"]);
    }

    /**
     * CURL请求
     * @param $url 请求地址
     * @param $data 请求参数
     * @param $header header
     * @return bool|string
     */
    public function http_request($url, $data, $method = "POST", $header = [])
    {
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => $method == "POST" ? true : false,
            CURLOPT_HTTPHEADER => $header,
        ];
        $data && $options[CURLOPT_POSTFIELDS] = $data;
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    /**
     * 生成指定长度的数字
     * @param $length
     * @return string
     */
    public function generateIDNumber($length = 16)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $IDNumber = '';
        for ($i = 0; $i < $length; $i++) {
            $IDNumber .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $IDNumber;
    }
}
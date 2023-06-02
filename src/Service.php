<?php

namespace YcOpen\Midjourney;

use Exception;
use GuzzleHttp\Client;

/**
 * 服务操作类
 * 后续增加
 * redis队列处理，异步任务，回调通知，日志系统
 */
class Service
{
    private const API_URL = 'https://discord.com/api/v9';

    private const APPLICATION_ID = '936929561302675456';

    private const DATA_ID = '938956540159881230';

    private const DATA_VERSION = '1077969938624553050';

    private const SESSION_ID = '2fb980f65e5c9a77c96ca01f2c242cf6';

    /**
     * @var Client
     */
    protected $client;

    protected $channel_id;

    protected $oauth_token;
    protected $timeout;

    protected $guild_id;

    protected $user_id;
    protected $notify = '';

    # 构造函数
    public function __construct(array $config)
    {
        if (!isset($config['channel_id']) && !$config['channel_id']) {
            throw new Exception('请提供频道ID');
        }
        if (!isset($config['oauth_token']) && !$config['oauth_token']) {
            throw new Exception('请提供用户授权token');
        }
        if (!isset($config['timeout']) && !$config['timeout']) {
            throw new Exception('请提供超时时间');
        }
        # 频道ID
        $this->channel_id = $config['channel_id'];
        # 授权TOKEN
        $this->oauth_token = $config['oauth_token'];
        # 超时时间
        $this->timeout = $config['timeout'];

        # 实例请求类
        $this->client = new Client([
            'base_uri' => self::API_URL,
            'headers' => [
                'Authorization' => $this->oauth_token
            ]
        ]);

        # 获取服务器ID
        $request = $this->client->get('channels/' . $this->channel_id);
        $response = json_decode((string) $request->getBody(), true);

        # 服务器ID
        $this->guild_id = $response['guild_id'];

        # 获取用户ID
        $request = $this->client->get('users/@me');
        $response = json_decode((string) $request->getBody(), true);
        $this->user_id = $response['id'];
    }

    # 获取关键词图片
    public function imagine(string $prompt)
    {
        # 准备参数
        $params = [
            'type' => 2,
            'application_id' => self::APPLICATION_ID,
            'guild_id' => $this->guild_id,
            'channel_id' => $this->channel_id,
            'session_id' => self::SESSION_ID,
            'data' => [
                'version' => self::DATA_VERSION,
                'id' => self::DATA_ID,
                'name' => 'imagine',
                'type' => 1,
                'options' => [
                    [
                        'type' => 3,
                        'name' => 'prompt',
                        'value' => $prompt
                    ]
                ],
                'application_command' => [
                    'id' => self::DATA_ID,
                    'application_id' => self::APPLICATION_ID,
                    'version' => self::DATA_VERSION,
                    'default_permission' => true,
                    'default_member_permissions' => '',
                    'type' => 1,
                    'nsfw' => false,
                    'name' => 'imagine',
                    'description' => 'Create images with Midjourney',
                    'dm_permission' => true,
                    'options' => [
                        [
                            'type' => 3,
                            'name' => 'prompt',
                            'description' => 'The prompt to imagine',
                            'required' => true
                        ]
                    ]
                ],
                'attachments' => []
            ]
        ];
        $data = [
            'json' => $params
        ];
        $response = $this->client->post('interactions', $data);
        if ($response->getStatusCode() !== 204) {
            throw new Exception('投递图片失败');
        }
        # 获取图片
        $imagineMessage = null;
        $timeout = $this->timeout;
        while (is_null($imagineMessage)) {
            $imagineMessage = $this->getImagine($prompt);
            if (is_null($imagineMessage)) {
                sleep(2);
                $timeout = $timeout-2;
            }
            if ($timeout <= 0) {
                throw new Exception('没有获取到数据');
            }
        }
        return $imagineMessage;
    }

    # 获取图片
    private function getImagine(string $prompt)
    {
        $response = $this->client->get('channels/' . $this->channel_id . '/messages');
        $response = json_decode((string) $response->getBody());

        $raw_message = self::firstWhere($response, function ($item) use ($prompt) {
            return (
                str_starts_with($item->content, "**{$prompt}** - <@" . $this->user_id . '>') and
                !str_contains($item->content, '%') and
                str_ends_with($item->content, '(fast)')
            );
        });

        if (is_null($raw_message)) return null;

        return (object) [
            'id'          => $raw_message->id,
            'prompt'      => $prompt,
            'raw_message' => $raw_message
        ];
    }

    
    public function generate(string $prompt, int $upscale_index = 0)
    {
        $imagine = $this->imagine($prompt);
        $upscaled_photo_url = $this->upscale($imagine, $upscale_index);

        return (object) [
            'imagine_message_id' => $imagine->id,
            'upscaled_photo_url' => $upscaled_photo_url
        ];
    }

    # U操作（异步任务投递）
    public function upscale($message, int $upscale_index = 0)
    {
        if (! property_exists($message, 'raw_message')) {
            throw new Exception('Upscale需要从imagine/getImagine方法获得一个消息对象');
        }

        if ($upscale_index < 0 or $upscale_index > 3) {
            throw new Exception('上限索引必须是0和3之间');
        }

        $upscale_hash = null;
        $raw_message = $message->raw_message;

        if (property_exists($raw_message, 'components') and is_array($raw_message->components)) {
            $upscales = $raw_message->components[0]->components;

            $upscale_hash = $upscales[$upscale_index]->custom_id;
        }

        $params = [
            'type'           => 3,
            'guild_id'       => self::$guild_id,
            'channel_id'     => self::$channel_id,
            'message_flags'  => 0,
            'message_id'     => $message->id,
            'application_id' => self::APPLICATION_ID,
            'session_id'     => self::SESSION_ID,
            'data'           => [
                'component_type' => 2,
                'custom_id'      => $upscale_hash
            ]
        ];

        $this->client->post('interactions', [
            'json' => $params
        ]);

        $upscaled_photo_url = null;
        while (is_null($upscaled_photo_url)) {
            $upscaled_photo_url = $this->getUpscale($message, $upscale_index);
            if (is_null($upscaled_photo_url)) sleep(3);
        }

        return $upscaled_photo_url;
    }

    # 获取U操作后续（异步任务消费）
    private function getUpscale($message, $upscale_index = 0)
    {
        if (! property_exists($message, 'raw_message')) {
            throw new Exception('Upscale需要从imagine/getImagine方法获得一个消息对象');
        }

        if ($upscale_index < 0 or $upscale_index > 3) {
            throw new Exception('上限索引必须是0和3之间');
        }

        $prompt = $message->prompt;

        $response = self::$client->get('channels/' . self::$channel_id . '/messages');
        $response = json_decode((string) $response->getBody());

        $message_index = $upscale_index + 1;
        $message = self::firstWhere($response, 'content', "**{$prompt}** - Image #{$message_index} <@" . self::$user_id . '>');

        if (is_null($message)) {
            $message = self::firstWhere($response, 'content', "**{$prompt}** - Upscaled by <@" . self::$user_id . '> (fast)');
        }

        if (is_null($message)) return null;

        if (property_exists($message, 'attachments') and is_array($message->attachments)) {
            $attachment = $message->attachments[0];

            return $attachment->url;
        }

        return null;
    }

    # 获取查询
    protected function firstWhere($array, $key, $value = null)
    {
        foreach ($array as $item) {
            if (
                (is_callable($key) and $key($item)) or
                (is_string($key) and str_starts_with($item->{$key}, $value))
            ) {
                return $item;
            }
        }
        return null;
    }
}
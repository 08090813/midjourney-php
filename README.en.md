# midjourney

### Description
midjourney开源包

### 安装方式
你可以使用Composer安装此库，在项目目录中运行以下命令：
```
composer require yc-open/midjourney
```

### 使用方式
要使用midjourney生成图像，首先需要创建Midjourney类的实例：
```
<?php

include 'vendor/autoload.php';

use YcOpen\Midjourney\Service;

# 频道ID
$discord_channel_id = '';
# 用户TOKEN
$discord_user_token = '';
$config = [
    'channel_id' => $discord_channel_id,
    'oauth_token' => $discord_user_token,
    'timeout'=> 30, # 超时时间
];

$midjourney = new Service($config);
$response = $midjourney->imagine('Pink Panda');
print_r($response);
```
### 参数1：$channel_id
将此值替换为安装Midjourney的频道ID，右键单击频道可以获得频道ID，然后复制频道ID。
请记住，你可以邀请中途机器人到你自己的服务器来工作
https://docs.midjourney.com/docs/invite-the-bot

### 参数2：$oauth_token
Discord不允许使用自动用户帐户（self-bots），如果发现，可能会导致帐户终止，因此使用该帐户的风险自负。
要获取用户令牌，请访问 https://discord.com/channels/@me ，然后打开“开发人员工具”中的“网络”选项卡。在请求之间查找授权标头。

### 请求方法
#### 本方法使用提示生成图像，并返回表示包含生成图像的消息的对象。$prompt参数是一个将用于生成图像的字符串。
$midjourney->imagine($prompt)

#### 本方法放大给定对象中包含的图像，并返回放大图像的URL。$imagine_object参数是从imagine/getImagine方法返回的对象。$upscale_index参数是一个介于0和3之间的整数，表示我们要升级的MJ机器人提供的选项。
$midjourney->upscale($imagine_object,$upscale_index)
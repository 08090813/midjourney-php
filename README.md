# PHP-midjourney-proxy

使用PHP实现代理 MidJourney 的discord频道，用最简单的方式调用AI绘图。

前段时间四处寻找midjourney的绘画接口，交了好几万的学费，现在我终于自己写出来了，而且还开源出来且是PHP版本。

PHP降低了门槛，过一段时间就会全网沸腾。。。

本项目仅实现基本思路，全网第一个PHP版本实现的Midjourney-proxy，如果觉得对你有帮助，给个star。

## 现有功能
- [x] 支持 Imagine 绘图
- [x] 支持 V 事件
- [x] 支持 U 事件
- [x] 支持频道消息 - 图片回调可以通过这个实现

## 后续计划
- [ ] 基于目前实现的逻辑做完整的接口开放平台
- [ ] 基于开放平台做完整的AI绘画产品
- [ ] 接入ChatGPT以及敏感词过滤等
- [ ] 支持Composer包一键安装使用
- [ ] 支持更多Midjourney绘画命令


## 注意事项
1. 本项目仅作为参考示例，已经跑通整个流程
2. 因市面上暂无Midjourney的开源代理项目，所以参考了Github的Java项目改成了PHP版本的
3. Java版项目地址：https://github.com/novicezk/midjourney-proxy
4. 本项目仅作为研究交流使用，如果有借鉴本项目思路或代码，还请给个链接，尊重下劳动成果，谢谢。

## 作者微信

 <img src="https://midjourney-1251511393.cos.ap-shanghai.myqcloud.com/json/%E5%BE%AE%E4%BF%A1%E5%9B%BE%E7%89%87_20230519183549.jpg" width = "300" height = "300" alt="交流群二维码" align=center />

## 应用项目

- [ChatGPT分销版](https://mp.weixin.qq.com/s/nzagBys82hskb9cM0YR4Mg) : 目前市场占有率90%，日充值10w+的产品。
- [超级SEO助手](https://mp.weixin.qq.com/s/aH0_sFNA-je6_UGJJRWgxg) : GPT和SEO的最强组合，批量化生成，自动化运行。
- [Midjourney开放平台](http://kfadmin.net) : 基于此包实现的接口开放平台，Saas架构，目前暂无线上演示。

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
    'timeout'=> 30, # 超时时间[README.md](..%2F..%2FPHP-midjourney-proxy%2FREADME.md)
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
本方法使用提示生成图像，并返回表示包含生成图像的消息的对象。$prompt参数是一个将用于生成图像的字符串。
```
$midjourney->imagine($prompt)
```

本方法放大给定对象中包含的图像，并返回放大图像的URL。$imagine_object参数是从imagine/getImagine方法返回的对象。$upscale_index参数是一个介于0和3之间的整数，表示我们要升级的MJ机器人提供的选项。
```
$midjourney->upscale($imagine_object,$upscale_index)
```
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

## 使用前提
1. 科学上网
2. PHP+Nignx+Mysql环境

## 注意事项
1. 本项目仅作为参考示例，已经跑通整个流程
2. 因市面上暂无Midjourney的开源代理项目，所以参考了Github的Java项目改成了PHP版本的
3. Java版项目地址：https://github.com/novicezk/midjourney-proxy
4. 本项目仅作为研究交流使用，如果有借鉴本项目思路或代码，还请给个链接，尊重下劳动成果，谢谢。

## 配置项

| 变量名 | 非空 | 描述 |
| :-----| :----: | :---- |
| guild_id | 是 | discord服务器ID |
| channel_id | 是 | discord频道ID |
| user-token | 是 | discord用户Token |
| bot-token | 是 | 自定义机器人Token |

## 作者微信

 <img src="https://midjourney-1251511393.cos.ap-shanghai.myqcloud.com/json/%E5%BE%AE%E4%BF%A1%E5%9B%BE%E7%89%87_20230519183549.jpg" width = "300" height = "300" alt="交流群二维码" align=center />

## 应用项目

- [ChatGPT分销版](https://mp.weixin.qq.com/s/nzagBys82hskb9cM0YR4Mg) : 目前市场占有率90%，日充值10w+的产品。
- [超级SEO助手](https://mp.weixin.qq.com/s/aH0_sFNA-je6_UGJJRWgxg) : GPT和SEO的最强组合，批量化生成，自动化运行。
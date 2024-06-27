# IYUUPlus开发版

<a href="https://item.jd.com/100058776147.html" target="_blank"><img src="https://iyuu-1251099245.cos.ap-chengdu.myqcloud.com/zspace-01.jpg" width="1080"></a>

# 介绍

IYUUPlus项目运行在php-cli模式，常驻内存运行；集成webui界面、辅种、转移、下载、定时访问URL、动态域名ddns等常用功能，提供完善的插件机制。

IYUUPlus客户端完全开源，行为透明，安全可靠；根据源码可以自由定制。

IYUU自动辅种工具，目前能对国内大部分的PT站点自动辅种，支持下载器集群，支持多盘位，支持多下载目录，支持连接远程下载器等。

# 免责声明

在使用本工具前，请认真阅读《免责声明》全文如下：

使用IYUUAutoReseed或IYUUPlus自动辅种工具本身是非常安全的，IYUU脚本辅种时不会跟PT站点的服务器产生任何交互，只是会把下载种子链接推送给下载器，由下载器去站点下载种子。理论上，任何站点、任何技术都无法检测你是否使用了IYUUAutoReseed。危险来自于包括但不限于以下几点：

第一：建议不要自己手动跳校验，任何因为跳校验ban号，跟IYUU无关；

第二：官方首发资源、其他一切首发资源的种子，IYUUAutoReseed自动辅种工具也无法在出种前辅种，如果因为你个人的作弊而被ban号，跟IYUU无关；

第三：您使用IYUU工具造成的一切损失，与IYUU无关。如不接受此条款，请不要使用IYUUAutoReseed，并立刻删除已经下载的源码。

# 原理

IYUU自动辅种工具（英文名：IYUUAutoReseed），是一款PHP语言编写的Private
Tracker辅种脚本，通过计划任务或常驻内存，按指定频率调用transmission、qBittorrent下载软件的API接口，提取正在做种的info_hash提交到辅种服务器API接口（辅种过程和PT站没有交互），根据API接口返回的数据拼接种子连接，提交给下载器，由下载器主动去站点下载种子、校验、做种，自动辅种各个站点。

# 使用文档

[http://doc.iyuu.cn](http://doc.iyuu.cn)

## 运行要求最低PHP版本：v8.3.0

推荐使用最新稳定版

**必须开启的扩展**

```config
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=exif
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=pdo_sqlite
extension=sockets
extension=sodium
extension=sqlite3
extension=zip
```

# 技术栈

| 组件            | 版本     | 官网                                          |
|---------------|--------|---------------------------------------------|
| Workerman版本   | 4.1.15 | https://www.workerman.net/doc/workerman/    |
| Webman版本      | 1.5.16 | https://www.workerman.net/doc/webman/       |
| WebmanAdmin版本 | 0.6.20 | https://www.workerman.net/doc/webman-admin/ |
| PHP版本         | 8.3.0  | https://www.php.net/                        |
| MYSQL版本       | 5.7.26 | https://www.mysql.com/                      |
| Layui         | 2.8.12 | https://layui.dev/                          |
| Vue           | 3.4.21 | https://vuejs.org/                          |

# 支持的下载器

1. transmission
2. qBittorrent

# 版本发布页

国内：https://gitee.com/ledc/iyuuplus-dev/tags

国际：https://github.com/ledccn/iyuuplus-dev/tags

# nginx反向代理配置

```conf
location ^~ / {
  proxy_set_header X-Real-IP $remote_addr;
  proxy_set_header Host $host;
  proxy_set_header X-Forwarded-Proto $scheme;
  proxy_http_version 1.1;
  proxy_set_header Connection "";
  if (!-f $request_filename){
    proxy_pass http://127.0.0.1:8787;
  }
}

location /app/d9422b72cffad23098ad301eea0f8419
{
  proxy_pass http://127.0.0.1:3131;
  proxy_http_version 1.1;
  proxy_set_header Upgrade $http_upgrade;
  proxy_set_header Connection "Upgrade";
  proxy_set_header X-Real-IP $remote_addr;
}
```

# 需求提交/错误反馈

- QQ群：859882209[2000人]，41477250[1000人]，924099912[2000人]
- issues： https://github.com/ledccn/iyuuplus-dev/issues
- 博客：https://www.iyuu.cn/

# 接口开发文档

实时更新的接口文档：http://doc.iyuu.cn
如果您懂得其他语言的开发，可以基于接口做成任何您喜欢的样子，比如手机APP，二进制包，Windows的GUI程序，浏览器插件等。欢迎分享您的作品！

# 感谢贡献者

- https://github.com/hxsf
- https://github.com/DDS-Derek
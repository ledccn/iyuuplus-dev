# IYUUPlus开发版

## 要求最低PHP版本：v8.3.0

推荐使用最新稳定版

### 必须开启的扩展

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

## 技术栈

| 组件            | 版本     | 官网                                          |
|---------------|--------|---------------------------------------------|
| Workerman版本   | 4.1.15 | https://www.workerman.net/doc/workerman/    |
| Webman版本      | 1.5.16 | https://www.workerman.net/doc/webman/       |
| WebmanAdmin版本 | 0.6.20 | https://www.workerman.net/doc/webman-admin/ |
| PHP版本         | 8.3.0  | https://www.php.net/                        |
| MYSQL版本       | 5.7.26 | https://www.mysql.com/                      |
| Layui         | 2.8.12 | https://layui.dev/                          |
| Vue           | 3.4.21 | https://vuejs.org/                          |

## 支持情况

```shell
+------+--------------+------+---------+----------------+--------------+--------------------------------------------+
| 序号 | 站点名称     | 爬虫 | RSS订阅 | 下载种子元数据 | 拼接种子链接 | 类名                                       |
+------+--------------+------+---------+----------------+--------------+--------------------------------------------+
| 1    | 1ptba        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\Driver1ptba        |
| 2    | 52pt         | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\Driver52pt         |
| 3    | audiences    | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverAudiences    |
| 4    | beitai       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverBeitai       |
| 5    | btschool     | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverBtschool     |
| 6    | carpt        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverCarpt        |
| 7    | chdbits      | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverChdbits      |
| 8    | cyanbug      | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverCyanbug      |
| 9    | dajiao       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverDajiao       |
| 10   | discfan      | Yes  |         | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverDiscfan      |
| 11   | dragonhd     | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverDragonhd     |
| 12   | eastgame     | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverEastgame     |
| 13   | haidan       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHaidan       |
| 14   | hares        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHares        |
| 15   | hd4fans      | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHd4fans      |
| 16   | hdatmos      | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHdatmos      |
| 17   | hdcity       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHdcity       |
| 18   | hdfans       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHdfans       |
| 19   | hdhome       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHdhome       |
| 20   | hdmayi       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHdmayi       |
| 21   | hdpost       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHdpost       |
| 22   | hdpt         | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHdpt         |
| 23   | hdsky        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHdsky        |
| 24   | hdtime       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHdtime       |
| 25   | hdvideo      | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHdvideo      |
| 26   | hhanclub     | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHhanclub     |
| 27   | hitpt        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHitpt        |
| 28   | hudbt        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverHudbt        |
| 29   | joyhd        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverJoyhd        |
| 30   | keepfrds     | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverKeepfrds     |
| 31   | m-team       |      | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverMTeam        |
| 32   | monikadesign | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverMonikadesign |
| 33   | nanyangpt    | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverNanyangpt    |
| 34   | nicept       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverNicept       |
| 35   | opencd       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverOpencd       |
| 36   | oshen        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverOshen        |
| 37   | ourbits      | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverOurbits      |
| 38   | pandapt      | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverPandapt      |
| 39   | piggo        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverPiggo        |
| 40   | pt0ffcc      | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverPt0ffcc      |
| 41   | pt2xfree     | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverPt2xfree     |
| 42   | ptchina      | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverPtchina      |
| 43   | pter         | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverPter         |
| 44   | pthome       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverPthome       |
| 45   | ptlsp        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverPtlsp        |
| 46   | ptsbao       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverPtsbao       |
| 47   | pttime       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverPttime       |
| 48   | qhstudio     | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverQhstudio     |
| 49   | redleaves    | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverRedleaves    |
| 50   | rousi        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverRousi        |
| 51   | sharkpt      | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverSharkpt      |
| 52   | soulvoice    | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverSoulvoice    |
| 53   | ssd          | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverSsd          |
| 54   | tjupt        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverTjupt        |
| 55   | torrentccf   | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverTorrentccf   |
| 56   | ttg          | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverTtg          |
| 57   | ubits        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverUbits        |
| 58   | upxin        | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverUpxin        |
| 59   | wintersakura | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverWintersakura |
| 60   | zhuque       | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverZhuque       |
| 61   | zmpt         | Yes  | Yes     | Yes            | Yes          | Iyuu\SiteManager\Driver\DriverZmpt         |
+------+--------------+------+---------+----------------+--------------+--------------------------------------------+
```

## 版本发布页

### 国内：https://gitee.com/ledc/iyuuplus-dev/tags

### 国际：https://github.com/ledccn/iyuuplus-dev/tags

## 使用

#### 1.拉取代码

```shell
git clone https://gitee.com/ledc/iyuuplus-dev.git
```

#### 2.运行

Windows系统双击`windows.bat`运行；
其他系统`php start.php restart -d`

#### 3.安装数据库

浏览器访问 http://127.0.0.1:8787
填写数据库信息、爱语飞飞Token、设置管理员账号

#### 4.重启系统

Windows系统双击`windows.bat`运行；
其他系统`php start.php restart -d`

#### 5.登录后台

浏览器访问：http://127.0.0.1:8787/app/admin

首次登录，会自动安装计划任务、管理中心等

#### 6.重启系统

Windows系统双击`windows.bat`运行；
其他系统`php start.php restart -d`

## 感谢贡献者

- https://github.com/hxsf
- https://github.com/DDS-Derek
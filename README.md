# Rpc Service 搭建说明文档

最新文档说明：https://github.com/thefair-net/thefairlib

[TOC]

## 安装

仅可运行于 Linux 和 Mac 环境下，Windows 下也可以通过 Docker for Windows 来作为运行环境或虚拟机，通常来说 Mac 环境下，推荐本地环境部署

* PHP >= 7.3
* Swoole PHP 扩展 >= 4.5，并关闭了 Short Name
* OpenSSL PHP 扩展
* JSON PHP 扩展
* PDO PHP 扩展 （如需要使用到 MySQL 客户端）
* Redis PHP 扩展 （如需要使用到 Redis 客户端）
* Protobuf PHP 扩展 （如需要使用到 gRPC 服务端或客户端）

`composer create-project lmz/xxx-skeleton test_service`

复制项目中的 `.env.example` 为 `.env`
本地配置文件 `.env`

安装包 `composer up`

生产环境`composer dump-autoload -o`


## 项目文件结构

```
├── app
│   ├── Constants   // 常量约定
│   ├── Contract    // 接口
│   ├── Controller  // 控制器
│   ├── Event       // 事件
│   ├── Exception   // 异常处理
│   │   └── Handler // Error 监听器
│   ├── Job         // 异步任务处理
│   ├── Library     // 系统自定义库
│   ├── Middleware  // 中间件
│   ├── Model       // 数据模型，只处理数据库、缓存相关
│   ├── Process     // 多进程管理
│   ├── Request     // 参数约定
│   ├── Server      // 系统核心服务
│   └── Service     // 业务处理
├── listener    // 事件监听器
├── bin
│   └── hyperf.php  // 架构启动文件
├── composer.json
├── composer.lock
├── config          // 配置文件
├── docs            // 文档
│   └── sql
├── openapi.yaml    // 自动生成文档
├── phpunit.xml     // 单元测试
├── runtime         // 日志目录
│   ├── container
│   ├── hyperf.pid
│   └── logs
├── test            // 测试
│   ├── Cases
│   ├── HttpTestCase.php
│   └── bootstrap.php
├── Jenkinsfile     // 自动化测试
├── README.md       // 说明文档
├── .env            // 开发配置
├── .env.example    // demo 开发配置文件
├── .editorconfig   // 编辑器参数约定
├── README.md       // 说明文档
└── watch.php       // 本地开发热更新文件
```

### 配置文件结构

```
config
├── autoload // 此文件夹内的配置文件会被配置组件自己加载，并以文件夹内的文件名作为第一个键值
│   ├── amqp.php  // 用于管理 AMQP 组件
│   ├── annotations.php // 用于管理注解
│   ├── aspects.php // 用于管理 AOP 切面
│   ├── auth // 系统白单名
│   ├── async_queue.php // 用于管理基于 Redis 实现的简易队列服务
│   ├── cache.php // 用于管理缓存组件
│   ├── commands.php // 用于管理自定义命令
│   ├── consul.php // 用于管理 Consul 客户端
│   ├── databases.php // 用于管理数据库客户端
│   ├── devtool.php // 用于管理开发者工具
│   ├── exceptions.php // 用于管理异常处理器
│   ├── elastic.php // 用于管理 elasticsearch 客户端
│   ├── listeners.php // 用于管理事件监听者
│   ├── lock.php // 分布式锁
│   ├── logger.php // 用于管理日志
│   ├── middlewares.php // 用于管理中间件
│   ├── opentracing.php // 用于管理调用链追踪
│   ├── processes.php // 用于管理自定义进程
│   ├── redis.php // 服务限流
│   ├── rate_limit.php // 用于管理 Redis 客户端
│   ├── translation.php // 国际化配置
│   ├── validation.php // 参数自动验证
│   ├── rate_limit.php // 用于管理 Redis 客户端
│   └── server.php // 用于管理 Server 服务
├── config.php // 用于管理用户或框架的配置，如配置相对独立亦可放于 autoload 文件夹内
├── container.php // 负责容器的初始化，作为一个配置文件运行并最终返回一个 Psr\Container\ContainerInterface 对象
├── dependencies.php // 用于管理 DI 的依赖关系和类对应关系
│   ├── i18n    // 国际化具体信息
│   │   └── languages
└── routes.php // 用于管理路由
```

## 开发必读

### 不能通过全局变量获取属性参数

在 `PHP-FPM` 下可以通过全局变量获取到请求的参数，服务器的参数等，在 `Hyperf` 和 `Swoole` 内，都 无法 通过 `$_GET/$_POST/$_REQUEST/$_SESSION/$_COOKIE/$_SERVER`等`$_`开头的变量获取到任何属性参数。

### 通过容器获取的类都是单例

通过依赖注入容器获取的都是进程内持久化的，是多个协程共享的，所以不能包含任何的请求唯一的数据或协程唯一的数据，这类型的数据都通过协程上下文去处理，具体请仔细阅读 [依赖注入](https://hyperf.wiki/#/./zh/di) 和 [协程](https://hyperf.wiki/#/./zh/coroutine) 章节。


### 框架生命周期

`Hyperf` 是运行于 `Swoole` 之上的，想要理解透彻 `Hyperf` 的生命周期，那么理解 `Swoole` 的生命周期也至关重要。
`Hyperf` 的命令管理默认由 `symfony/console` 提供支持(如果您希望更换该组件您也可以通过改变 `skeleton` 的入口文件更换成您希望使用的组件)，在执行 `php bin/hyperf.php start` 后，将由 `Hyperf\Server\Command\StartServer` 命令类接管，并根据配置文件 `config/autoload/server.php` 内定义的 `Server` 逐个启动。
关于依赖注入容器的初始化工作，我们并没有由组件来实现，因为一旦交由组件来实现，这个耦合就会非常的明显，所以在默认的情况下，是由入口文件来加载 `config/container.php` 来实现的。

### 禁止注入 model，实现单例方法

可以使用 `UserInfoModel::xxxx` 方法，或`make`，`new`

### 请求与协程生命周期

`Swoole` 在处理每个连接时，会默认创建一个协程去处理，主要体现在 `onRequest、onReceive、onConnect` 事件，所以可以理解为每个请求都是一个协程，由于创建协程也是个常规操作，所以一个请求协程里面可能会包含很多个协程，同一个进程内协程之间是内存共享的，但调度顺序是非顺序的，且协程间本质上是相互独立的没有父子关系，所以对每个协程的状态处理都需要通过 [协程上下文](https://hyperf.wiki/#/zh-cn/coroutine?id=%e5%8d%8f%e7%a8%8b%e4%b8%8a%e4%b8%8b%e6%96%87) 来管理。

## 路由

路由必须是三级，/m/c/a

http 服务使用 `@AutoController` 
Rpc 服务使用 `@RpcService`

如 ` * @RpcService(name="v2/test", protocol="jsonrpc-tcp-length-check", server="json-rpc")`

### 自动生成 Request 文件

```shell
php bin/hyperf.php request
```

### 参数过滤

路由 `/v2/test/get_test` 对应 `\App\Controller\V2\Test::getTest` 方法

必须新建一个对应的 `app/Request/V2/Test/GetTest.php` 文件

生成命令：`php bin/hyperf.php gen:request V2/Test/GetTest`

```php
public function getTest()
{
    $uid = input('uid');
    $name = input('name');
    $fields = input('fields', []);
    return $this->showResult([
        $uid,
        $fields,
        $name,
    ]);
}
```

`input` 可以获得`GET|POST`参数，相当于`$_REQUEST`

***app/Request/V2/Test/GetTest.php*** 源码
```php
<?php

declare(strict_types=1);

namespace App\Request\V2\Test;

use App\Request\BaseRequest;

class GetTest extends BaseRequest
{

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'uid' => 'required|integer|get|post',
            'name' => 'required|str',
            'fields' => 'array',
            'ignore_cache' => 'boolean',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public function messages(): array
    {
        return [
            'uid.required' => ':attribute 不能为空',
            'uid.integer' => ':attribute 必须为整型',
            'fields.array' => ':attribute 必须为数组',
            'ignore_cache.boolean' => ':attribute 必须为 true 或 false',
        ];
    }
}

```

### 新增验证规则

mobile 国内手机号验证

`'phone' => 'required|mobile'` 

str或s 对字符串进行编码
`'phone' => 'required|str'` 
`'phone' => 'required|s'` 

i 对整数值进行强转，如果直接使用参数取值为字符串类型，主要解决当`declare(strict_types=1);`时，会报`ust be of the type int, string given`
使用方法`'age' => 'required|i'` 

> string、integer、int  被系统底层占用
>
#### http 服务

`'phone' => 'required|s|post'` 

`'phone' => 'required|s|get'`  // get 验证直接使用empty判断，不能传0、空、false

### cookie

```php
getCookie('xxx');
getCookies();
getCookies();
setCookies(new \Hyperf\HttpMessage\Cookie\Cookie('xxx', 'xxx', time() + 86400, '/', $domain));
```

## Model 模型

约束：只做数据库、缓存操作，不写业务逻辑，业务推荐写在 service 里面

### 分表自动创建模型

```shell
php bin/hyperf.php gen:dataModel user_info
```
![-w790](http://sh.cdnimage.net/mweb/2020052515903751991692/15903751646681-15903751991692.jpg)

```php
<?php

declare (strict_types=1);
namespace App\Model\User;

use TheFairLib\Model\DataModel;
/**
 * @property int $uid 用户id，用于做sharding
 * @property string $username 用户名
 * @property string $country_code 国家编码
 * @property string $country 注册国家
 * @property int $nationality 国籍
 * @property string $nick 昵称
 * @property string $sex 用户性别
 * @property string $mobile 用户手机号（加密）
 * @property string $password 用户密码
 * @property string $avatar 头像地址
 * @property string $state 用户状态
 * @property string $source 注册来源
 * @property string $app_source æ³¨å†Œåº”ç”¨æ¥æº
 * @property string $self_desc 自我描述
 * @property string $auth_desc 认证描述
 * @property string $geek_desc 达人描述
 * @property string $salt 加密salt
 * @property string $last_visit_time 最后访问时间
 * @property string $last_visit_ip 
 * @property string $reg_ip 
 * @property string $ctime 创建时间
 * @property string $utime 更新时间
 */
class UserInfo extends DataModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_info';
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'xxx_user';
    /**
     * sharding num
     *
     * @var int
     */
    protected $shardingNum = 20;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['uid', 'username', 'country_code', 'country', 'nationality', 'nick', 'sex', 'mobile', 'password', 'avatar', 'state', 'source', 'app_source', 'self_desc', 'auth_desc', 'geek_desc', 'salt', 'last_visit_time', 'last_visit_ip', 'reg_ip', 'ctime', 'utime'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['uid' => 'integer', 'nationality' => 'integer'];
}
```

### shardingId 方法

model 类中重写表名，原方法`$this->db()->table($this->_getTableName($uid))`

```php
public function getUserDevice($uid, $deviceId)
{
    return (array)self::shardingId($uid)->where([
        'uid' => $uid,
        'device_id' => $deviceId,
    ])->first();
}
```

全局使用方式

```php
UserInfo::shardingId($uid)->where([
    'uid' => $uid,
    'device_id' => $deviceId,
])->first();
```

### 分页

行数大于 50 会抛出异常。(无法直接修改，改动太大)

```json
{
    "code": 40001,
    "message": {
        "text": "per page max 50",
        "action": "toast"
    },
    "result": {
        "per_age": 300,
    }
}
```

demo 方法
```php
UserInfo::query()->paginate(3)])
```

返回结果

```json
{
  "code": 0,
  "message": {
    "text": "",
    "action": "toast"
  },
  "result": {
    "item_list": [
      {
        "id": 4434,
        "title": "你永远不知道明天和意外哪个先来"
      }
    ],
    "page": 1,
    "item_per_page": 3,
    "item_count": 500,
    "page_count": 167
  }
}
```

### 全局 uuid

`getUuid()`

### sql 注入

demo 用例

```php
/**
 * 原生 sql 测试
 *
 * @return array
 * @throws ConnectionException
 */
public function rawSql()
{
    $uid = "1 or 1=1";
    return $this->db()->select("select * from xxx_user_info_0 where uid = {$uid}");
}
```
上面这条 sql 可以使用参数过滤做来，如强转 int

```php
/**
 * 原生 sql 测试
 *
 * @return array
 * @throws ConnectionException
 */
public function rawSql()
{
    $nick = "1 or 1=1";
    return $this->db()->select("select * from xxx_user_info_0 where nick = $nick");
}
```
上面这条 sql 传入的是一个字符串，如果用参数过滤很容易误杀，必须使用**预处理查询**

***系统底层会监控 sql 语句，发现原生 sql 语法，自动报警***

## Redis 使用

### getPrefix 缓存前缀

`getPrefix('Cache', 'hash')`

### 全局用法

```php
TheFairLib\Library\Cache\Redis::getContainer('pool_name')->set();
TheFairLib\Library\Cache\Redis::getContainer('pool_name')->get();
```

### zset 全局分页

```php
getItemListByPageFromCache(self::REDIS_POOL, $name, $lastItemId, 'asc', $itemPerPage, true);

//页码 
listItemFromCache(self::REDIS_POOL, $name, 1,'desc', $itemPerPage, true);
```

## 异常

**code 必须为 int，还不是 "0"**

### 普通异常

```php
throw new ServiceException('用户已经注册', ['uid' => $checkUid, 'third_party_uid' => hideStr($thirdPartyUid), 'mobile' => hideStr($mobile)]);

{
    "code": 40001,
    "message": {
        "text": "用户已经注册",
        "action": "toast"
    },
    "result": {
        "uid": 1111,
        "third_party_uid": "933**************************8c1",
        "mobile": "186*****263",
        "exception": "App\\Exception\\ServiceException"
    }
}
```

### code 异常

```php
throw new BusinessException(ErrorCode::CODE_RATE_LIMIT, ['host' => getServerLocalIp()]);

{
    "code": 50003,
    "message": {
        "text": "192*******.43 服务器超时, 请稍后再试",
        "action": "toast"
    },
    "result": {
        "exception": "App\\Exception\\BusinessException"
    }
}
```

### empty 异常

```php
throw new EmptyException('数据为空', ['uid' => 1]);

{
    "code": 40001,
    "message": {
        "text": "数据为空",
        "action": "toast"
    },
    "result": {
        "uid": 1,
        "exception": "App\\Exception\\EmptyException"
    }
}
```

## 访问 rpc 服务


```php
$data = \TheFairLib\Service\JsonRpc\RpcClient\Client::Instance('xxx_service')->call('/v2/test/get_test', [

]);
```

## Elasticsearch 搜索

```php
\TheFairLib\Library\Search\Elastic::getContainer()->get([
    'index' => 'xxx',
    'type' => 'xxx',
    'id' => 1,
]);
```

## 统一日志处理

全局方法：`TheFairLib\Library\Logger::get()`

### level 日志等级

1. DEBUG (100): 详细的debug信息。
2. INFO (200): 关键事件。
3. NOTICE (250): 普通但是重要的事件。
4. WARNING (300): 出现非错误的异常。
5. ERROR (400): 运行时错误，但是不需要立刻处理。
6. CRITICA (500): 严重错误。
7. EMERGENCY (600): 系统不可用。

### 其他日志处理

```ini
LOG_DIR=/home/xxx/logs/www/  # 日志保存路径
CLOSE_LOG=0 # 1为关闭日志，0为正常
LOG_MAX_FILES_DAY=30 # 日志保存天数
```


## hyperf service 服务之间的访问

配置文件 `config/autoload/services.php`


```php
<?php

declare(strict_types=1);

return [
     'consumers' => [
        [
            'name' => 'v2/test',
            'service' => '',
            'protocol' => 'jsonrpc-tcp-length-check',
            'load_balancer' => 'random',
            'nodes' => [
                [
                    'host' => '192.168.0.249',
                    'port' => 2301,
                ],
            ],
            'app_key' => 'xxx',
            'app_secret' => 'xxx1111',
            // 配置项，会影响到 Packer 和 Transporter
            'options' => [
                'connect_timeout' => 5.0,
                'recv_timeout' => 5.0,
                'settings' => [
                    // 根据协议不同，区分配置
                    'open_length_check' => true,
                    'package_length_type' => 'N',
                    'package_length_offset' => 0,
                    'package_body_offset' => 4,
                    'package_max_length' => 1024 * 1024 * 2,
                ],
                // 当使用 JsonRpcPoolTransporter 时会用到以下配置
                'pool' => [
                    'min_connections' => 1,
                    'max_connections' => 32,
                    'connect_timeout' => 10.0,
                    'wait_timeout' => 3.0,
                    'heartbeat' => -1,
                    'max_idle_time' => 60.0,
                ],
            ],
        ],
    ],
];

```


新建 RpcClient 

访问
```php

function smart(string $method, array $params = [], int $ttl = 0, string $poolName = 'default'): array {}

RpcClient::get('content_service')->smart('v1/test/get_test', [], 1000, 'user_info');
```


## 文档同步

`composer doc`

##### 使用 tips:

###### 1.注意：默认会在对应的项目下生成与各个接口 Doc 注解中填写的 tag 字段的第一个值为准，

比如在 controller 下定义：

```
/**
 * @Doc(name="测试方法", tag={"user", "api"})
 *
 * @return array
 */
// ...  
```

那么会在项目下创建一个 user 的分类：

![image-20210304170520601](https://i.loli.net/2021/03/04/53DLdAY7ynlu8JT.png)

###### 2.默认只会新增不存在的接口（以接口的 scheme 做唯一识别，如 `/v1/test/test` ），所以当你做了接口改动，需要覆盖现有接口文档时，你可以：

1. 到文档中删除接口，然后重新在本地执行 `composer gen_doc`

2. 或者在 config/autoload/docs.php 的 force_update 中添加配置，值为你要选择覆盖的接口 path，例如：

   ```php
   'force_update' => [
       '/v1/test/test',
   ],
   ```

   那么每次执行文档命令，都会以你本地最新的结果为准同步到在线文档。
3. 接口访问后的 response 文件默认是不覆盖的，可以在 runtime 下删除对应的 .json 文件来更新 response 结果，也可以在 docs.refresh_response_file 中加入配置项，保持 response 文件持续更新


## 线上服务启动 

### systemd 管理 

用于 `centos 7`

在项目下新建：`bin/push.service`

```ini
[Unit]
Description=push.service Http Server
After=network.target
After=syslog.target

[Service]
Type=simple
LimitNOFILE=65535
ExecStart=/usr/bin/php /home/xxx/www/push_service/bin/hyperf.php start
ExecReload=/bin/kill -USR1 $MAINPID
Restart=always

[Install]
WantedBy=multi-user.target graphical.target
```

使用`ln -s /home/xxx/www/push_service/bin/push.service /usr/lib/systemd/system && systemctl daemon-reload && systemctl enable push.service`

```shell
#启动服务
systemctl start push.service
#restart服务
systemctl restart push.service
#关闭服务
systemctl stop push.service

systemctl status push.service
```

## 单元测试

```shell
composer test
```


## 格式化项目代码

```shell
composer cs-fix
```

## 静态语法检测

```shell
composer analyse
```


## 其他问题
rpc或http 返回结果 `500404`，请重试

## 常用方法，可全局调用

![-w992](http://sh.cdnimage.net/mweb/2021071616264168341961/16264168127694-16264168341961.jpg)

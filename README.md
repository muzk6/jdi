# [jdi](https://github.com/muzk6/jdi.git)
> PHP 框架 —— Just Do It<br>
相关项目：<br>
[jdi-ops: JDI 框架的 OPS 运维后台](https://github.com/muzk6/jdi-ops)

## 起步

### 安装

`composer require muzk6/jdi`

### 基本用例

*index.php*
```php
require __DIR__ . '/vendor/autoload.php';

// 框架初始化
\JDI\App::init();

// 注册路由
route_get('/', function () {
    return 'Just Do It!';
});

// 分发路由
svc_router()->dispatch();
```

开启服务：`php -S 0.0.0.0:8080`

### 参数配置

自定义配置：`\JDI\App::init(['config.debug' => false]);`

其它配置项参考下表：

配置项 | 默认值 | 描述
--- | --- | ---
config.debug | true | 调试开发模式，用于显示错误信息、关闭视图模板缓存、关闭 opcache
config.path_data | <jdi 根目录>/data | 数据目录，保证有写权限
config.path_view | <jdi 根目录>/views | 视图模板目录
config.path_config_first | <空> | 第一优先级配置目录，找不到配置文件时，就在第二优先级配置目录里找，以此类推
config.path_config_second | <空> | 第二优先级配置目录
config.path_config_third | <jdi 根目录>/config | 第三优先级配置目录，一般默认即可，取框架的默认配置文件
config.timezone | PRC | 时区
config.session_start | true | 开启 session
config.init_handler | null | 容器初始化回调，null 时默认调用 \JDI\App::initHandler

### 更多例子请 `cd` 到目录 `tests/feature`

- 简单路由 `php -S 0.0.0.0:8080 router_simple.php`
- 中间件与路由 `php -S 0.0.0.0:8080 router_advanced.php`
- 经典 MVC `php -S 0.0.0.0:8080 router_mvc.php`

## 规范建议
> 仅仅建议

- 变量名小驼峰，与数据库字段、数组键、URL中的参数统一
- 方法名小驼峰，与面向对象统一
- 函数名下划线，与面向过程、内置函数统一

## 路由

### 注册路由

- `route_get()` 注册回调 GET 请求
- `route_post()` 注册回调 POST 请求
- `route_any()` 注册回调任何请求
- `route_middleware()` 注册路由中间件，顺序执行，组内优先
- `route_group()` 路由分组，隔离中间件

其中参数 url: `/demo` 全匹配；`#/demo#` 正则匹配(`#`开头自动切换为正则模式)；更多高级用法可使用 `\JDI\Services\Router::addRoute`

### 用例

#### 中间件

```php
route_middleware(function () {
    echo '中间件a1'; // 这里为了测试才用 echo，实际开发时只用于处理逻辑而不需要打印
});

route_middleware(function () {
    echo '中间件a2';
});

route_group(function () {
    route_middleware(function () {
        echo '中间件b1';
    });

    route_get('/mid', function () {
        return 'Just Do It!';
    });
});

route_middleware(function () {
    echo '中间件a3';
});
```

GET 请求 `/mid` 输出：
```
中间件b1
中间件a1
中间件a2
Just Do It!
中间件a3
```

#### 异常处理

```php
// 不指定参数三，用默认方式
route_post('/xhr', function () {
    panic();
});

// 指定参数三，自定义异常处理
route_post('/doc', function () {
    panic('doc error');
}, function (Exception $exception) {
    if ($exception instanceof AppException) {
        // 只提示业务异常
        alert($exception->getMessage());
    } else {
        // 非业务异常不提示
        back();
    }
});
```

POST 请求 `/xhr` 输出：`{ "s": false, "c": 0, "m": "", "d": {} }`

POST 请求 `/doc` 输出 `alert()` 弹层：`doc error`

#### 路由的其它方法

- `\JDI\Services\Router::setStatus404Handler` 设置响应 404 的回调函数
- `\JDI\Services\Router::fireStatus404` 触发 404 错误
- `\JDI\Services\Router::getMatchedRoute` 成功匹配的路由
- `\JDI\Services\Router::getREMatches` URL 正则捕获项
- `\JDI\Services\Router::getException` 异常，通常用于在后置中间件做处理
- `\JDI\Services\Router::setResponseContent` 设置响应内容，通常用于在后置中间件改写响应内容
- `\JDI\Services\Router::getResponseContent` 获取响应内容

## 请求参数
> 获取、过滤、表单验证、类型强转 请求参数 `$_GET,$_POST` 支持 `payload`

- 以下的验证失败时会抛出异常 \JDI\Exceptions\AppException

### 不验证，一个一个获取

```php
$first_name = input('first_name');
$last_name = input('last_name');
var_dump($first_name, $last_name);
```

### 不验证，统一获取

```php
$request = request();
var_dump($request);
```

### 部分验证，一个一个获取

```php
$first_name = input('first_name');
$last_name = validate('last_name')->required()->get('名字');
var_dump($first_name, $last_name);
```

### 部分验证，统一获取

```php
validate('last_name')->required()->setTitle('名字');
$request = request();
var_dump($request);
```

### 串联短路方式验证（默认）

遇到验证不通过时，立即终止后面的验证

```php
validate('first_name')->required();
validate('last_name')->required()->setTitle('名字');
$request = request(); // 以串联短路方式验证
var_dump($request);
```

*串联结果*
```json
{
    "s": false,
    "c": 10001000,
    "m": "参数错误",
    "d": {
        "first_name": "不能为空"
    }
}
```

### 并联验证

即使前面的验证不通过，也会继续验证后面的字段

```php
validate('first_name')->required();
validate('last_name')->required()->setTitle('名字');
$request = request(true); // 以并联方式验证
```

*并联结果*
```json
{
    "s": false,
    "c": 10001000,
    "m": "参数错误",
    "d": {
        "first_name": "不能为空",
        "last_name": "名字不能为空"
    }
}
```

### `input()` 参数说明

`'get.foo:i'` 中的类型转换`i`为整型，其它类型为：

Name | Type
--- | ---
i | int
s | string
b | bool
a | array
f | float
d | double

## 响应内容

在路由回调里使用

- `return 'Just Do It!';` Just Do It!
- `return [];` { "s": true, "c": 0, "m": "", "d": {} }
- `return ['foo' => 1];` { "s": true, "c": 0, "m": "", "d": { "foo": 1 } }
- `return api_msg('保存成功');` { "s": true, "c": 0, "m": "保存成功", "d": {} }
- `panic();` { "s": false, "c": 0, "m": "", "d": {} }
- `panic('保存失败');` { "s": false, "c": 0, "m": "保存失败", "d": {} }
- `panic('保存失败', ['foo' => 1]);` { "s": false, "c": 0, "m": "保存失败", "d": { "foo": 1 } }
- `panic(10001000);` { "s": false, "c": 10001000, "m": "参数错误", "d": {} }; 参考翻译文件 lang_zh_CN.php
- `panic([10002001, 'name' => 'tom']);` { "s": false, "c": 10002001, "m": "欢迎 tom", "d": {} }

以上方式都是 `return api_json()` 的衍生，更多需求可直接调用 `api_json()`

其中与 `api_format()` 的关系是：`api_json()` 即 `json_encode(api_format())`

## 容器服务

### 定义单例服务
 
```php
function svc_foo()
{
    return \JDI\App::singleton(__FUNCTION__, function () {
        return new Foo();
    });
}
```

### 覆盖

如果上面的 `svc_foo()` 还未调用过，可以覆盖：

```php
\JDI\App::set('svc_foo', function () {
    return new Bar();
});
```

如果要强制修改，可以先删除：

```php
\JDI\App::unset('svc_foo');
```

## PDO 数据库

可以配置 MySQL, SQLite 等 PDO 支持的数据库

- 配置文件 `config/mysql.php`
- 用例参考 `tests/phpunit/Services/DBTest.php`

如果想同时使用 SQLite 等数据库, 复制 `mysql.php` 为新的数据库配置文件，按需配置 dsn，再注册容器即可(参考 `services.php` `svc_mysql()`)

## `helpers` 其它辅助函数用例

#### `config()` 配置文件

- `config('app.lang')`
- 例如第一、二优先级目录分别是 `config/dev/`, `config/common/`
- 依次搜索下面文件，存在时返回第一个结果的文件内容：
    - `config/dev/app.php`
    - `config/common/app.php`
    - `vendor/muzk6/jdi/config/app.php`
- `config(['app.lang' => 'en'])`设置 run-time 的配置

#### `trans()` 多语言文本

- `trans(10001000)`
- 假设当前语言是`zh_CN`, 默认语言是`en`
- 依次搜索`lang_zh_CN.php, lang_en.php`, 存在`10001000`这个`key`时返回第一个结果内容，都不存在时返回`''`

#### `logfile()` 文件日志

`logfile('test', ['foo', 'bar'], 'login')` 把内容写到`data/log/login_20190328.log`

各日志文件说明：

- `standard_xxx.log` PHP 标准错误处理程序写的日志，比较精简，但能记录 Fatal Error, Parse Error
- `error_xxx.log` 框架写的错误日志，比较详细
- `app_xxx.log` 用户写的默认日志，文件名可以修改，由 `logfile()` 参数3控制 

通用日志字段场景：

```php
logfile('test', ['user_id' => 123, '这里写日志']); 
logfile('test', ['user_id' => 123, '另一处又写日志']);
```

如果像以上例子都要记录 `user_id`, 可以使用 `\JDI\Services\Log::setData` 单独把 `user_id` 设置起来，后面调用 `logfile()` 时不需要再记录 `user_id`：

```php
svc_log()->setData('user_id', 123);

logfile('test', ['这里写日志']);
logfile('test', ['另一处又写日志']);
```

效果与前面每次都要记录 `user_id` 的例子一致

#### `url()` 带协议和域名的完整URL

- 当前域名URL：`url('path/to')`
- 其它域名URL：`url(['test', '/path/to'])`

#### `panic()` 直接抛出业务异常对象

- `panic(10001000)` 等于 `throw new AppException('10001000')` 自动转为错误码对应的文本，参考翻译文件 lang_zh_CN.php
- `panic('foo')` 等于 `throw new AppException('foo')`
- `panic('foo', ['bar'])` 等于 `throw (new AppException('foo'))->setData(['bar'])`

`AppException` 异常属于业务逻辑，能够作为提示通过接口返回给用户看，而其它异常则不会(安全考虑)

#### `request_flash()`, `old()` 记住并使用上次的请求参数

- `request_flash()` 把本次请求的参数缓存起来
- `old(string $name = null, string $default = '')` 上次请求的字段值

#### `xsrf_*()` XSRF

- `xsrf_field()`直接生成 HTML
- `xsrf_token()`生成 token
- `xsrf_check()`效验，token 来源于 `$_SERVER['HTTP_X_XSRF_TOKEN'], $_POST['_token'], $_GET['_token'], $_REQUEST['_token']`

请求时带上 `Token`, 使用以下任意一种方法

- `POST` 请求通过表单参数 `_token`, 后端将从 `$_POST['_token']` 读取
- `GET` 请求通过 `?_token=`, 后端将从 `$_GET['_token']` 读取
- 通过指定请求头 `X-XSRF-Token`, 后端将从 `$_SERVER['HTTP_X_XSRF_TOKEN']` 读取

#### `flash_*()` 闪存，一性次缓存

- `flash_set(string $key, $value)` 闪存设置
- `flash_has(string $key)` 存在且为真
- `flash_exists(string $key)` 闪存是否存在，即使值为 null
- `flash_get(string $key)` 闪存获取并删除
- `flash_del(string $key)` 闪存删除

#### `assign()`, `view()` 模板与变量

- `assign('firstName', 'Hello')` 定义模板变量
- `return view('demo', ['title' => $title])` 定义模板变量的同时返回渲染内容

#### `back()`, `redirect()`, `alert()` 网页跳转

- `back()` 网页后退
- `redirect('/demo')` 跳转到 `/demo`
- `alert()` JS alert() 并跳转回上一页

## 登录

```php
svc_auth()->login(1010); // 登录 ID 为 1010
svc_auth()->getUserId(); // 1010
svc_auth()->isLogin(); // true
svc_auth()->logout(); // 退出登录
```

## 消息队列

- worker 遇到信号 `SIGTERM`, `SIGHUP`, `SIGINT`, `SIGQUIT` 会平滑结束进程。如果要强行结束可使用信号 `SIGKILL`, 命令为 `kill -s KILL <PID>`
- 当文件有变动时，队列有消息会触发 worker 退出，因此需要以守护进程方式启动 worker, 建议生产环境使用 supervisor 服务，临时测试可用 `watch` 命令启动 worker 

### 依赖

`composer require php-amqplib/php-amqplib:^2.9`

### 配置

`config/rabbitmq.php`

### 用例

参考 `tests/feature/message_queue.php`

建议规则：
- 每个 worker 只消费一个队列；
- 队列名与 worker名 一致，便于定位队列名对应的 worker 文件；
- 队列名与 worker名 要有项目名前缀，防止在 Supervisor, RabbitMq 里与其它项目搞混

## OPS 运维与开发

推荐使用本框架的运维后台 [jdi-ops](https://github.com/muzk6/jdi-ops)

### XDebug Trace
> 跟踪调试日志

日志默认位于 `data/xdebug_trace/`

#### 依赖

`ext-xdebug`

#### 跟踪 fpm

- 当前URL 主动开启: `/?_xt=name0`，`name0`是当前日志的标识名
- Cookie 主动开启: `_xt=name0;`

*注意：`URL`, `Cookie` 方式的前提必须先设置 `config/whitelist.php` 白名单 `IP` 或 白名单 `Cookie`*

#### 跟踪 cli

`php demo.php --trace` 在任何脚本命令后面加上参数 `--trace` 即可

### XHProf

日志默认位于 `data/xhprof/`

#### 依赖

[扩展 tideways_xhprof](https://github.com/tideways/php-xhprof-extension/releases)

#### 使用

- 配置文件 `config/xhprof.php`
- `enable` 设置为 `1`, 即可记录大于等于指定耗时的请求
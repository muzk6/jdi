# [jdi](https://github.com/muzk6/jdi.git)
> PHP 框架 —— Just Do It

## 起步

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

自定义配置：`\JDI\App::init(['config.debug' => false]);`

其它配置项参考下表：

配置项 | 默认值 | 描述
--- | --- | ---
config.debug | true | 调试开发模式，用于显示错误信息、关闭视图模板缓存、关闭 opcache、跳过白名单检查
config.path_data | <jdi 根目录>/data | 数据目录
config.path_view | <jdi 根目录>/views | 视图模板目录
config.path_config_first | <空> | 第一优先级配置目录，找不到配置文件时，就在第二优先级配置目录里找，以此类推
config.path_config_second | <空> | 第二优先级配置目录
config.path_config_third | <jdi 根目录>/config | 第三优先级配置目录，一般默认即可，取框架的默认配置文件
config.init_handler | null | 容器初始化回调，null 时默认调用 \JDI\App::initHandler

### 更多例子请 `cd` 到目录 `tests/feature`

- 简单路由 `php -S 0.0.0.0:8080 router_simple.php`
- 中间件与路由 `php -S 0.0.0.0:8080 router_advanced.php`
- 经典 MVC `php -S 0.0.0.0:8080 router_mvc.php`

## 规范建议
> 仅仅建议

- 变量名小驼峰，与数据库字段、数组键、URL中的参数统一
- 方法名小驼峰，与面向对象统一
- 函数名下划线，与面积过程、内置函数统一

## 路由

### 注册路由

- `route_get()` 注册回调 GET 请求
    - `route_get_re()` 正则匹配
- `route_post()` 注册回调 POST 请求
    - `route_post_re()` 正则匹配 
- `route_any()` 注册回调任何请求
    - `route_any_re()` 正则匹配
- `route_middleware()` 注册路由中间件
- `route_group()` 路由分组

```php
route_get('/demo/xhr', function () {
    // 返回 state:false 的 json
    panic('失败消息'); // 这里抛出 AppException 异常，相当于 return api_json(true, [], '失败消息');

    // 返回 state:true 的 json 内容
    $data = ['key1' => 'val1'];
    return $data; // 直接返回数组，相当于 return api_json(true, $data);
    
    // 原样输出文本
    return 'hello world'; // 返回非数组，直接原样输出文本，一般用于返回 view() 来输出 html 网页
});
```

## 请求参数
> 获取、过滤、表单验证、类型强转 请求参数 `$_GET,$_POST` 支持 `payload`

- 以下的验证失败时会抛出异常 \JDI\Exceptions\AppException

### 不验证，一个一个获取

```php
$first_name = input('post.first_name');
$last_name = input('last_name');
var_dump($first_name, $last_name);exit;
```

### 不验证，统一获取

```php
input('post.first_name');
input('last_name');
$request = request();
var_dump($request);exit;
```

### 部分验证，一个一个获取

```php
$first_name = input('post.first_name');
$last_name = validate('last_name')->required()->get('名字');
var_dump($first_name, $last_name);exit;
```

### 部分验证，统一获取

```php
input('post.first_name');
validate('last_name')->required()->setTitle('名字');
$request = request();
var_dump($request);exit;
```

### 串联短路方式验证（默认）

遇到验证不通过时，立即终止后面的验证

```php
validate('post.first_name')->required();
validate('last_name')->required()->setTitle('名字');
$request = request(); // 以串联短路方式验证
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
validate('post.first_name')->required();
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

## PDO 数据库

可以配置 MySQL, SQLite 等 PDO 支持的数据库

- 配置文件 `config/mysql.php`
- 用例参考 `tests/phpunit/Services/DBTest.php`

如果想同时使用 SQLite 等数据库, 参考复制 `mysql.php` 为新的数据库配置文件，按需配置 dsn，再注册容器即可(参考 `services.php` `svc_mysql()`)

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

- `standard_xxx.log` PHP 标准错误处理程序写的日志，比较精简，但只能它才能记录 Fatal Error, Parse Error
- `error_xxx.log` 框架写的错误日志，比较详细
- `app_xx.log` 用户写的默认日志，文件名可以修改，由 `logfile()` 参数3控制 

#### `url()` 带协议和域名的完整URL

- 当前域名URL：`url('path/to')`
- 其它域名URL：`url(['test', '/path/to'])`

#### `panic()` 直接抛出业务异常对象

- `panic('foo')` 等于 `new AppException('foo')`
- `panic('foo', ['bar'])` 等于 `new (AppException('foo'))->setData(['bar'])`
- `panic(10001000)` 等于 `new AppException('10001000')` 自动转为错误码对应的文本

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

#### `api_format()`, `api_json()` 格式化为接口输出的内容结构

- `api_format(true, ['foo' => 1])` 格式化为成功的内容结构 array
- `api_format($exception)` 格式化异常对象为失败的内容结构 array
- `api_json()`, `api_format()` 用法一样，区别是返回 string-json
- `api_success()`, `api_error()` 是 `api_json()` 的简写

*成功提示，在控制器 action 里的等价写法如下：*

```json
{
    "s": true,
    "c": 0,
    "m": "",
    "d": {
        "foo": 1
    }
}
```

```php
public function successAciton()
{
    return ['foo' => 1]; // 只能返回消息体 d
    return api_success('我是成功消息', 0, ['foo' => 1]); // 支持返回 c, m ,d; 一般用于方便返回纯 m, 例如 api_success('我是成功消息');
    return api_json(true, ['foo' => 1]); // 支持返回 s, c, m ,d
}
```

*错误提示等价写法如下：*

```json
{
    "s": false,
    "c": 0,
    "m": "我是失败消息",
    "d": {
        "foo": 1
    }
}
```

```php
public function errorAciton()
{
    panic('我是失败消息', ['foo' => 1]); // 直接抛出异常，不用 return, 如果使用错误码，错误码必须存在于 `lang/` 配置里
    return api_error('我是失败消息', 0, ['foo' => 1]); // 支持返回 c, m ,d; 可自由指定错误码
    return api_json(false, ['foo' => 1]); // 支持返回 s, c, m ,d
}
```

#### `assign()`, `view()` 模板与变量

- `assign('firstName', 'Hello')` 定义模板变量
- `return view('demo', ['title' => $title])` 定义模板变量的同时返回渲染内容

#### `back()`, `redirect()` 网页跳转

- `return back()` 跳转回上一步
- `return redirect('/demo')` 跳转到 `/demo`

## 登录

```php
svc_auth()->login(1010); // 登录 ID 为 1010
svc_auth()->getUserId(); // 1010
svc_auth()->isLogin(); // true
svc_auth()->logout(); // 退出登录
```

## 消息队列

worker 遇到信号 `SIGTERM`, `SIGHUP`, `SIGINT`, `SIGQUIT` 会平滑结束进程。
如果要强行结束可使用信号 `SIGKILL`, 命令为 `kill -s KILL <PID>`

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

*注意：请确保对 `data/` 目录有写权限*

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

*注意：请确保对 `data/` 目录有写权限*

#### 依赖

[扩展 tideways_xhprof](https://github.com/tideways/php-xhprof-extension/releases)

#### 使用

- 配置文件 `config/xhprof.php`
- `enable` 设置为 `1`, 即可记录大于指定耗时的请求
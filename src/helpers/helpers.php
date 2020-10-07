<?php

use JDI\App;
use JDI\Exceptions\AppException;
use JDI\Services\Validator;

if (!function_exists('config')) {
    /**
     * 读取、设置 配置
     * <p>
     * 读取 config/dev/app.php 里的 lang 配置：config('app.lang')<br>
     * 设置：config(['app.lang' => 'en'])
     * </p>
     * @param string|array $key string时读取，array时设置
     * @return bool|mixed
     */
    function config($key)
    {
        if (is_array($key)) {
            $ret = false;
            foreach ($key as $k => $v) {
                $ret = svc_config()->set($k, $v);
            }

            return $ret;
        } else {
            return svc_config()->get($key);
        }
    }
}

if (!function_exists('panic')) {
    /**
     * 抛出业务异常对象
     * @param string|int|array $message_or_code 错误码或错误消息
     * <p>带有参数的错误码，使用 array: [10002001, 'name' => 'tom'] 或 [10002001, ['name' => 'tom']]</p>
     * @param array $data 附加数组
     * @throws AppException
     */
    function panic($message_or_code = '', array $data = [])
    {
        AppException::panic($message_or_code, $data);
    }
}

if (!function_exists('trans')) {
    /**
     * 转换成当前语言的文本
     * @param int $code
     * @param array $params
     * @return string
     */
    function trans(int $code, array $params = [])
    {
        return svc_lang()->trans($code, $params);
    }
}

if (!function_exists('logfile')) {
    /**
     * 文件日志
     * @param string $index 日志名(索引)
     * @param array|string $data 日志内容
     * @param string $filename 日志文件名前缀
     * @return int|null
     */
    function logfile(string $index, $data, string $filename = 'app')
    {
        return svc_log()->file($index, $data, $filename);
    }
}

if (!function_exists('api_format')) {
    /**
     * API 格式化
     * @param bool|AppException|Exception $state 业务状态，异常对象时自动填充后面的参数
     * @param array $data 对象体
     * @param string $message 消息体
     * @param int $code 消息码
     * @return array
     */
    function api_format($state, array $data = [], string $message = '', int $code = 0)
    {
        $body = [
            's' => false,
            'c' => $code,
            'm' => $message,
            'd' => $data,
        ];

        if ($state instanceof Exception) {
            $exception = $state;

            empty($code) && $body['c'] = $exception->getCode();
            empty($message) && $body['m'] = $exception->getMessage();

            if (empty($body['d']) && ($exception instanceof AppException)) {
                $body['d'] = $exception->getData();
            }
        } else {
            $body['s'] = boolval($state);
        }

        $body['s'] = boolval($body['s']);
        $body['c'] = intval($body['c']);
        $body['m'] = strval($body['m']);

        return $body;
    }
}

if (!function_exists('api_json')) {
    /**
     * JSON 类型的 API 格式
     * @param bool|AppException|Exception $state 业务状态，异常对象时自动填充后面的参数
     * @param array $data 对象体
     * @param string $message 消息体
     * @param int $code 消息码
     * @return string
     */
    function api_json($state, array $data = [], string $message = '', int $code = 0)
    {
        // 先刷出 buffer, 避免被后面的 header 影响
        if (ob_get_status()) {
            ob_flush();
            flush();
        }

        headers_sent() || header('Content-Type: application/json; Charset=UTF-8');

        $body = api_format($state, $data, $message, $code);
        if (empty($body['d'])) {
            $body['d'] = new stdClass();
        }

        return json_encode($body);
    }
}

if (!function_exists('api_success')) {
    /**
     * 成功状态的 api_json()
     * @param string $message 消息体
     * @param int $code 消息码
     * @param array $data 对象体
     * @return string
     */
    function api_success(string $message = '', int $code = 0, array $data = [])
    {
        return api_json(true, $data, $message, $code);
    }
}

if (!function_exists('api_error')) {
    /**
     * 失败状态的 api_json()
     * @param string $message 消息体
     * @param int $code 消息码
     * @param array $data 对象体
     * @return string
     */
    function api_error(string $message = '', int $code = 0, array $data = [])
    {
        return api_json(false, $data, $message, $code);
    }
}

if (!function_exists('assign')) {
    /**
     * 定义模板变量
     * @param string $name
     * @param mixed $value
     */
    function assign(string $name, $value)
    {
        svc_blade()->assign($name, $value);
    }
}

if (!function_exists('view')) {
    /**
     * 渲染视图模板
     * @param string $view 模板名
     * @param array $params 模板里的参数
     * @return string
     * @throws Exception
     */
    function view(string $view, array $params = [])
    {
        return svc_blade()->view($view, $params);
    }
}

if (!function_exists('input')) {
    /**
     * 从 $_GET, $_POST 获取请求参数，支持 payload
     * <p>
     * 简单用例：input('age') 即 $_POST['age'] <br>
     * 高级用例：input('post.age:i', 18, function ($val) { return $val+1; }) <br>
     * 即 $_POST['age']不存在时默认为18，最终返回 intval($_GET['age'])+1
     * @param string $field [(post|get|request).]<field_name>[.(i|b|a|f|d|s)]<br>
     * 参数池默认为 $_POST<br>
     * field_name 为字段名<br>
     * 类型强转：i=int, b=bool, a=array, f=float, d=double, s=string(默认)
     * @param mixed $default 默认值
     * @param callable $after 后置回调函数，其返回值将覆盖原字段值<br>
     * 回调函数格式为 function ($v, $k) {}<br>
     * </p>
     * @return mixed
     */
    function input(string $field, $default = '', callable $after = null)
    {
        return svc_request()->input($field, $default, $after);
    }
}

/**
 * 当前环境是否为开发环境
 */
if (!function_exists('env_is_dev')) {
    function env_is_dev()
    {
        return App::get('config.app_env') === 'dev';
    }
}

if (!function_exists('validate')) {
    /**
     * 从 $_GET, $_POST 获取请求参数，支持 payload
     * <p>
     * 简单用例：input('age') 即 $_POST['age'] <br>
     * 高级用例：input('post.age:i', 18, function ($val) { return $val+1; }) <br>
     * 即 $_POST['age']不存在时默认为18，最终返回 intval($_GET['age'])+1
     * @param string $field [(post|get|request).]<field_name>[.(i|b|a|f|d|s)]<br>
     * 参数池默认为 $_POST<br>
     * field_name 为字段名<br>
     * 类型强转：i=int, b=bool, a=array, f=float, d=double, s=string(默认)
     * @param mixed $default 默认值
     * @param callable $after 后置回调函数，其返回值将覆盖原字段值<br>
     * 回调函数格式为 function ($v, $k) {}<br>
     * </p>
     * @return Validator
     */
    function validate(string $field, $default = '', callable $after = null)
    {
        return svc_request()->validate($field, $default, $after);
    }
}

if (!function_exists('request')) {
    /**
     * 获取所有请求参数，如果有验证则验证
     * @param bool $in_parallel false: 以串联短路方式验证；true: 以并联方式验证，即使前面的验证不通过，也会继续验证后面的字段
     * @return array
     * @throws AppException
     */
    function request(bool $in_parallel = false)
    {
        return svc_request()->request($in_parallel);
    }
}

if (!function_exists('back')) {
    /**
     * 网页后退
     * <p>`back()` 网页跳转回上一步</p>
     */
    function back()
    {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

if (!function_exists('redirect')) {
    /**
     * 网页跳转
     * <p>`redirect('/foo/bar')` 跳转到当前域名的`/foo/bar`地址去</p>
     * <p>`redirect('https://google.com')` 跳转到谷歌</p>
     * @param string $url
     */
    function redirect(string $url)
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('url')) {
    /**
     * 带协议和域名的完整URL
     * <p>
     * 当前域名URL：url('/path/to')<br>
     * 其它域名URL：url(['test', '/path/to'])
     * </p>
     * @param string|array $path URL路径
     * @param array $params Query String
     * @param bool $secure 是否为安全协议
     * @return string
     */
    function url($path, array $params = [], bool $secure = false)
    {
        if (is_array($path)) {
            if (count($path) !== 2) {
                trigger_error("正确用法：url(['test', '/path/to'])", E_USER_ERROR);
            }

            list($alias, $path) = $path;
            $conf = svc_config()->get('url');
            if (!isset($conf[$alias])) {
                trigger_error("url.php 不存在配置项: {$alias}", E_USER_ERROR);
            }

            $host = $conf[$alias];
        } else {
            $host = $_SERVER['HTTP_HOST'] ?? '';
        }

        if ($host) {
            $protocol = $secure ? 'https://' : 'http://';
            $host = $protocol . $host;
        }

        if ($params) {
            $path .= strpos($path, '?') !== false ? '&' : '?';
            $path .= http_build_query($params);
        }

        return $host . $path;
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * 客户端IP
     * @return string
     */
    function get_client_ip()
    {
        $ip = '';
        if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            $ip = $_SERVER['HTTP_CDN_SRC_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])
            && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', trim($_SERVER['HTTP_CLIENT_IP']))) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', trim($_SERVER['HTTP_X_FORWARDED_FOR']), $matches)) {
            foreach ($matches[0] AS $xip) {
                $xip = trim($xip);
                if (filter_var($xip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
                    $ip = $xip;
                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return filter_var(trim($ip), FILTER_VALIDATE_IP) ?: '';
    }
}

if (!function_exists('throttle')) {
    /**
     * 频率限制
     * <p>ttl秒 内限制 limit次</p>
     * @param string $key 缓存key
     * @param int $limit 限制次数
     * @param int $ttl 指定秒数内
     * @return int 剩余次数，0表示这次是最后一次通过，下次就触发限制
     * @throws AppException ['reset' => 重置的时间点]
     */
    function throttle(string $key, int $limit, int $ttl)
    {
        $now = time();
        $len = 0;

        if (svc_redis()->lLen($key) < $limit) {
            $len = svc_redis()->lPush($key, $now);
        } else {
            $earliest = intval(svc_redis()->lIndex($key, -1));
            if ($now - $earliest < $ttl) {
                svc_redis()->expire($key, $ttl);
                AppException::panic(10001001, [
                    'reset' => $earliest + $ttl,
                ]);
            } else {
                svc_redis()->lTrim($key, 1, 0);
                $len = svc_redis()->lPush($key, $now);
            }
        }

        svc_redis()->expire($key, $ttl);
        return $limit - $len;
    }
}

if (!function_exists('request_flash')) {
    /**
     * 把本次请求的参数缓存起来
     * @return bool
     */
    function request_flash()
    {
        return svc_request()->flash();
    }
}

if (!function_exists('old')) {
    /**
     * 上次请求的字段值
     * @param string $name
     * @param string $default
     * @return mixed
     */
    function old(string $name = '', string $default = '')
    {
        return svc_request()->old($name, $default);
    }
}

if (!function_exists('flash_set')) {
    /**
     * 闪存设置
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    function flash_set(string $key, $value)
    {
        return svc_flash()->set($key, $value);
    }
}

if (!function_exists('flash_has')) {
    /**
     * 闪存是否有值
     * @param string $key
     * @return bool true: 存在且为真
     */
    function flash_has(string $key)
    {
        return svc_flash()->has($key);
    }
}

if (!function_exists('flash_exists')) {
    /**
     * 闪存是否存在
     * @param string $key
     * @return bool true: 存在，即使值为 null
     */
    function flash_exists(string $key)
    {
        return svc_flash()->exists($key);
    }
}

if (!function_exists('flash_get')) {
    /**
     * 闪存获取并删除
     * @param string $key
     * @return null|mixed
     */
    function flash_get(string $key)
    {
        return svc_flash()->get($key);
    }
}

if (!function_exists('flash_del')) {
    /**
     * 闪存删除
     * @param string $key
     * @return true
     */
    function flash_del(string $key)
    {
        return svc_flash()->del($key);
    }
}

if (!function_exists('xsrf_field')) {
    /**
     * 生成带有 token 的表单域 html 元素
     * @return string
     */
    function xsrf_field()
    {
        return svc_xsrf()->field();
    }
}

if (!function_exists('xsrf_token')) {
    /**
     * 获取 token
     * <p>会话初始化时才更新 token</p>
     * @return string
     */
    function xsrf_token()
    {
        return svc_xsrf()->token();
    }
}

if (!function_exists('xsrf_check')) {
    /**
     * 校验 token
     * @return true
     * @throws AppException
     */
    function xsrf_check()
    {
        return svc_xsrf()->check();
    }
}

if (!function_exists('mq_publish')) {
    /**
     * 消息队列发布
     * @param string $queue 队列名称
     * @param array $data
     * @param string $exchange_name 交换器名称
     * @param string $exchange_type 交换器类型
     */
    function mq_publish(string $queue, array $data, string $exchange_name = 'jdi.direct', string $exchange_type = 'direct')
    {
        svc_rabbitmq()->publish($queue, $data, $exchange_name, $exchange_type);
    }
}

if (!function_exists('svc_rabbitmq')) {
    /**
     * 消息队列消费
     * @param string $queue 队列名称
     * @param callable $callback
     */
    function mq_consume(string $queue, callable $callback)
    {
        svc_rabbitmq()->consume($queue, $callback);
    }
}

if (!function_exists('route_get')) {
    /**
     * 注册回调 GET 请求
     * @param string $url url全匹配
     * @param callable $action
     */
    function route_get($url, callable $action)
    {
        svc_router()->addRoute('GET', $url, $action);
    }
}

if (!function_exists('route_post')) {
    /**
     * 注册回调 POST 请求
     * @param string $url url全匹配
     * @param callable $action
     */
    function route_post($url, callable $action)
    {
        svc_router()->addRoute('POST', $url, $action);
    }
}

if (!function_exists('route_any')) {
    /**
     * 注册回调任何请求
     * @param string $url url全匹配
     * @param callable $action
     */
    function route_any($url, callable $action)
    {
        svc_router()->addRoute('ANY', $url, $action);
    }
}

if (!function_exists('route_get_re')) {
    /**
     * 注册回调 GET 请求
     * @param string $url url正则匹配
     * @param callable $action
     */
    function route_get_re($url, callable $action)
    {
        svc_router()->addRoute('GET', $url, $action, ['url_type' => 'regexp']);
    }
}

if (!function_exists('route_post_re')) {
    /**
     * 注册回调 POST 请求
     * @param string $url url正则匹配
     * @param callable $action
     */
    function route_post_re($url, callable $action)
    {
        svc_router()->addRoute('POST', $url, $action, ['url_type' => 'regexp']);
    }
}

if (!function_exists('route_any_re')) {
    /**
     * 注册回调任何请求
     * @param string $url url正则匹配
     * @param callable $action
     */
    function route_any_re($url, callable $action)
    {
        svc_router()->addRoute('ANY', $url, $action, ['url_type' => 'regexp']);
    }
}

if (!function_exists('route_middleware')) {
    /**
     * 注册路由中间件
     * @param callable $fn
     */
    function route_middleware(callable $fn)
    {
        svc_router()->addMiddleware($fn);
    }
}

if (!function_exists('route_group')) {
    /**
     * 路由分组
     * @param callable $fn
     */
    function route_group(callable $fn)
    {
        svc_router()->addGroup($fn);
    }
}

if (!function_exists('curl_post')) {
    /**
     * POST 请求
     * @param string|array $url
     * <p>string: 'http://sparrow.com/demo' 一般用于固定 url 的场景</p>
     * <p>array: ['rpc.jdi', '/demo'] 即读取配置 url.php 里的域名再拼接上 /demo 一般用于不同环境不同 url 的场景</p>
     * @param array $data POST 参数
     * @param array $headers 请求头
     * @param int $connect_timeout 请求超时(秒)
     * @return array|string|null
     */
    function curl_post($url, array $data = [], array $headers = [], int $connect_timeout = 3)
    {
        return svc_curl()->post($url, $data, $headers, $connect_timeout);
    }
}

if (!function_exists('curl_get')) {
    /**
     * GET 请求
     * @param string|array $url
     * <p>string: 'http://sparrow.com/demo' 一般用于固定 url 的场景</p>
     * <p>array: ['rpc.sparrow', '/demo'] 即读取配置 url.php 里的域名再拼接上 /demo 一般用于不同环境不同 url 的场景</p>
     * @param array $data querystring 参数
     * @param array $headers 请求头
     * @param int $connect_timeout 请求超时(秒)
     * @return bool|string|null
     */
    function curl_get($url, array $data = [], array $headers = [], int $connect_timeout = 3)
    {
        return svc_curl()->get($url, $data, $headers, $connect_timeout);
    }
}
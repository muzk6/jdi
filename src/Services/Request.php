<?php

/*
 * This file is part of the muzk6/jdi.
 *
 * (c) muzk6 <muzk6x@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


namespace JDI\Services;


use Exception;
use JDI\Exceptions\AppException;
use JDI\Support\Svc;

/**
 * HTTP请求体
 * @package JDI\Services
 */
class Request
{
    /**
     * @var null|array php://input
     */
    protected $payload = null;

    /**
     * @var array 所有请求参数的集合
     */
    protected $request = [];

    /**
     * 上次请求的参数
     * @var array
     */
    protected $old_request = [];

    /**
     * 选择请求参数池 $_GET, $POST, $_REQUEST
     * @param string $method
     * @return array
     */
    protected function pool($method = '')
    {
        switch ($method) {
            case 'get':
                $bucket = &$_GET;
                break;
            case 'request':
                $bucket = &$_REQUEST;
                break;
            case 'post':
            default:
                if (isset($_SERVER['HTTP_CONTENT_TYPE']) && strpos(strtolower($_SERVER['HTTP_CONTENT_TYPE']), 'application/json') !== false) {
                    if (is_null($this->payload)) {
                        $this->payload = (array)json_decode(file_get_contents('php://input'), true);
                    }
                    $bucket = &$this->payload;
                } else {
                    $bucket = &$_POST;
                }
                break;
        }

        return $bucket;
    }

    /**
     * 解析键值
     * @param string $key field, post.field, field:i, post.field:i
     * @return array [参数集, 当前参数名, 类型]
     */
    protected function parse(string $key)
    {
        $key = trim($key);
        if (strpos($key, '.') !== false) {
            $key_dot = explode('.', $key);
            $bucket = $this->pool($key_dot[0]);
            $name = $key_dot[1];
        } else {
            $request_method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : '';
            $bucket = $this->pool($request_method);
            $name = $key;
        }

        $type = '';
        if (strpos($name, ':') !== false) {
            $name_dot = explode(':', $name);
            $name = $name_dot[0];
            $type = $name_dot[1];
        }

        return [$bucket, $name, $type];
    }

    /**
     * 类型转换
     * @param $value
     * @param string $type
     * @return array|bool|float|double|int|string
     */
    protected function convert($value, string $type)
    {
        switch ($type) {
            case 'i':
                $value = intval($value);
                break;
            case 'b':
                $value = boolval($value);
                break;
            case 'a':
                $value = (array)$value;
                break;
            case 'f':
                $value = floatval($value);
                break;
            case 'd':
                $value = doubleval($value);
                break;
            case 's':
            default:
                $value = strval($value);
                break;
        }

        return $value;
    }

    /**
     * 解析并返回参数信息
     * @param string $field
     * @param string $default
     * @param callable|null $after
     * @return array
     */
    protected function getValue(string $field, $default = '', callable $after = null)
    {
        list($bucket, $field_name, $field_type) = $this->parse($field);

        if (isset($bucket[$field_name])) {
            if (is_array($bucket[$field_name])) {
                // 本身就是数组
                $field_value = $bucket[$field_name];
                $field_type = 'a';
            } elseif (strlen(strval($bucket[$field_name]))) {
                // 去掉前后空格
                $field_value = trim(strval($bucket[$field_name]));
            } else {
                // 空取默认值
                $field_value = $default;
            }
        } else {
            // 不存在取默认值
            $field_value = $default;
        }

        // 自定义后置回调
        if ($after) {
            $field_value = $after($field_value, $field_name);
        }

        return [$field_name, $field_value, $field_type];
    }

    /**
     * 从 $_GET, $_POST 获取请求参数，支持 payload
     * <p>
     * 简单用例：input('age') 即 GET 请求时取 $_GET['age']; POST 请求时 $_POST['age'] <br>
     * 高级用例：input('post.age:i', 18, function ($val) { return $val+1; }) <br>
     * 即 $_POST['age']不存在时默认为18，最终返回 intval($_POST['age'])+1
     * @param string $field [(post|get|request).]<field_name>[.(i|b|a|f|d|s)]<br>
     * 没有指定 post, get, request 时，自动根据请求方式从 $_GET, $_POST 里取变量<br>
     * field_name 为字段名<br>
     * 类型强转：i=int, b=bool, a=array(本身是数组时自动强制切换为a), f=float, d=double, s=string(默认)
     * @param mixed $default 默认值
     * @param callable $after 后置回调函数，其返回值将覆盖原字段值<br>
     * 回调函数格式为 function ($v, $k) {}<br>
     * </p>
     * @return mixed
     */
    public function input(string $field, $default = '', callable $after = null)
    {
        list($field_name, $field_value, $field_type) = $this->getValue($field, $default, $after);
        $new_value = $this->convert($field_value, $field_type);

        $this->request[$field_name] = [
            'value' => $new_value,
        ];

        return $new_value;
    }

    /**
     * 用法与 \Core\Request::input 一致
     * @param string $field
     * @param string $default
     * @param callable|null $after
     * @return Validator
     */
    public function validate(string $field, $default = '', callable $after = null)
    {
        list($field_name, $field_value, $field_type) = $this->getValue($field, $default, $after);
        $new_value = $this->convert($field_value, $field_type);
        $validator = new Validator($field_value, $new_value);

        $this->request[$field_name] = [
            'value' => $new_value,
            'validator' => $validator,
        ];

        return $validator;
    }

    /**
     * 读取所有请求参数，如果有验证则验证
     * @param bool $in_parallel false: 以串联短路方式验证；true: 以并联方式验证，即使前面的验证不通过，也会继续验证后面的字段
     * @return array
     * @throws AppException
     */
    public function request(bool $in_parallel = false)
    {
        // 把没有手动调用 input() 的参数进行 input()
        $request_method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : '';
        if ($request_method === 'get') {
            $keys = array_keys($_GET);
        } else {
            $keys = array_keys($_POST);
        }

        $keys = array_diff($keys, array_keys($this->request));
        foreach ($keys as $key) {
            $this->input($key);
        }

        $data = [];
        $errors = [];
        foreach ($this->request as $k => $v) {
            /** @var Validator $validator */
            $validator = &$v['validator'];

            try {
                if (!empty($validator)) {
                    $validator->get();
                }

                $data[$k] = $v['value'];
            } catch (Exception $exception) {
                $errors[$k] = $exception->getMessage();

                if (!$in_parallel) {
                    break;
                }
            }
        }
        $this->request = [];

        array_filter($errors) || $errors = null;
        if ($errors) {
            AppException::panic(10001000, $errors);
        }

        return $data;
    }

    /**
     * 把本次请求的参数缓存起来
     * @return bool
     */
    public function flash()
    {
        return Svc::flash()->set('__old_request', array_merge($_GET, $_POST)) ? true : false;
    }

    /**
     * 上次请求的字段值
     * @param string $name
     * @param string $default
     * @return mixed
     */
    public function old(string $name = '', string $default = '')
    {
        if (!$this->old_request) {
            $this->old_request = Svc::flash()->get('__old_request');
        }

        if ($name) {
            return $this->old_request[$name] ?? $default;
        } else {
            return $this->old_request;
        }
    }

}

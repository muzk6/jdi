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


use JDI\Exceptions\AppException;

/**
 * 验证器
 * @package JDI\Services
 */
class Validator
{
    /**
     * @var string 用户角度的字段名
     */
    protected $title;

    /**
     * @var mixed 字段原值
     */
    protected $raw_value;

    /**
     * @var mixed 字段新值
     */
    protected $new_value;

    /**
     * @var array 规则回调集合
     */
    protected $rules = [];

    public function __construct($raw_value, $new_value)
    {
        $this->raw_value = $raw_value;
        $this->new_value = $new_value;
    }

    /**
     * 验证并返回参数值
     * @param string $title 用户角度的字段名，如果有自定义 message 则标题无效
     * @return bool
     * @throws AppException
     */
    public function get(string $title = '')
    {
        if ($title) {
            $this->title = $title;
        }

        foreach ($this->rules as $k => $v) {
            $v();
        }

        return $this->new_value;
    }

    /**
     * 用户角度的字段名，如果有自定义 message 则标题无效
     * @param string $title
     * @return Validator
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 标题
     * @return string
     */
    protected function getTitle()
    {
        return $this->title;
    }

    /**
     * 字段值
     * @return mixed
     */
    protected function getRawValue()
    {
        return $this->raw_value;
    }

    /**
     * 添加规则
     * @param string $name
     * @param callable $fn
     * @return $this
     */
    protected function addRule(string $name, callable $fn)
    {
        $this->rules[$name] = $fn;
        return $this;
    }

    /**
     * 占位符转换
     * @param string|array $message
     * @return mixed
     */
    protected function trans($message)
    {
        if (is_array($message)) {
            $text = $message[0];
            $placeholders = array_slice($message, 1);
            $placeholders['name'] = $this->getTitle();
            foreach ($placeholders as $k => $v) {
                $text = str_replace("{{$k}}", $v, $text);
            }
        } else {
            $text = $message;
        }

        return $text;
    }

    /**
     * 必须
     * @param string $message
     * @return Validator
     */
    public function required(string $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($message) {
            strlen(strval($this->getRawValue()))
            || AppException::panic($message
                ? $this->trans($message)
                : [10001100, 'name' => $this->getTitle()]
            );
        });
    }

    /**
     * 数字
     * @param string $message
     * @return Validator
     */
    public function numeric(string $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($message) {
            is_numeric($this->getRawValue())
            || AppException::panic($message
                ? $this->trans($message)
                : [10001101, 'name' => $this->getTitle()]
            );
        });
    }

    /**
     * 数组
     * @param string $message
     * @return Validator
     */
    public function arr(string $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($message) {
            is_array($this->getRawValue())
            || AppException::panic($message
                ? $this->trans($message)
                : [10001102, 'name' => $this->getTitle()]
            );
        });
    }

    /**
     * 邮箱地址
     * @param string $message
     * @return Validator
     */
    public function email(string $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($message) {
            !!filter_var($this->getRawValue(), FILTER_VALIDATE_EMAIL)
            || AppException::panic($message
                ? $this->trans($message)
                : [10001105, 'name' => $this->getTitle()]
            );
        });
    }

    /**
     * URL
     * @param string $message
     * @return Validator
     */
    public function url(string $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($message) {
            !!filter_var($this->getRawValue(), FILTER_SANITIZE_URL)
            || AppException::panic($message
                ? $this->trans($message)
                : [10001106, 'name' => $this->getTitle()]
            );
        });
    }

    /**
     * IP
     * @param string $message
     * @return Validator
     */
    public function ip(string $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($message) {
            !!filter_var($this->getRawValue(), FILTER_VALIDATE_IP)
            || AppException::panic($message
                ? $this->trans($message)
                : [10001107, 'name' => $this->getTitle()]
            );
        });
    }

    /**
     * 时间戳
     * @param string $message
     * @return Validator
     */
    public function timestamp(string $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($message) {
            strtotime(date('Y-m-d H:i:s', $this->getRawValue())) == $this->getRawValue()
            || AppException::panic($message
                ? $this->trans($message)
                : [10001108, 'name' => $this->getTitle()]
            );
        });
    }

    /**
     * 日期
     * @param string $message
     * @return Validator
     */
    public function date(string $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($message) {
            !!strtotime($this->getRawValue())
            || AppException::panic($message
                ? $this->trans($message)
                : [10001109, 'name' => $this->getTitle()]
            );
        });
    }

    /**
     * @param string $pattern
     * @param string|array $message
     * @return Validator
     */
    public function regex(string $pattern, string $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($pattern, $message) {
            !!preg_match($pattern, $this->getRawValue())
            || AppException::panic($message
                ? $this->trans($message)
                : [10001110, 'name' => $this->getTitle()]
            );
        });
    }

    /**
     * 在数组 $range 中
     * @param array $range
     * @param string|array $message
     * @return Validator
     */
    public function in(array $range, $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($range, $message) {
            in_array($this->getRawValue(), $range)
            || AppException::panic($message
                ? $this->trans($message)
                : [10001111, 'name' => $this->getTitle(), 'range' => implode(',', $range)]
            );
        });
    }

    /**
     * 不在数组 $range 中
     * @param array $range
     * @param string|array $message
     * @return Validator
     */
    public function notIn(array $range, $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($range, $message) {
            !in_array($this->getRawValue(), $range)
            || AppException::panic($message
                ? $this->trans($message)
                : [10001112, 'name' => $this->getTitle(),
                    'range' => implode(',', $range),
                ]
            );
        });
    }

    /**
     * 在闭合区间范围内
     * @param int $left 左区间
     * @param int $right 右区间
     * @param string|array $message
     * @return Validator
     */
    public function between(int $left, int $right, $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($left, $right, $message) {
            (($this->getRawValue() >= $left) && ($this->getRawValue() <= $right))
            || AppException::panic($message
                ? $this->trans($message)
                : [10001113, 'name' => $this->getTitle(),
                    'left' => $left,
                    'right' => $right,
                ]
            );
        });
    }

    /**
     * 不在闭合区间范围内
     * @param int $left 左区间
     * @param int $right 右区间
     * @param string|array $message
     * @return Validator
     */
    public function notBetween(int $left, int $right, $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($left, $right, $message) {
            (($this->getRawValue() >= $left) && ($this->getRawValue() <= $right))
            && AppException::panic($message
                ? $this->trans($message)
                : [10001114, 'name' => $this->getTitle(),
                    'left' => $left,
                    'right' => $right,
                ]
            );
        });
    }

    /**
     * 最大值
     * @param int $max
     * @param string|array $message
     * @return Validator
     */
    public function max(int $max, $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($max, $message) {
            $this->getRawValue() <= $max
            || AppException::panic($message
                ? $this->trans($message)
                : [10001115, 'name' => $this->getTitle(), 'max' => $max]
            );
        });
    }

    /**
     * 最小值
     * @param int $min
     * @param string|array $message
     * @return Validator
     */
    public function min(int $min, $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($min, $message) {
            $this->getRawValue() >= $min
            || AppException::panic($message
                ? $this->trans($message)
                : [10001116, 'name' => $this->getTitle(), 'min' => $min]
            );
        });
    }

    /**
     * 字符串长度
     * @param int $len
     * @param string|array $message
     * @return Validator
     */
    public function length(int $len, $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($len, $message) {
            strlen($this->getRawValue()) == $len
            || AppException::panic($message
                ? $this->trans($message)
                : [10001117, 'name' => $this->getTitle(), 'len' => $len]
            );
        });
    }

    /**
     * 和指定的请求字段值一致
     * @param string $field_name
     * @param string $message
     * @return Validator
     */
    public function confirm(string $field_name, string $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($field_name, $message) {
            $this->getRawValue() == (isset($_REQUEST[$field_name]) ? trim($_REQUEST[$field_name]) : '')
            || AppException::panic($message
                ? $this->trans($message)
                : [10001118, 'name' => $this->getTitle()]
            );
        });
    }

    /**
     * 大于
     * @param int $num
     * @param string|array $message
     * @return Validator
     */
    public function gt(int $num, $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($num, $message) {
            $this->getRawValue() > $num
            || AppException::panic($message
                ? $this->trans($message)
                : [10001119, 'name' => $this->getTitle(), 'num' => $num]
            );
        });
    }

    /**
     * 小于
     * @param int $num
     * @param string|array $message
     * @return Validator
     */
    public function lt(int $num, $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($num, $message) {
            $this->getRawValue() < $num
            || AppException::panic($message
                ? $this->trans($message)
                : [10001120, 'name' => $this->getTitle(), 'num' => $num]
            );
        });
    }

    /**
     * 大于等于
     * @param int $num
     * @param string|array $message
     * @return Validator
     */
    public function gte(int $num, $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($num, $message) {
            $this->getRawValue() >= $num
            || AppException::panic($message
                ? $this->trans($message)
                : [10001121, 'name' => $this->getTitle(), 'num' => $num]
            );
        });
    }

    /**
     * 小于等于
     * @param int $num
     * @param string|array $message
     * @return Validator
     */
    public function lte(int $num, $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($num, $message) {
            $this->getRawValue() <= $num
            || AppException::panic($message
                ? $this->trans($message)
                : [10001122, 'name' => $this->getTitle(), 'num' => $num]
            );
        });
    }

    /**
     * 等于
     * @param string $val
     * @param string|array $message
     * @return Validator
     */
    public function eq(string $val, $message = '')
    {
        return $this->addRule(__FUNCTION__, function () use ($val, $message) {
            $this->getRawValue() == $val
            || AppException::panic($message
                ? $this->trans($message)
                : [10001123, 'name' => $this->getTitle(), 'val' => $val]
            );
        });
    }

    /**
     * 自定义验证
     * @param callable $fn 不需要 return, 使用 panic() 表示验证不通过
     * @return Validator
     */
    public function custom(callable $fn)
    {
        return $this->addRule(__FUNCTION__, function () use ($fn) {
            $fn($this->getRawValue());
        });
    }

}

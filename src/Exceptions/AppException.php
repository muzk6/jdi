<?php

/*
 * This file is part of the muzk6/jdi.
 *
 * (c) muzk6 <muzk6x@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace JDI\Exceptions;

use Exception;
use JDI\Support\Svc;
use Throwable;

/**
 * 业务异常类，支持 set/get 数组数据
 * @package JDI\Exceptions
 */
final class AppException extends Exception implements Throwable
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * 设置附带抛出的数组
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 附带抛出的数组
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 抛出业务异常对象
     * @param string|int|array $message_or_code 错误码或错误消息
     * <p>带有参数的错误码，使用 array: [10002001, 'name' => 'tom'] 或 [10002001, ['name' => 'tom']]</p>
     * @param array $data 附加数组
     * @throws AppException
     */
    public static function panic($message_or_code = '', array $data = [])
    {
        if (is_array($message_or_code)) {
            $code = $message_or_code[0];
            $lang_params = (isset($message_or_code[1]) && is_array($message_or_code[1]))
                ? $message_or_code[1]
                : array_slice($message_or_code, 1);
            $message = Svc::lang()->trans($code, $lang_params);

            $exception = new AppException($message, $code);
        } elseif (is_int($message_or_code)) {
            $code = $message_or_code;
            $message = Svc::lang()->trans($code);
            $exception = new AppException($message, $code);
        } else {
            $message = $message_or_code;
            $exception = new AppException($message);
        }

        $data && $exception->setData($data);
        throw $exception;
    }

}

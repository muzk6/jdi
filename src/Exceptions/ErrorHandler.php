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
 * 异常错误 handler
 * @package JDI\Exceptions
 */
class ErrorHandler
{
    /**
     * set_error_handler
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @return bool
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $error_type = array(
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSING ERROR',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE ERROR',
            E_CORE_WARNING => 'CORE WARNING',
            E_COMPILE_ERROR => 'COMPILE ERROR',
            E_COMPILE_WARNING => 'COMPILE WARNING',
            E_USER_ERROR => 'USER ERROR',
            E_USER_WARNING => 'USER WARNING',
            E_USER_NOTICE => 'USER NOTICE',
            E_STRICT => 'STRICT NOTICE',
            E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
        );

        $data = [
            'message' => $errstr,
            'level' => $error_type[$errno] ?? $errno,
            'file' => "{$errfile}:{$errline}",
            'line_content' => self::getLineContent($errfile, $errline),
            'backtrace' => [],
        ];

        $trace = (new Exception())->getTraceAsString();
        if (trim($trace)) {
            $data['backtrace'] = explode("\n", $trace);
        }

        Svc::log()->file('error_handler', $data, 'error');

        return false;
    }

    /**
     * set_exception_handler
     * @param Throwable $exception
     * @throws Throwable
     */
    public static function exceptionHandler(Throwable $exception)
    {
        $filename = $exception->getFile();
        $line_num = $exception->getLine();

        $data = [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => "{$filename}:{$line_num}",
            'line_content' => self::getLineContent($filename, $line_num),
            'backtrace' => [],
        ];

        $trace = $exception->getTraceAsString();
        if (trim($trace)) {
            $data['backtrace'] = explode("\n", $trace);
        }

        Svc::log()->file('exception_handler', $data, 'error');

        throw $exception;
    }

    /**
     * 取文件指定行内容
     * @param string $filename
     * @param int $line
     * @return string
     */
    public static function getLineContent(string $filename, int $line)
    {
        $source_code = '';
        $handle = @fopen($filename, 'r');
        if ($handle) {
            $current = 0;
            while (is_resource($handle) && !feof($handle)) {
                $buffer = fgets($handle, 1024);
                $current++;

                if ($line == $current) {
                    $source_code = trim($buffer);
                    break;
                }
            }

            fclose($handle);
        }

        return $source_code;
    }

    /**
     * display_errors 配置是否有打开
     * @return bool
     */
    public static function is_display_errors()
    {
        return error_reporting() === 0 || in_array(strtolower(ini_get('display_errors')), ['1', 'on']);
    }

}

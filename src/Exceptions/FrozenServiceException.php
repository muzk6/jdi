<?php


namespace JDI\Exceptions;


use Exception;
use Throwable;

class FrozenServiceException extends Exception implements Throwable
{
    public function __construct(string $offset)
    {
        parent::__construct("不能覆盖已经执行过回调的容器项: {$offset}");
    }
}

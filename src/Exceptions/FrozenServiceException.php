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
use Throwable;

class FrozenServiceException extends Exception implements Throwable
{
    public function __construct(string $offset)
    {
        parent::__construct("不能覆盖已经执行过回调的容器项: {$offset}");
    }
}

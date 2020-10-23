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

/**
 * 语言翻译器
 * @package JDI\Services
 */
class Lang
{
    /**
     * @var string 目标语言
     */
    protected $lang;

    /**
     * @var array 语言字典
     */
    protected $dict = [];

    /**
     * @param array $conf
     */
    public function __construct(array $conf)
    {
        $this->lang = $conf['lang'];
        $this->dict = $conf['dict'];
    }

    /**
     * 当前目标语言
     * @param string $lang
     */
    public function setLang(string $lang)
    {
        $this->lang = $lang;
    }

    /**
     * 当前目标语言
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * 转换成当前语言的文本
     * @param int $code
     * @param array $params
     * @return string
     */
    public function trans(int $code, array $params = [])
    {
        $lang = &$this->dict[$this->lang];
        if (!isset($lang)) {
            trigger_error("字典配置 {$this->$lang} 不存在", E_USER_ERROR);
        }

        if (is_callable($lang)) {
            $lang = call_user_func($lang);
        }

        $text = '';
        if (isset($lang[$code])) {
            $text = $lang[$code];
        } else {
            trigger_error("字典索引 {$this->$lang} {$code} 不存在", E_USER_WARNING);
        }

        if ($text && $params) {
            foreach ($params as $k => $v) {
                $text = str_replace("{{$k}}", $v, $text);
            }
        }

        return $text;
    }

}

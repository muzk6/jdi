<?php


namespace JDI\Tests\Services;


use PHPUnit\Framework\TestCase;

class FlashTest extends TestCase
{
    public function test()
    {
        flash_set('test', '');
        $this->assertEquals(false, flash_has('test'));
        $this->assertEquals(true, flash_exists('test'));

        flash_del('test');
        $this->assertEquals(false, flash_exists('test'));

        flash_set('test2', null);
        $this->assertEquals(false, flash_has('test2'));
        $this->assertEquals(true, flash_exists('test2'));
    }
}
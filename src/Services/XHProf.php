<?php


namespace JDI\Services;

/**
 * XHProf
 * @package JDI\Services
 */
class XHProf
{
    protected $conf;

    protected $start_time;

    public function __construct(array $conf)
    {
        $this->conf = $conf;
    }

    /**
     * 按配置自动开启
     * @return bool
     */
    public function auto()
    {
        if (empty($this->conf['enable'])) {
            return false;
        }

        if (!(mt_rand(1, 100) <= $this->conf['probability'] * 100)) {
            return false;
        }

        return $this->start();
    }

    /**
     * 手动开启
     * @return bool
     */
    public function start()
    {
        if (!extension_loaded('tideways_xhprof')) {
            trigger_error('请安装扩展: tideways_xhprof', E_USER_ERROR);
        }

        $this->start_time = microtime(true);
        tideways_xhprof_enable(TIDEWAYS_XHPROF_FLAGS_CPU | TIDEWAYS_XHPROF_FLAGS_MEMORY);

        $alias = $this;
        register_shutdown_function(function () use ($alias) {
            $alias->shutdown();
        });

        return true;
    }

    /**
     * 结束回调
     * @return bool
     */
    protected function shutdown()
    {
        $end_time = microtime(true);
        $cost_time = $end_time - $this->start_time;
        if ($cost_time < $this->conf['min_time']) {
            return false;
        }

        $path = $this->conf['path_data'];
        if (!file_exists($path)) {
            mkdir($path, 0744, true);
        }

        if (PHP_SAPI == 'cli') {
            $cmd = basename($_SERVER['argv'][0]);
            $url = $cmd . ' ' . implode(' ', array_slice($_SERVER['argv'], 1));
        } else {
            $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        }

        $name = "{$url};{$cost_time}";
        $name = rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($name)), '=');

        $data = tideways_xhprof_disable();
        file_put_contents(
            sprintf('%s/%s.%s.xhprof', $path, $name, uniqid()),
            serialize($data)
        );
    }
}

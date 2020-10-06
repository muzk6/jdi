<?php


namespace JDI\Services;

/**
 * Xdebug Trace
 * @package JDI\Services
 */
class Xdebug
{
    protected $max_depth;
    protected $max_data;
    protected $max_children;

    /**
     * @var array
     */
    protected $conf;

    public function __construct(array $conf)
    {
        $this->conf = $conf;
        $this->initDisplaySetting();
    }

    /**
     * 最大能显示的 数组、对象 维度
     * @param int $max_depth
     * @return $this
     */
    public function setMaxDepth(int $max_depth)
    {
        $this->max_depth = $max_depth;

        return $this;
    }

    /**
     * 最大能显示的字符串长度
     * @param int $max_data
     * @return $this
     */
    public function setMaxData(int $max_data)
    {
        $this->max_data = $max_data;

        return $this;
    }

    /**
     * 最多能显示的 数组、对象 成员数
     * @param int $max_children
     * @return $this
     */
    public function setMaxChildren(int $max_children)
    {
        $this->max_children = $max_children;

        return $this;
    }

    /**
     * 初始化为默认的显示设置
     */
    protected function initDisplaySetting()
    {
        $this->setMaxDepth(intval(ini_get('xdebug.var_display_max_depth')));
        $this->setMaxData(intval(ini_get('xdebug.var_display_max_data')));
        $this->setMaxChildren(intval(ini_get('xdebug.var_display_max_children')));
    }

    /**
     * 按前置条件自动开启跟踪
     */
    public function auto()
    {
        $trace_start = false;
        $name = '';

        // 从 cgi 开启
        if (svc_whitelist()->isSafeIp() || svc_whitelist()->isSafeCookie()) {
            if (isset($_REQUEST['_xt'])) {
                $name = $_REQUEST['_xt'];
            } elseif (isset($_COOKIE['_xt'])) {
                $name = $_COOKIE['_xt'];
            }

            if ($name) {
                $trace_start = true;

                isset($_REQUEST['_max_depth']) && $this->setMaxDepth(intval($_REQUEST['_max_depth']));
                isset($_REQUEST['_max_data']) && $this->setMaxData(intval($_REQUEST['_max_data']));
                isset($_REQUEST['_max_children']) && $this->setMaxChildren(intval($_REQUEST['_max_children']));
            }
        }

        // 从 cli/trace.php 开启
        $trace_conf_file = $this->conf['path_data'] . '/.tracerc';
        if (!$trace_start && file_exists($trace_conf_file)) {
            $trace_conf = include($trace_conf_file);

            $url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $trace_conf['url'] = preg_replace('#https?://#', '', $trace_conf['url']);
            if ($trace_conf['expire'] > time() // 检查过期
                && preg_match("#^{$trace_conf['url']}#i", $url) // 检查 url path 是否匹配
                && (!$trace_conf['user_id'] || (svc_auth()->isLogin() && $trace_conf['user_id'] == svc_auth()->getUserId())) // 有指定用户时，检查特定用户
            ) {
                $trace_start = true;

                $this->setMaxDepth($trace_conf['max_depth']);
                $this->setMaxData($trace_conf['max_data']);
                $this->setMaxChildren($trace_conf['max_children']);

                $name = $trace_conf['name'];
            }
        }

        if ($trace_start) {
            $this->start($name);
        }
    }

    /**
     * 手动跟踪
     * @param string $trace_name 日志名，即日志文件名的 xt: 的值
     * <p>建议把 uniqid() 作为 $name</p>
     * @return bool
     */
    public function start($trace_name)
    {
        if (!extension_loaded('xdebug')) {
            trigger_error('请安装扩展: xdebug', E_USER_ERROR);
        }

        if (!file_exists($this->conf['path_data'])) {
            mkdir($this->conf['path_data'], 0744, true);
        }

        ini_set('xdebug.var_display_max_depth', $this->max_depth);
        ini_set('xdebug.var_display_max_data', $this->max_data);
        ini_set('xdebug.var_display_max_children', $this->max_children);
        $this->initDisplaySetting();

        ini_set('xdebug.trace_format', 1);
        ini_set('xdebug.collect_return', 1);
        ini_set('xdebug.collect_params', 4);
        ini_set('xdebug.collect_assignments', 1);
        ini_set('xdebug.show_mem_delta', 1);
        ini_set('xdebug.collect_includes', 1);

        $url = '';
        if (PHP_SAPI == 'cli') {
            $cmd = basename($_SERVER['argv'][0]);
            $url = $cmd . ' ' . implode(' ', array_slice($_SERVER['argv'], 1));
        } else {
            if (isset($_SERVER['HTTP_HOST'])) {
                $url .= $_SERVER['HTTP_HOST'];
            }

            if (isset($_SERVER['REQUEST_URI'])) {
                $url .= $_SERVER['REQUEST_URI'];
            }
        }

        $trace_data = [
            'uuid' => uniqid(),
            'trace' => $trace_name,
            'user_id' => svc_auth()->getUserId(),
            'url' => $url,
        ];
        $trace_filename = rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode(json_encode($trace_data))), '=');

        register_shutdown_function(function () {
            xdebug_stop_trace();
        });

        xdebug_start_trace($this->conf['path_data'] . '/' . $trace_filename);

        return true;
    }
}

<?php


namespace JDI\Services;


class Log
{
    /**
     * @var string 日志路径
     */
    protected $path_data;

    public function __construct(array $conf)
    {
        $this->path_data = $conf['path_data'];
    }

    /**
     * 文件日志
     * @param string $index 日志名(索引)
     * @param array|string $data 日志内容
     * @param string $filename 日志文件名前缀
     * @param int $trace_index 调用处堆栈索引
     * @return int|null
     */
    public function file(string $index, $data, string $filename = 'app', $trace_index = 0)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $trace_index + 1)[$trace_index];
        $filename = trim(str_replace('/', '', $filename));

        $log = json_encode([
            'time' => date('Y-m-d H:i:s'),
            'index' => $index,
            'request_id' => isset($_SERVER['REQUEST_TIME_FLOAT']) ? md5(strval($_SERVER['REQUEST_TIME_FLOAT'])) : '',
            'file' => "{$trace['file']}:{$trace['line']}",
            'sapi' => PHP_SAPI,
            'hostname' => php_uname('n'),
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'host' => $_SERVER['HTTP_HOST'] ?? '',
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'client_ip' => get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'user_id' => svc_auth()->getUserId(),
            'data' => $data,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

        $path = sprintf('%s/%s_%s.log',
            $this->path_data, $filename, date('Ym'));

        return file_put_contents($path, $log . PHP_EOL, FILE_APPEND);
    }
}

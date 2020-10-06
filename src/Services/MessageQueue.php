<?php

namespace JDI\Services;

use ErrorException;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * 消息队列
 * @package JDI\Services
 */
class MessageQueue
{
    /**
     * @var AMQPStreamConnection
     */
    protected $connection;

    /**
     * @var AMQPChannel[]
     */
    protected $channels = [];

    /**
     * @var array 配置集
     */
    protected $conf;

    /**
     * @var array 当前配置
     */
    protected $host = [];

    /**
     * @var callable 服务实例化回调
     */
    protected $connection_handler;

    /**
     * Queue constructor.
     * @param array $conf
     * @param callable $connection_handler 服务实例化回调
     */
    public function __construct(array $conf, callable $connection_handler)
    {
        if (!class_exists('\PhpAmqpLib\Connection\AMQPStreamConnection')) {
            trigger_error('"composer require php-amqplib/php-amqplib:^2.9" at first', E_USER_ERROR);
        }

        $this->conf = $conf;
        $this->connection_handler = $connection_handler;
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        foreach ($this->channels as $channel) {
            $channel->close();
        }

        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * 获取连接资源
     * @return AMQPStreamConnection
     */
    public function getConnection()
    {
        if (!$this->connection) {
            shuffle($this->conf);
            foreach ($this->conf as $host) {
                try {
                    $this->connection = call_user_func($this->connection_handler, $host);
                    $this->host = $host;
                    break;
                } catch (Exception $exception) {
                    trigger_error($exception->getMessage() . ': ' . json_encode($host, JSON_UNESCAPED_SLASHES), E_USER_WARNING);
                }
            }
        }

        return $this->connection;
    }

    /**
     * 消息队列发布
     * @param string $queue 队列名称
     * @param array $data
     * @param string $exchange_name 交换器名称，缺省时取配置
     * @param string $exchange_type 交换器类型，缺省时取配置
     */
    public function publish(string $queue, array $data, string $exchange_name = '', string $exchange_type = '')
    {
        $exchange_name || $exchange_name = $this->host['exchange_name'];
        $exchange_type || $exchange_type = $this->host['exchange_type'];

        $channel_key = md5("publish_{$exchange_name}_{$exchange_type}_{$queue}");
        if (!isset($this->channels[$channel_key])) {
            $this->channels[$channel_key] = $this->getConnection()->channel(); // 这里每次返回都是新的 channel 对象
            $this->channels[$channel_key]->exchange_declare($exchange_name, $exchange_type, false, true, false);
            $this->channels[$channel_key]->queue_declare($queue, false, true, false, false);
        }

        $msg = new AMQPMessage(
            json_encode($data),
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );

        $this->channels[$channel_key]->basic_publish($msg, '', $queue);
    }

    /**
     * 消息队列消费
     * @param string $queue 队列名称
     * @param callable $callback
     */
    public function consume(string $queue, callable $callback)
    {
        if (PHP_SAPI != 'cli') {
            return;
        }

        ini_set('memory_limit', -1);

        $channel_key = md5("consume_{$queue}");
        if (!isset($this->channels[$channel_key])) {
            $this->channels[$channel_key] = $this->getConnection()->channel(); // 这里每次返回都是新的 channel 对象
            $this->channels[$channel_key]->queue_declare($queue, false, true, false, false);
        }

        $this->channels[$channel_key]->basic_qos(null, 1, null);

        $script_time = time();
        $file_stats = [];
        $this->channels[$channel_key]->basic_consume($queue, '', false, false, false, false,
            function ($msg) use ($queue, $callback, $script_time, &$file_stats) {
                // 每300秒退出 worker, 比销毁容器更安全(考虑到开发者可能用静态类)，释放 mysql 之类的长连接
                if (time() - $script_time >= 300) {
                    echo sprintf('[Exit At %s, Timeout.]', date('Y-m-d H:i:s')) . PHP_EOL;
                    exit;
                }

                foreach ($file_stats as $file => $file_stat) {
                    // 检查文件最后修改时间、大小，不同就直接结束进程(使用 supervisor 进行重启)；同时比较文件大小，防止开发机与运行环境时间不一致
                    clearstatcache(true, $file);
                    if ($file_stat['mtime'] != filemtime($file)
                        || $file_stat['size'] != filesize($file)) {
                        echo sprintf('[Exit At %s, Files Updated.]', date('Y-m-d H:i:s')) . PHP_EOL;
                        exit;
                    }
                }

                /** @var AMQPChannel $channel */
                $channel = $msg->delivery_info['channel'];

                $params = json_decode($msg->body, true);
                $start_time = microtime(true);

                $temp_id = uniqid();
                echo str_repeat('-', 30) . "<{$queue} id={$temp_id}>" . str_repeat('-', 30) . PHP_EOL;
                echo 'Params: ' . PHP_EOL;
                var_export($params);
                echo PHP_EOL;

                try {
                    $result = $callback($params);
                    $channel->basic_ack($msg->delivery_info['delivery_tag']);

                    echo 'Result: ' . PHP_EOL;
                    var_export($result);
                    echo PHP_EOL;
                } catch (Exception $exception) {
                    $channel->basic_nack($msg->delivery_info['delivery_tag']);

                    echo 'Exception: ' . PHP_EOL;
                    var_export($exception->getMessage());
                }

                $end_time = microtime(true);
                echo 'StartTime: ' . date('Y-m-d H:i:s', $start_time);
                echo '; EndTime: ' . date('Y-m-d H:i:s', $end_time);
                echo '; Elapse(sec): ' . ($end_time - $start_time);
                echo '; PeakMemory(MB): ' . (memory_get_peak_usage(true) / 1024 / 1024) . PHP_EOL;
                echo str_repeat('-', 29) . "</{$queue} id={$temp_id}>" . str_repeat('-', 30) . PHP_EOL;

                // 执行完业务 $callback 后，get_included_files() 才能取到所有相关文件，并及时保存文件状态
                $included_files = get_included_files();
                foreach ($included_files as $included_file) {
                    clearstatcache(true, $included_file);
                    $mtime = filemtime($included_file);
                    $size = filesize($included_file);

                    if (!isset($file_stats[$included_file])) {
                        $file_stats[$included_file] = ['mtime' => $mtime, 'size' => $size];
                    }
                }
            }
        );

        // 注册进程信号，防止 worker 中途被强制结束
        $signal_handler = function ($signal) {
            $map = array(
                SIGTERM => 'SIGTERM',
                SIGHUP => 'SIGHUP',
                SIGINT => 'SIGINT',
                SIGQUIT => 'SIGQUIT',
            );
            $signal_name = $map[$signal] ?? $signal;

            echo sprintf("[Exit Softly At %s, By Signal: {$signal_name}.]", date('Y-m-d H:i:s')) . PHP_EOL;
            exit;
        };

        pcntl_signal(SIGTERM, $signal_handler); // supervisor stop/restart 使用的信号
        pcntl_signal(SIGHUP, $signal_handler);
        pcntl_signal(SIGINT, $signal_handler);
        pcntl_signal(SIGQUIT, $signal_handler);

        while ($this->channels[$channel_key]->is_consuming()) {
            try {
                $this->channels[$channel_key]->wait();
            } catch (ErrorException $e) {
                trigger_error($e->getMessage(), E_USER_ERROR);
            }

            pcntl_signal_dispatch();
        }
    }
}

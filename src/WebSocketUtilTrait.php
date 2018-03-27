<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/4/8 0008
 * Time: 23:10
 */

namespace MyLib\WebSocket\Util;

use inhere\console\io\Input;
use inhere\console\io\Output;
use MyLib\SysUtil\Cli;
use Inhere\Library\Traits\FixedEventTrait;
use Inhere\Library\Traits\ConfigTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Class WebSocketUtilTrait
 * @package MyLib\WebSocket\Util
 */
trait WebSocketUtilTrait
{
    use FixedEventTrait;
    use ConfigTrait {
        setConfig as tSetConfig;
    }

    /**
     * all available opCodes
     * @var array
     */
    protected static $opCodes = [
        'continuation' => self::OPCODE_CONT, // 0
        'text' => self::OPCODE_TEXT,   // 1
        'binary' => self::OPCODE_BINARY, // 2
        'close' => self::OPCODE_CLOSE,  // 8
        'ping' => self::OPCODE_PING,   // 9
        'pong' => self::OPCODE_PONG,   // 10
    ];

    /**
     * the driver name
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var Output
     */
    protected $cliOut;

    /**
     * @var Input
     */
    protected $cliIn;

    /**
     * @var bool
     */
    private $initialized;

    /**
     * @var array
     */
    protected $config = [
        // server address HOST:PORT
        'server' => '',

        // enable ssl
        'enable_ssl' => false,

        // 数据块大小 byte 发送数据时将会按这个大小拆分发送
        'fragment_size' => 1024,

        // 'buffer_size' => 8192, // 8kb

        // 设置写(发送)缓冲区 最大2m @see `StreamsServer::setBufferSize()`
        'write_buffer_size' => 2097152,

        // 设置读(接收)缓冲区 最大2m
        'read_buffer_size' => 2097152,

        // while 循环时间间隔 毫秒(ms) millisecond. 1s = 1000ms = 1000 000us
        'sleep_time' => 500,

        // 连接超时时间 s
        'timeout' => 2.2,

        // 最大数据接收长度 1024 / 2048 byte
        'max_data_len' => 2048,
    ];

    /**
     * @return array
     */
    protected function appendDefaultConfig(): array
    {
        return [
            // ...
        ];
    }

    /**
     * WSAbstracter constructor.
     * @param array $config
     */
    protected function initConfig(array $config)
    {
        $this->cliIn = new Input();
        $this->cliOut = new Output();

        if ($append = $this->appendDefaultConfig()) {
            $this->config = array_merge($this->config, $append);
        }

        $this->setConfig($config);

        $this->init();

        $this->initialized = true;
    }

    /**
     * init
     */
    protected function init()
    {
        $this->handleCommandAndConfig();
    }

    /**
     * handleCommandAndConfig
     */
    protected function handleCommandAndConfig()
    {}

    /**
     * Logs data to disk or stdout
     * @param string $msg
     * @param int|string $level
     * @param array $data
     */
    public function log(string $msg, $level = LogLevel::INFO, array $data = [])
    {
        if ($this->isDebug() && ($info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1))) {
            $msg = sprintf(' [%s:%d] ', $info['class'] ?? 'UNKNOWN', $info['line'] ?? -1) . $msg;
        }

        $this->logger->log($level, $msg, $data);
    }

    /**
     * Logs data to stdout
     * @param string $text
     * @param bool $nl
     * @param bool|int $quit
     */
    protected function stdout($text, $nl = true, $quit = false)
    {
        // CliHelper::stdout($text, $nl, $quit);
        $this->getCliOut()->write($text, $nl, $quit);
    }

    /**
     * Logs data to stderr
     * @param string $text
     * @param bool $nl
     * @param bool|int $quit
     */
    protected function stderr($text, $nl = true, $quit = -200)
    {
        Cli::stderr($text, $nl, $quit);
    }

    /**
     * @return array
     */
    public static function getOpCodes(): array
    {
        return self::$opCodes;
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     */
    public function setConfig(array $config)
    {
        if ($this->initialized) {
            throw new \InvalidArgumentException('Has been initialize completed. don\'t allow change config.');
        }

        $this->tSetConfig($config);
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return $this->getValue($name, $default);
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getDriverName(): string
    {
        return static::DRIVER_NAME;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        if (!$this->host) {
            $this->host = self::DEFAULT_HOST;
        }

        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        if (!$this->port || $this->port <= 0) {
            $this->port = self::DEFAULT_PORT;
        }

        return $this->port;
    }

    /**
     * @return Output
     */
    public function getCliOut(): Output
    {
        return $this->cliOut;
    }

    /**
     * @param Output $output
     */
    public function setCliOut(Output $output)
    {
        $this->cliOut = $output;
    }

    /**
     * @return Input
     */
    public function getCliIn(): Input
    {
        return $this->cliIn;
    }

    /**
     * @param Input $cliIn
     */
    public function setCliIn(Input $cliIn)
    {
        $this->cliIn = $cliIn;
    }

    /**
     * Generate a random string for WebSocket key.(for client)
     * @return string Random string
     * @throws \Exception
     */
    public function genKey(): string
    {
        $key = '';
        $chars = self::TOKEN_CHARS;
        $chars_length = \strlen($chars);

        for ($i = 0; $i < 16; $i++) {
            $key .= $chars[\random_int(0, $chars_length - 1)]; //mt_rand
        }

        return \base64_encode($key);
    }

    /**
     * Generate WebSocket sign.(for server)
     * @param string $key
     * @return string
     */
    public function genSign(string $key): string
    {
        return \base64_encode(sha1(trim($key) . self::SIGN_KEY, true));
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return (bool)$this->getValue('debug', false);
    }

    /**
     * @param mixed $messages
     * @param bool $nl
     * @param bool|int $exit
     */
    public function print($messages, $nl = true, $exit = false)
    {
        $this->cliOut->write($messages, $nl, $exit);
    }

    /**
     * @param int $code
     */
    protected function quit($code = 0)
    {
        exit((int)$code);
    }
}

<?php

namespace DigitaleHelden\Logger;

use Gelf\Publisher;
use Gelf\Transport\UdpTransport;
use Monolog\Handler\GelfHandler;
use Monolog\Handler\RotatingFileHandler;

/**
 * Class Logger
 * @package DigitaleHelden\Logger
 */
class Logger
{
    /**
     * @const string
     */
    public const HANDLER = 'HANDLER';

    /**
     * @const bool
     */
    public const SESSION = 'SESSION';

    /**
     * @const string
     */
    public const SESSION_NAME = 'SESSION_NAME';

    /**
     * @var null
     */
    public static $instance = null;

    /**
     * @var null
     */
    public $logger = null;

    /**
     * @var string
     */
    protected $facility = '';

    /**
     * @var array
     */
    public $options =
    [
        self::HANDLER => 'gelf',
        self::SESSION => true,
        self::SESSION_NAME => '_uid'
    ];


    /**
     * @param string $facility
     * @param array $options
     * @throws Exception
     */
    protected function __construct($facility, array $options = [])
    {
        $this->facility = $facility;
        $this->options = array_merge($this->options, $options);

        $this->logger = new \Monolog\Logger($this->facility);
        $this->logger->pushHandler($this->createHandler());

        $this->init();
    }


    /**
     * @param $facility
     * @param array $options
     * @throws Exception
     */
    public static function create($facility, array $options = [])
    {
        if(is_null(self::$instance))
        {
            self::$instance = new self($facility, $options);
        }
    }


    /**
     * @return GelfHandler|RotatingFileHandler
     * @throws Exception
     */
    protected function createHandler()
    {
        switch($this->options[self::HANDLER])
        {
            case 'gelf':
                $transport = new UdpTransport('91.218.23.60', 12201, UdpTransport::CHUNK_SIZE_LAN);
                $publisher = new Publisher($transport);
                $handler = new GelfHandler($publisher);
                return $handler;
            case 'file':
                if(defined('ABSPATH'))
                {
                    $dir = ABSPATH . 'wp-content/logs';
                }else{
                    $dir = dirname(__FILE__) . '/../../../../logs';
                }
               if(!is_dir($dir))
               {
                   mkdir($dir, 0775);
               }
               if(!is_file($dir . '/.htaccess'))
               {
                   file_put_contents($dir . '/.htaccess', "Options -Indexes\norder deny,allow\ndeny from all");
               }
               $handler = new RotatingFileHandler(sprintf('%s/%s.log', $dir, $this->facility));
               $handler->setFilenameFormat('{date}', 'Y-m-d');
               return $handler;
            default:
                throw new Exception(sprintf('Log handler %s not supported', $this->options[self::HANDLER]));
        }
    }


    /**
     *
     */
    protected function init()
    {
        if((bool)$this->options[self::SESSION])
        {
            if(isset($_COOKIE) && !isset($_COOKIE[self::SESSION_NAME]))
            {
                setcookie(self::SESSION_NAME, md5(uniqid(rand() + time(), true)), 0, '/');
            }
        }
    }


    /**
     * @return null
     * @throws Exception
     */
    public static function logger()
    {
        if(is_null(self::$instance))
        {
            throw new Exception('Logger has not been initialized yet!');
        }
        return self::$instance;
    }


    /**
     * @param $message
     * @param array $context
     * @return mixed
     */
    public function debug($message, array $context = [])
    {
        return self::$instance->logger->debug($message, static::compile($context));
    }


    /**
     * @param $message
     * @param array $context
     * @return mixed
     */
    public function info($message, array $context = [])
    {
        return self::$instance->logger->info($message, static::compile($context));
    }


    /**
     * @param $message
     * @param array $context
     * @return mixed
     */
    public function notice($message, array $context = [])
    {
        return self::$instance->logger->notice($message, static::compile($context));
    }


    /**
     * @param $message
     * @param array $context
     * @return mixed
     */
    public function warning($message, array $context = [])
    {
        return self::$instance->logger->warning($message, static::compile($context));
    }


    /**
     * @param $message
     * @param array $context
     * @return mixed
     */
    public function error($message, array $context = [])
    {
        return self::$instance->logger->error($message, static::compile($context));
    }


    /**
     * @param $message
     * @param array $context
     * @return mixed
     */
    public function critical($message, array $context = [])
    {
        return self::$instance->logger->critical($message, static::compile($context));
    }


    /**
     * @param $message
     * @param array $context
     * @return mixed
     */
    public function alert($message, array $context = [])
    {
        return self::$instance->logger->alert($message, static::compile($context));
    }


    /**
     * @param $message
     * @param array $context
     * @return mixed
     */
    public function emergency($message, array $context = [])
    {
        return self::$instance->logger->emergency($message, static::compile($context));
    }


    /**
     * @param array|null $context
     * @return array
     */
    protected static function compile($context)
    {
        $context = (array)$context;

        $origin = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)[1];

        $context = $context +
        [
            'host' => ((isset($_SERVER) && isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : php_uname('n')),
            'file' => $origin['file'],
            'line' => $origin['line'],
        ];
        if((bool)self::$instance->options[self::SESSION] && isset($_COOKIE[self::SESSION_NAME]))
        {
            $context['uid'] = $_COOKIE[self::SESSION_NAME];
        }

        return $context;
    }


    /**
     * @param $name
     * @param $arguments
     * @return false|mixed|null
     * @throws Exception
     */
    public static function __callStatic($name, $arguments)
    {
        $methods =
        [
            'debug',
            'info',
            'notice',
            'warning',
            'error',
            'critical',
            'alert',
            'emergency'
        ];
        if(in_array($name, $methods))
        {
            return call_user_func_array([self::logger(), $name], [$arguments[0], $arguments[1]]);
        }
        return null;
    }
}
<?php

namespace DigitaleHelden\Logger;

use Gelf\Publisher;
use Monolog\ErrorHandler;
use Monolog\Handler\GelfHandler;
use Gelf\Transport\UdpTransport;
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
    public const ERROR_HANDLER = 'ERROR_HANDLER';

    /**
     * @const bool
     */
    public const EXCEPTION_HANDLER = 'EXCEPTION_HANDLER';

    /**
     * @const bool
     */
    public const FATAL_HANDLER = 'FATAL_HANDLER';

    /**
     * @const bool
     */
    public const BYPASS = 'BYPASS';

    /**
     * @const bool
     */
    public const SESSION_CREATE = 'SESSION_CREATE';

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
    protected static $handler = null;

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
        self::ERROR_HANDLER => true,
        self::EXCEPTION_HANDLER => false,
        self::FATAL_HANDLER => true,
        self::BYPASS => false,
        self::SESSION_CREATE => false,
        self::SESSION_NAME => 'dh-uid'
    ];


    /**
     * @param string $facility
     * @param array $options
     * @throws Exception
     */
    public function __construct($facility, array $options = [])
    {
        $this->facility = $facility;
        $this->options = array_merge($this->options, $options);

        $this->logger = new \Monolog\Logger($this->facility);
        $this->logger->pushHandler($this->createHandler());

        if(empty(self::$handler))
        {
            self::$handler = new ErrorHandler($this->logger);
            if($this->options[self::ERROR_HANDLER])
            {
                self::$handler->registerErrorHandler([], false, 32511);
            }
            if($this->options[self::EXCEPTION_HANDLER])
            {
                self::$handler->registerExceptionHandler([]);
            }
            if($this->options[self::FATAL_HANDLER])
            {
                self::$handler->registerFatalHandler();
            }
        }

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
                $transport = new UdpTransport('139.59.157.116', 12201, UdpTransport::CHUNK_SIZE_LAN);
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
        if((bool)$this->options[self::SESSION_CREATE])
        {
            if(isset($_COOKIE) && !isset($_COOKIE[self::SESSION_NAME]))
            {
                setcookie(self::SESSION_NAME, md5(uniqid(rand() + time(), true)), 0, '/');
            }
        }
    }


    /**
     * @return bool
     */
    public static function initialized(): bool
    {
        return (!is_null(self::$instance)) ? true : false;
    }


    /**
     * @return Logger
     * @throws Exception
     */
    public static function logger(): Logger
    {
        if(is_null(self::$instance))
        {
            throw new Exception('Logger has not been initialized yet!');
        }
        return self::$instance;
    }


    /**
     * @param $level
     * @param $message
     * @param $group
     * @param null $context
     * @throws Exception
     */
    public function trace($level, $message, $group, $context = null): void
    {
        if(!$this->options[self::BYPASS])
        {
            if(!method_exists($this, $level))
            {
                throw new Exception(sprintf('level: %s is not a valid log level', $level));
            }
            try
            {
                $context = array_merge((array)$context, ['group' => $group]);
                $this->logger->{$level}($message, $this->compile($context));
            }
            catch(\Exception $e){}
        }
    }


    /**
     * @param $message
     * @param null $context
     * @return void
     */
    public function debug($message, $context = null): void
    {
        try
        {
            if(!$this->options[self::BYPASS])
            {
                $this->logger->debug($message, $this->compile($context));
            }
        }
        catch(\Exception $e){}
    }


    /**
     * @param $message
     * @param null $context
     * @return void
     */
    public function info($message, $context = null): void
    {
        try
        {
            if(!$this->options[self::BYPASS])
            {
                $this->logger->info($message, $this->compile($context));
            }
        }
        catch(\Exception $e){}
    }


    /**
     * @param $message
     * @param null $context
     * @return void
     */
    public function notice($message, $context = null): void
    {
        try
        {
            if(!$this->options[self::BYPASS])
            {
                $this->logger->notice($message, $this->compile($context));
            }
        }
        catch(\Exception $e){}
    }


    /**
     * @param $message
     * @param null $context
     * @return void
     */
    public function warning($message, $context = null): void
    {
        try
        {
            if(!$this->options[self::BYPASS])
            {
                $this->logger->warning($message, $this->compile($context));
            }
        }
        catch(\Exception $e){}
    }


    /**
     * @param $message
     * @param null $context
     * @return void
     */
    public function error($message, $context = null): void
    {
        try
        {
            if(!$this->options[self::BYPASS])
            {
                $this->logger->error($message, $this->compile($context));
            }
        }
        catch(\Exception $e){}
    }


    /**
     * @param $message
     * @param null $context
     * @return void
     */
    public function critical($message, $context = null): void
    {
        try
        {
            if(!$this->options[self::BYPASS])
            {
                $this->logger->critical($message, $this->compile($context));
            }
        }
        catch(\Exception $e){}
    }


    /**
     * @param $message
     * @param null $context
     * @return void
     */
    public function alert($message, $context = null): void
    {
        try
        {
            if(!$this->options[self::BYPASS])
            {
                $this->logger->alert($message, $this->compile($context));
            }
        }
        catch(\Exception $e){}
    }


    /**
     * @param $message
     * @param null $context
     * @return void
     */
    public function emergency($message, $context = null): void
    {
        try
        {
            if(!$this->options[self::BYPASS])
            {
                $this->logger->emergency($message, $this->compile($context));
            }
        }
        catch(\Exception $e){}
    }


    /**
     * @param array|null $context
     * @return array|string[]
     */
    protected function compile(?array $context): array
    {
        if(!is_array($context))
        {
            $context = ['param' => (string)$context];
        }
        $env = (defined('WP_ENV') && !empty(WP_ENV)) ? WP_ENV : '';
        $user = (function_exists('get_current_user_id')) ? get_current_user_id() : 0;
        $origin = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)[1];
        $agent = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : 'cli';
        $host = (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : '';
        if(empty($host))
        {
            $host = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
        }
        if(empty($host))
        {
            $host = (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : '';
        }
        if(empty($host))
        {
            $host = php_uname('n');
        }
        $context = $context +
        [
            'file' => $origin['file'],
            'line' => $origin['line'],
            'user' => $user,
            'env' => $env,
            'host' => $host,
            'agent' => $agent,
            'api' => 'server'
        ];
        if(isset($_COOKIE[$this->options[self::SESSION_NAME]]) && !empty($_COOKIE[$this->options[self::SESSION_NAME]]))
        {
            $context['uid'] = $_COOKIE[$this->options[self::SESSION_NAME]];
        }
        if(isset($GLOBALS[$this->options[self::SESSION_NAME]]) && !empty($GLOBALS[$this->options[self::SESSION_NAME]]))
        {
            $context['uid'] = $GLOBALS[$this->options[self::SESSION_NAME]];
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

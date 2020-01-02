<?php

namespace Ajoystick;

/**
 * Class Log
 * @package Ajoystick
 */
class Log
{
    /**
     * log levels map
     */
    const LOG_LEVELS = [
        'none' => 0,
        'error' => 1,
        'warning' => 2,
        'notice' => 3,
        'info' => 4,
        'debug' => 5
    ];

    /**
     * log file handle
     *
     * @var resource
     */
    private static $logFile;

    /**
     * log level
     *
     * @var int
     */
    private static $logLevel = 0;

    /**
     * Log constructor.
     *
     * @param $file
     */
    function __construct($file)
    {
        self::setLogFile($file);
    }

    public function __destruct()
    {
        fclose(self::$logFile);
    }

    /**
     * @param string|array $file
     * @return void
     */
    public static function setLogFile($file)
    {
        $handle = $file === 'php://stdout' ? STDOUT : @fopen($file, 'ab');
        if ($handle === false) {
            exit("can not open log file: $file");
        }
        self::$logFile = $handle;
    }

    /**
     * @return resource
     */
    public static function getLogFile()
    {
        if (is_null(self::$logFile) and defined('LOG_FILE')) {
            self::setLogFile(LOG_FILE);
        }
        return self::$logFile;
    }

    /**
     * @param int $level
     * @return void
     */
    public static function setLogLevel(int $level)
    {
        self::$logLevel = $level;
    }

    /**
     * @param string $message
     * @return void
     */
    public static function debug(string $message)
    {
        if (self::$logLevel < self::LOG_LEVELS['debug']) {
            return;
        }
        $time = date('Y-m-d H:i:s');
        fwrite(self::getLogFile(), "[$time][DEBUG]$ $message\n");
    }

    /**
     * @param string $message
     * @return void
     */
    public static function info(string $message)
    {
        if (self::$logLevel < self::LOG_LEVELS['info']) {
            return;
        }
        $time = date('Y-m-d H:i:s');
        fwrite(self::getLogFile(), "[$time][INFO]$ $message\n");
    }

    /**
     * @param string $message
     * @return void
     */
    public static function notice(string $message)
    {
        if (self::$logLevel < self::LOG_LEVELS['notice']) {
            return;
        }
        $time = date('Y-m-d H:i:s');
        fwrite(self::getLogFile(), "[$time][NOTICE]$ $message\n");
    }

    /**
     * @param string $message
     * @return void
     */
    public static function warning(string $message)
    {
        if (self::$logLevel < self::LOG_LEVELS['warning']) {
            return;
        }
        $time = date('Y-m-d H:i:s');
        fwrite(self::getLogFile(), "[$time][WARNING]$ $message\n");
    }

    /**
     * @param string $message
     * @return void
     */
    public static function error(string $message)
    {
        if (self::$logLevel < self::LOG_LEVELS['error']) {
            return;
        }
        $time = date('Y-m-d H:i:s');
        fwrite(self::getLogFile(), "[$time][ERROR]$ $message\n");
    }
}

<?php
namespace FinXLog\Module;
use \Monolog;

class Logger
{
    private static $inctance;
    /**
     * @var null|\Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * for typical use - single for app
     * configurable Logger::me()->setLogger(\Psr\Log\LoggerInterface)
     * not singleton: multi-instance by "new Logger"
     * @return static
     */
    public static function me()
    {
        if (!static::$inctance) {
            static::$inctance = new static();
        }

        return static::$inctance;
    }

    public static function log()
    {
        return static::me()->getLogger();
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    private function getLoggerDefault()
    {
        $logger = new Monolog\Logger('Language');
        $logger
            ->pushHandler(
                new Monolog\Handler\StreamHandler(
                    'php://stderr',
                    Monolog\Logger::WARNING
                )
            )
            ->pushHandler(
                new Monolog\Handler\StreamHandler(
                    'php://stdout',
                    Monolog\Logger::INFO,
                    false
                )
            );

        return $logger;
    }

    public function getLogger()
    {
        if (!$this->logger) {
            $this->setLogger(
                $this->getLoggerDefault()
            );
        }

        return $this->logger;
    }
}
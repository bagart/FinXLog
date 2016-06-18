<?php
namespace FinXLog\Module;
use FinXLog\Iface;
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

    /**
     * @return null|\Psr\Log\LoggerInterface|Monolog\Logger
     */
    public static function log()
    {
        return static::me()->getLogger();
    }

    public static function error($message, $context = [])
    {
        if ($context instanceof \Throwable) {
            $context = [
                'exception' => get_class($context),
                'trace' =>$context->getTraceAsString()
            ];
            if ($context instanceof Iface\ExceptionWithParams) {
                $context = [
                    'params' => $context->getParams()
                ];
            }
        }

        return static::log()->error($message, $context);
    }


    public static function dbg($message, $context = [])
    {
        if ($context instanceof \Throwable) {
            $context = [
                'exception' => get_class($context),
                'trace' =>$context->getTraceAsString()
            ];
            if ($context instanceof Iface\ExceptionWithParams) {
                $context = [
                    'params' => $context->getParams()
                ];
            }
        }

        return static::log()->debug($message, $context);
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    private function getDefaultCliFormater()
    {
        return new Monolog\Formatter\LineFormatter("%message%\n");
    }

    private function getDefaultSingleLineFormater()
    {
        return new Monolog\Formatter\LineFormatter('%message%');
    }

    private function getLoggerDefault()
    {
        $logger = (new Monolog\Logger('Language'));


        if (!getenv('FINXLOG_DEBUG')) {
            $logger
                ->pushHandler(
                    new Monolog\Handler\StreamHandler(
                        'php://stderr',
                        Monolog\Logger::WARNING
                    )
                );
        } elseif (getenv('FINXLOG_DEBUG') <= Monolog\Logger::DEBUG) {
            $logger
                ->pushHandler(
                    (
                        new Monolog\Handler\StreamHandler(
                            'php://stderr',
                            Monolog\Logger::INFO
                        )
                    )
                        ->setFormatter(
                            new Monolog\Formatter\LineFormatter("\n")
                        )
                )
                ->pushHandler(
                    (
                        new Monolog\Handler\StreamHandler(
                            'php://stderr',
                            Monolog\Logger::DEBUG
                        )
                    )
                        ->setFormatter(
                            $this->getDefaultSingleLineFormater()
                        )
                );
        } else {
            $logger->pushHandler(
                (
                    new Monolog\Handler\StreamHandler(
                        'php://stdout',
                        getenv('FINXLOG_DEBUG') > Monolog\Logger::DEBUG
                            ? getenv('FINXLOG_DEBUG')
                            : Monolog\Logger::INFO
                    )
                )
                    ->setFormatter($this->getDefaultCliFormater())
            );
        }

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
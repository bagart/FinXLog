<?php
namespace FinXLog\Module\Connector;

use FinXLog\Iface;
use FinXLog\Traits;
use Socket\Raw\Factory;

/**
 * internal Iface\connector => external client + param
 * Class Quotation
 * @package FinXLog\Module\Connector
 */
class Quotation implements Iface\QuotationConnector
{
    use Traits\WithConnectorRaw;
    protected $size = 1024;
    protected $read_type = PHP_NORMAL_READ;

    public function setReadType($read_type)
    {
        assert(in_array($read_type, [PHP_NORMAL_READ, PHP_BINARY_READ]));
        assert($read_type != PHP_NORMAL_READ, 'Quotation: $read_type != PHP_NORMAL_READ is not ready');

        $this->read_type = $read_type;

        return $this;
    }

    public function getDefaultConnector()
    {
        return (new Factory())->createClient(
            getenv('FINXLOG_QUOTATION_SERVER_ADDRESS')
                . ':' . getenv('FINXLOG_QUOTATION_SERVER_PORT')
        );
    }

    protected function checkConnector($connector)
    {
        assert(is_object($connector));
        assert(method_exists($connector, 'read'));

        return $this;
    }

    public function read()
    {
        /**
         * @var $connector \Socket\Raw\Socket
         */
        $connector = $this->getConnector();
        return $connector->read(
                $this->size,
                $this->read_type
            );
    }

    public function write($buffer)
    {
        /**
         * @var $connector \Socket\Raw\Socket
         */
        $connector = $this->getConnector();
        $connector->write(
            $buffer
        );

        return $this;
    }

    public function getQuotation()
    {
        return $this->read();
    }

}
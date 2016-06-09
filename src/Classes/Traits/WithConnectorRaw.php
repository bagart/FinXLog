<?php
namespace FinXLog\Traits;

use FinXLog\Iface;


/**
 * Class WithConnectors
 * @package FinXLog\Traits
 */
trait WithConnectorRaw
{
    protected $connector;

    abstract public function getDefaultConnector();

    public function setConnector($connector)
    {
        if (method_exists($this, 'checkConnector')) {
            $this->checkConnector($connector);
        }
        $this->connector = $connector;

        return $this;
    }

    public function getConnector()
    {
        if (!$this->connector) {
            $this->setConnector(
                $this->getDefaultConnector()
            );
        }

        return $this->connector;
    }
}
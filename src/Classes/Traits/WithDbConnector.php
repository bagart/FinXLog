<?php
namespace FinXLog\Traits;

use FinXLog\Iface;


/**
 * Class WithDbConnector
 * @package FinXLog\Traits
 */
trait WithDbConnector
{
    /**
     * @var Iface\QueueConnector
     */
    private $db_connector;

    abstract public function getDefaultDbConnector();

    public function setDbConnector(Iface\QueueConnector $db_connector)
    {
        if (method_exists($this, 'checkDbConnector')) {
            $this->checkDbConnector($db_connector);
        }
        $this->db_connector = $db_connector;

        return $this;
    }

    public function getDbConnector()
    {
        if (!$this->db_connector) {
            $this->setDbConnector(
                $this->getDefaultDbConnector()
            );
        }

        return $this->db_connector;
    }
}